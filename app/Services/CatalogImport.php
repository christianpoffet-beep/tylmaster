<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Release;
use App\Models\Setting;
use App\Models\Track;
use App\Models\TrackAudioFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * One-time import of the label catalogue from the SWISSPERFORM repertoire
 * list (November 2026): products, their tracks, bands and labels.
 *
 * The file is prepared outside the admin, with every decision about the
 * spreadsheet already applied; this class only checks it against the data
 * that is there and writes it. plan() only reads, run() writes - all or
 * nothing, in one transaction.
 *
 * What exists already is not changed, only linked: a product is recognised
 * by its EAN, else its catalogue number, else (with neither) its title; a
 * track by its ISRC; a band or label by any of its names. A second run of the
 * same file therefore creates nothing.
 *
 * Goes again together with its page once the catalogue is in.
 */
class CatalogImport
{
    public const FORMAT = 'tyl-catalog-import';

    private const STATUSES = ['draft', 'released', 'archived'];

    private function __construct(private array $data)
    {
    }

    /** @throws \InvalidArgumentException when the text is not an import file */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);

        if (!is_array($data) || ($data['format'] ?? null) !== self::FORMAT || !is_array($data['products'] ?? null)) {
            throw new \InvalidArgumentException('Das ist keine Katalog-Importdatei.');
        }
        if ((int) ($data['version'] ?? 0) !== 1) {
            throw new \InvalidArgumentException('Diese Version der Importdatei kennt der Import nicht.');
        }

        return new self($data);
    }

    /** Where the file came from, for the page. */
    public function meta(): array
    {
        return [
            'source' => (string) ($this->data['source'] ?? ''),
            'created' => (string) ($this->data['created'] ?? ''),
        ];
    }

    /**
     * What run() would do, without writing anything. Errors block the import;
     * warnings are for a look before it starts.
     *
     * @return array{errors: string[], warnings: string[], organizations: array, products: array, counts: array}
     */
    public function plan(): array
    {
        $errors = [];
        $warnings = [];
        $knownTypes = Setting::productTypeValues();

        $releases = Release::withCount('tracks')->orderBy('id')->get();
        $releasesByEan = $releases->filter(fn ($r) => filled($r->ean_upc))->groupBy(fn ($r) => $this->eanKey($r->ean_upc));
        $releasesByCatalog = $releases->filter(fn ($r) => filled($r->catalog_number))->groupBy(fn ($r) => $this->key($r->catalog_number));
        $releasesByTitle = $releases->groupBy(fn ($r) => $this->key($r->title));

        $tracksByIsrc = Track::whereNotNull('isrc')->where('isrc', '!=', '')->orderBy('id')
            ->get(['id', 'title', 'version', 'isrc'])
            ->groupBy(fn ($t) => Track::normalizeIsrc($t->isrc));
        $audioByIsrc = TrackAudioFile::whereNotNull('isrc')->where('isrc', '!=', '')
            ->get(['id', 'track_id', 'isrc'])
            ->groupBy(fn ($a) => Track::normalizeIsrc($a->isrc));
        $links = DB::table('product_track')->get(['release_id', 'track_id'])
            ->map(fn ($l) => $l->release_id . '-' . $l->track_id)
            ->flip();

        $organizations = [];
        $products = [];
        $seen = ['ean' => [], 'catalog' => [], 'isrc' => [], 'release' => []];

        foreach ($this->data['products'] as $i => $p) {
            $p = is_array($p) ? $p : [];
            $title = $this->text($p['title'] ?? null);
            $where = 'Produkt ' . ($i + 1) . ($title ? " «{$title}»" : '');

            if (!$title) {
                $errors[] = "{$where}: Titel fehlt.";
                continue;
            }

            $ean = $this->text($p['ean_upc'] ?? null);
            $catalog = $this->text($p['catalog_number'] ?? null);
            $date = $this->text($p['release_date'] ?? null);
            $types = array_values(array_filter(array_map(fn ($t) => $this->text($t), (array) ($p['product_type'] ?? []))));
            $bands = array_values(array_unique(array_filter(array_map(fn ($b) => $this->text($b), (array) ($p['bands'] ?? [])))));
            $label = $this->text($p['label'] ?? null);

            $this->checkLength($errors, $where, 'Titel', $title, 255);
            $this->checkLength($errors, $where, 'EAN / UPC', $ean, 30);
            $this->checkLength($errors, $where, 'Katalognummer', $catalog, 50);
            if ($date !== null && !$this->isDate($date)) {
                $errors[] = "{$where}: Datum «{$date}» ist kein gültiges Datum (JJJJ-MM-TT).";
            }
            foreach ($types as $type) {
                if (!in_array($type, $knownTypes, true)) {
                    $errors[] = "{$where}: Den Typ «{$type}» gibt es in den Einstellungen (Produkt-Typen) nicht.";
                }
            }
            if (!$bands) {
                $errors[] = "{$where}: keine Band.";
            }
            foreach (['ean' => $ean ? $this->eanKey($ean) : null, 'catalog' => $catalog ? $this->key($catalog) : null] as $kind => $value) {
                if ($value === null) continue;
                if (isset($seen[$kind][$value])) {
                    $errors[] = "{$where}: " . ($kind === 'ean' ? "EAN {$ean}" : "Katalognummer {$catalog}") . ' steht in der Datei zweimal.';
                }
                $seen[$kind][$value] = true;
            }

            foreach ($bands as $band) {
                $this->wantOrganization($organizations, 'band', $band, null);
            }
            if ($label) {
                $this->wantOrganization($organizations, 'label', $label, $catalog);
            }

            // Recognise a product that is there already
            $existing = null;
            $matchedBy = null;
            if ($ean && $releasesByEan->has($this->eanKey($ean))) {
                [$existing, $matchedBy] = [$releasesByEan[$this->eanKey($ean)], 'EAN'];
            } elseif ($catalog && $releasesByCatalog->has($this->key($catalog))) {
                [$existing, $matchedBy] = [$releasesByCatalog[$this->key($catalog)], 'Katalognummer'];
            } elseif (!$ean && !$catalog && $releasesByTitle->has($this->key($title))) {
                [$existing, $matchedBy] = [$releasesByTitle[$this->key($title)], 'Titel'];
            }
            if ($existing) {
                if ($existing->count() > 1) {
                    $warnings[] = "{$where}: {$existing->count()} vorhandene Produkte mit gleicher {$matchedBy} - verknüpft wird #{$existing->first()->id}.";
                }
                $existing = $existing->first();
                if (isset($seen['release'][$existing->id])) {
                    $errors[] = "{$where}: landet auf demselben vorhandenen Produkt #{$existing->id} wie ein anderes Produkt der Datei.";
                }
                $seen['release'][$existing->id] = true;

                if ($matchedBy === 'EAN' && $catalog && filled($existing->catalog_number) && $this->key($existing->catalog_number) !== $this->key($catalog)) {
                    $warnings[] = "{$where}: vorhanden über die EAN, aber dort mit Katalognummer {$existing->catalog_number} statt {$catalog}.";
                }
                if ($matchedBy === 'Katalognummer' && $ean && filled($existing->ean_upc)) {
                    $warnings[] = "{$where}: vorhanden über die Katalognummer, aber dort mit EAN {$existing->ean_upc} statt {$ean}.";
                }
                if ($existing->tracks_count > 0) {
                    $warnings[] = "{$where}: vorhanden mit {$existing->tracks_count} Track(s) - die Tracklist wird ergänzt, nicht ersetzt.";
                }
            }

            $tracks = [];
            foreach ((array) ($p['tracks'] ?? []) as $j => $t) {
                $t = is_array($t) ? $t : [];
                $trackTitle = $this->text($t['title'] ?? null);
                $trackWhere = "{$where}, Track " . ($j + 1) . ($trackTitle ? " «{$trackTitle}»" : '');

                if (!$trackTitle) {
                    $errors[] = "{$trackWhere}: Titel fehlt.";
                    continue;
                }

                $version = $this->text($t['version'] ?? null);
                $isrc = Track::normalizeIsrc($this->text($t['isrc'] ?? null));
                $genre = $this->text($t['genre'] ?? null);
                $years = $this->text($t['recording_years'] ?? null);
                $status = $this->text($t['status'] ?? null) ?? 'released';
                $band = $this->text($t['band'] ?? null);
                $number = $t['number'] ?? null;
                $duration = $t['duration_seconds'] ?? null;

                $this->checkLength($errors, $trackWhere, 'Titel', $trackTitle, 255);
                $this->checkLength($errors, $trackWhere, 'Version', $version, 100);
                $this->checkLength($errors, $trackWhere, 'Genre', $genre, 100);
                if ($isrc !== null && !preg_match(Track::ISRC_REGEX, $isrc)) {
                    $errors[] = "{$trackWhere}: ISRC «{$isrc}» ist ungültig.";
                }
                if ($years !== null && (!preg_match(Track::RECORDING_YEARS_REGEX, $years) || mb_strlen($years) > 50)) {
                    $errors[] = "{$trackWhere}: Aufnahmejahr «{$years}» ist ungültig.";
                }
                if (!in_array($status, self::STATUSES, true)) {
                    $errors[] = "{$trackWhere}: Status «{$status}» gibt es nicht.";
                }
                if (!is_int($number) || $number < 1 || $number > 9999) {
                    $errors[] = "{$trackWhere}: Tracknummer fehlt oder ist ungültig.";
                }
                if ($duration !== null && (!is_int($duration) || $duration < 0)) {
                    $errors[] = "{$trackWhere}: Dauer ist ungültig.";
                }
                if (!$band || !in_array($band, $bands, true)) {
                    $errors[] = "{$trackWhere}: Band «{$band}» gehört nicht zu den Bands des Produkts.";
                }

                $existingTrack = null;
                if ($isrc !== null) {
                    if (isset($seen['isrc'][$isrc])) {
                        $errors[] = "{$trackWhere}: ISRC {$isrc} steht in der Datei zweimal.";
                    }
                    $seen['isrc'][$isrc] = true;

                    if ($tracksByIsrc->has($isrc)) {
                        $matches = $tracksByIsrc[$isrc];
                        $existingTrack = $matches->first();
                        if ($matches->count() > 1) {
                            $warnings[] = "{$trackWhere}: ISRC {$isrc} hat schon {$matches->count()} Tracks - verknüpft wird #{$existingTrack->id}.";
                        }
                    } elseif ($audioByIsrc->has($isrc)) {
                        $errors[] = "{$trackWhere}: ISRC {$isrc} steht schon bei einer Audioversion von Track #{$audioByIsrc[$isrc]->first()->track_id}.";
                    }
                }

                $tracks[] = [
                    'number' => $number,
                    'title' => $trackTitle,
                    'version' => $version,
                    'isrc' => $isrc,
                    'genre' => $genre,
                    'duration_seconds' => $duration,
                    'recording_years' => $years,
                    'status' => $status,
                    'band' => $band,
                    'existing' => $existingTrack,
                    'linked' => $existing && $existingTrack && $links->has($existing->id . '-' . $existingTrack->id),
                ];
            }

            if (!$tracks) {
                $warnings[] = "{$where}: ohne Tracks.";
            }

            $products[] = [
                'title' => $title,
                'catalog_number' => $catalog,
                'ean_upc' => $ean,
                'release_date' => $date,
                'product_type' => $types,
                'bands' => $bands,
                'label' => $label,
                'existing' => $existing,
                'matched_by' => $matchedBy,
                'tracks' => $tracks,
            ];
        }

        $this->resolveOrganizations($organizations, $warnings);

        $tracks = collect($products)->flatMap(fn ($p) => $p['tracks']);

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'organizations' => $organizations,
            'products' => $products,
            'counts' => [
                'products_new' => collect($products)->whereNull('existing')->count(),
                'products_existing' => collect($products)->whereNotNull('existing')->count(),
                'tracks_new' => $tracks->whereNull('existing')->count(),
                'tracks_existing' => $tracks->whereNotNull('existing')->count(),
                'bands_new' => collect($organizations)->where('type', 'band')->whereNull('existing')->count(),
                'bands_existing' => collect($organizations)->where('type', 'band')->whereNotNull('existing')->count(),
                'labels_new' => collect($organizations)->where('type', 'label')->whereNull('existing')->count(),
                'labels_existing' => collect($organizations)->where('type', 'label')->whereNotNull('existing')->count(),
            ],
        ];
    }

    /**
     * Writes the plan. Nothing is saved when anything goes wrong on the way.
     *
     * @return array<string, int> what was created and linked
     * @throws \RuntimeException when the plan has errors
     */
    public function run(): array
    {
        return DB::transaction(function () {
            $plan = $this->plan();

            if ($plan['errors']) {
                throw new \RuntimeException('Die Datei hat ' . count($plan['errors']) . ' Fehler, es wurde nichts importiert.');
            }

            $result = ['organizations' => 0, 'products' => 0, 'tracks' => 0, 'links' => 0];

            $orgIds = [];
            foreach ($plan['organizations'] as $key => $org) {
                if ($org['existing']) {
                    $orgIds[$key] = $org['existing']->id;
                    continue;
                }

                $orgIds[$key] = Organization::create([
                    'type' => $org['type'],
                    'names' => [$org['name']],
                    'catalogs' => $org['type'] === 'label' && $org['catalogs'] ? $org['catalogs'] : null,
                ])->id;
                $result['organizations']++;
            }

            foreach ($plan['products'] as $p) {
                $release = $p['existing'];
                if (!$release) {
                    $release = Release::create([
                        'title' => $p['title'],
                        'product_type' => $p['product_type'],
                        'ean_upc' => $p['ean_upc'],
                        'release_date' => $p['release_date'],
                        'catalog_number' => $p['catalog_number'],
                    ]);
                    $result['products']++;
                }

                $labelId = $p['label'] ? $orgIds[$this->orgKey('label', $p['label'])] : null;
                $productOrgIds = array_map(fn ($band) => $orgIds[$this->orgKey('band', $band)], $p['bands']);
                if ($labelId) {
                    $productOrgIds[] = $labelId;
                }
                $release->organizations()->syncWithoutDetaching($productOrgIds);

                foreach ($p['tracks'] as $t) {
                    $track = $t['existing'];
                    if (!$track) {
                        $track = Track::create([
                            'title' => $t['title'],
                            'version' => $t['version'],
                            'isrc' => $t['isrc'],
                            'genre' => $t['genre'],
                            'duration_seconds' => $t['duration_seconds'],
                            'recording_years' => $t['recording_years'],
                            'status' => $t['status'],
                        ]);
                        $result['tracks']++;
                    }

                    $track->organizations()->syncWithoutDetaching(array_filter([$orgIds[$this->orgKey('band', $t['band'])], $labelId]));

                    if (!$t['linked']) {
                        $release->tracks()->syncWithoutDetaching([
                            $track->id => ['track_number' => $t['number'], 'disc_number' => 1, 'role' => 'main'],
                        ]);
                        $result['links']++;
                    }
                }
            }

            return $result;
        });
    }

    /** Collects each band and label once; a label also gathers the catalogue prefixes it is used with. */
    private function wantOrganization(array &$organizations, string $type, string $name, ?string $catalog): void
    {
        $key = $this->orgKey($type, $name);
        $organizations[$key] ??= ['type' => $type, 'name' => $name, 'catalogs' => [], 'existing' => null];

        if ($catalog && preg_match('/^([A-Z]+)\d+$/i', $catalog, $m) && !in_array(strtoupper($m[1]), $organizations[$key]['catalogs'], true)) {
            $organizations[$key]['catalogs'][] = strtoupper($m[1]);
        }
    }

    /**
     * Finds each band and label among the organizations by any of its names.
     * Of several with the same name a label whose catalogues fit is taken,
     * otherwise the oldest - with a warning either way.
     */
    private function resolveOrganizations(array &$organizations, array &$warnings): void
    {
        $byName = [];
        foreach (Organization::orderBy('id')->get(['id', 'type', 'names', 'catalogs']) as $org) {
            foreach ((array) $org->names as $name) {
                $byName[$this->key((string) $name)][$org->id] = $org;
            }
        }

        foreach ($organizations as &$wanted) {
            $all = collect($byName[$this->key($wanted['name'])] ?? []);
            $ofType = $all->where('type', $wanted['type'])->values();
            $what = $wanted['type'] === 'band' ? 'Band' : 'Label';

            if ($ofType->isEmpty()) {
                if ($all->isNotEmpty()) {
                    $warnings[] = "{$what} «{$wanted['name']}» gibt es schon als Organisation vom Typ «{$all->first()->type}» (#{$all->first()->id}) - wird trotzdem neu als {$what} angelegt.";
                }
                continue;
            }

            $chosen = $ofType->first();
            if ($ofType->count() > 1) {
                $fitting = $ofType->filter(fn ($org) => array_intersect(array_map('strtoupper', (array) $org->catalogs), $wanted['catalogs']));
                $chosen = $fitting->first() ?? $chosen;
                $warnings[] = "{$what} «{$wanted['name']}» gibt es {$ofType->count()}× (#" . $ofType->pluck('id')->join(', #') . ") - verknüpft wird #{$chosen->id}.";
            }

            $wanted['existing'] = $chosen;
        }
    }

    private function checkLength(array &$errors, string $where, string $field, ?string $value, int $max): void
    {
        if ($value !== null && mb_strlen($value) > $max) {
            $errors[] = "{$where}: {$field} ist länger als {$max} Zeichen.";
        }
    }

    private function isDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value;
    }

    private function text(mixed $value): ?string
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function key(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    private function eanKey(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }

    private function orgKey(string $type, string $name): string
    {
        return $type . '|' . $this->key($name);
    }
}
