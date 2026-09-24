<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use LogsActivity;

    protected $fillable = [
        'contract_number', 'title', 'type', 'status', 'language', 'start_date', 'end_date', 'terms',
        'subject', 'subject_heading', 'relations_note', 'relations_heading',
        'preamble_mode', 'preamble_text', 'show_parties_table',
        'sections', 'auto_number_sections', 'closing_note',
        'has_zession', 'zession_amount', 'zession_currency', 'zession_notes',
        'territory', 'rights', 'rights_label_a', 'rights_label_b', 'rights_labels',
        'logo_path', 'logo_in_header', 'logo_as_watermark',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'has_zession' => 'boolean',
        'zession_amount' => 'decimal:2',
        'territory' => 'array',
        'rights' => 'array',
        'rights_labels' => 'array',
        'sections' => 'array',
        'show_parties_table' => 'boolean',
        'auto_number_sections' => 'boolean',
        'logo_in_header' => 'boolean',
        'logo_as_watermark' => 'boolean',
    ];

    /**
     * Resolve the party labels used in the rights/remuneration split.
     * Falls back to the legacy two-label model and fills empty slots with a
     * numbered default (e.g. "Partei 1"). Always returns at least two entries.
     */
    public function resolvedRightsLabels(string $defaultPrefix = 'Partei'): array
    {
        $labels = (is_array($this->rights_labels) && count($this->rights_labels) > 0)
            ? $this->rights_labels
            : [$this->rights_label_a, $this->rights_label_b];

        // Party names act as the fallback when a label slot was left blank.
        $partyNames = $this->parties
            ->map(fn ($p) => $p->organization?->primary_name ?? $p->contact?->full_name)
            ->values()
            ->all();

        $resolve = function (int $i, $label) use ($defaultPrefix, $partyNames) {
            if ($label !== null && $label !== '') {
                return $label;
            }
            if (!empty($partyNames[$i])) {
                return $partyNames[$i];
            }
            return $defaultPrefix . ' ' . ($i + 1);
        };

        $out = [];
        foreach (array_values($labels) as $i => $label) {
            $out[] = $resolve($i, $label);
        }
        while (count($out) < 2) {
            $out[] = $resolve(count($out), null);
        }

        // Drop phantom trailing slots: blank stored labels beyond the actual
        // party count (e.g. a party was removed after the rights were saved).
        $rawLabels = array_values($labels);
        while (count($out) > 2
            && count($out) > count($partyNames)
            && empty($rawLabels[count($out) - 1])) {
            array_pop($out);
        }

        return $out;
    }

    /**
     * Group the parties into the blocks the preamble speaks about.
     *
     * Consecutive parties carrying the same role label ("Label", "Künstler")
     * belong to one side of the contract and are named together. A party
     * without a role label stands on its own.
     *
     * @return array<int, array{role: ?string, parties: array<int, ContractParty>}>
     */
    public function partyRoleGroups(): array
    {
        $groups = [];

        foreach ($this->parties as $i => $party) {
            $role = $this->partyRoleLabel($party, $i);
            $key = $role !== null ? mb_strtolower($role) : null;
            $last = count($groups) > 0 ? $groups[count($groups) - 1] : null;

            if ($key !== null && $last !== null && $last['key'] === $key) {
                $groups[count($groups) - 1]['parties'][] = $party;
                continue;
            }

            $groups[] = ['key' => $key, 'role' => $role, 'parties' => [$party]];
        }

        return array_map(fn ($g) => ['role' => $g['role'], 'parties' => $g['parties']], $groups);
    }

    /**
     * The contractual role of a party ("Label", "Künstlerin oder Künstler").
     * Falls back to the rights split label stored for the same slot.
     */
    protected function partyRoleLabel(ContractParty $party, int $index): ?string
    {
        $role = trim((string) ($party->role_label ?? ''));
        if ($role !== '') {
            return $role;
        }

        $labels = is_array($this->rights_labels) ? array_values($this->rights_labels) : [];
        $fallback = trim((string) ($labels[$index] ?? ''));

        return $fallback !== '' ? $fallback : null;
    }

    /**
     * The party preamble, rendered as blocks of plain lines so the PDF and the
     * admin preview stay in sync. Returns an empty array when the preamble is
     * switched off or written by hand.
     *
     * @return array<int, array{lines: array<int, string>, strong: array<int, bool>}>
     */
    public function preambleBlocks(array $t): array
    {
        if (($this->preamble_mode ?? 'auto') !== 'auto') {
            return [];
        }

        $blocks = [];

        foreach ($this->partyRoleGroups() as $group) {
            $lines = [];
            $strong = [];
            $parties = $group['parties'];
            $joint = count($parties) > 1;

            $push = function (string $line, bool $bold = false) use (&$lines, &$strong) {
                $lines[] = $line;
                $strong[] = $bold;
            };

            // A group of several contacts under one organization reads as
            // "<Org> / bestehend aus / <Person>, <Adresse>".
            $orgIds = array_unique(array_filter(array_map(fn ($p) => $p->organization_id, $parties)));
            $sharedOrg = (count($orgIds) === 1 && count($parties) > 1)
                ? $parties[0]->organization
                : null;

            if ($sharedOrg) {
                $push($sharedOrg->primary_name, true);
                $push($t['preamble_consisting_of']);
                foreach ($parties as $party) {
                    $name = $party->contact?->full_name;
                    if (!$name) {
                        continue;
                    }
                    $address = implode(', ', $party->contact ? $this->entityAddressLines($party->contact) : []);
                    $push($address !== '' ? $name . ', ' . $address : $name);
                }
            } else {
                foreach ($parties as $party) {
                    $entity = $party->organization ?? $party->contact;
                    if (!$entity) {
                        continue;
                    }
                    $push($party->organization?->primary_name ?? $party->contact->full_name, true);

                    if ($party->organization && $party->contact) {
                        $push($t['preamble_represented_by'] . ' ' . $party->contact->full_name);
                    }
                    foreach ($this->entityAddressLines($entity) as $line) {
                        $push($line);
                    }
                }
            }

            if ($lines === []) {
                continue;
            }

            if ($group['role']) {
                $key = $joint ? 'preamble_hereinafter_joint' : 'preamble_hereinafter';
                $push('(' . strtr($t[$key], [':role' => $group['role']]) . ')');
            }

            $blocks[] = ['lines' => $lines, 'strong' => $strong];
        }

        return $blocks;
    }

    /**
     * Street and "ZIP City" of a contact or organization, empty parts skipped.
     */
    protected function entityAddressLines($entity): array
    {
        $lines = [];
        if ($entity->street) {
            $lines[] = $entity->street;
        }
        if ($entity->zip || $entity->city) {
            $lines[] = trim(($entity->zip ?? '') . ' ' . ($entity->city ?? ''));
        }

        return $lines;
    }

    /**
     * The contract clauses in printing order. Falls back to the legacy single
     * terms textarea so contracts written before the section editor still print.
     *
     * @return array<int, array{title: string, body: string, page_break: bool}>
     */
    public function resolvedSections(array $t): array
    {
        $sections = is_array($this->sections) ? $this->sections : [];
        $sections = array_values(array_filter(
            $sections,
            fn ($s) => trim((string) ($s['title'] ?? '')) !== '' || trim((string) ($s['body'] ?? '')) !== ''
        ));

        if ($sections === [] && trim((string) $this->terms) !== '') {
            return [[
                'title' => $t['terms_title'],
                'body' => $this->terms,
                'page_break' => false,
                'numbered' => false,
            ]];
        }

        return array_map(fn ($s) => [
            'title' => trim((string) ($s['title'] ?? '')),
            'body' => (string) ($s['body'] ?? ''),
            'page_break' => !empty($s['page_break']),
            'numbered' => !isset($s['numbered']) || !empty($s['numbered']),
        ], $sections);
    }

    /**
     * Per-party percentage values for a single right, aligned to the label list.
     * Falls back to the legacy split_a/split_b pair for older records.
     */
    public function rightSplitValues(array $right): array
    {
        if (isset($right['splits']) && is_array($right['splits'])) {
            return array_values($right['splits']);
        }

        return [$right['split_a'] ?? 0, $right['split_b'] ?? 0];
    }

    /**
     * Translation strings for the contract PDF, keyed by language.
     * Falls back to German for unknown languages.
     */
    public const PDF_STRINGS = [
        'de' => [
            'subtitle' => 'Vertrag',
            'meta_type' => 'Typ',
            'meta_status' => 'Status',
            'meta_start' => 'Startdatum',
            'meta_end' => 'Enddatum',
            'status_draft' => 'Entwurf',
            'status_active' => 'Aktiv',
            'status_expired' => 'Ausgelaufen',
            'status_terminated' => 'Gekündigt',
            'parties_title' => 'Vertragsparteien',
            'parties_col_party' => 'Partei',
            'parties_col_contact' => 'Ansprechperson',
            'parties_col_share' => 'Anteil',
            'subject_title' => 'Vertragsgegenstand',
            'zession_title' => 'Zession (Vorschusszahlung)',
            'zession_note' => 'Dieser Betrag wird mit künftigen Einnahmen verrechnet.',
            'territory_title' => 'Geltungsbereich / Territory',
            'territory_worldwide' => 'Weltweit',
            'rights_title' => 'Vergütung',
            'rights_intro' => 'Die Einnahmen werden zwischen :parties wie folgt aufgeteilt:',
            'rights_col_type' => 'Rechtetyp',
            'rights_col_split' => 'Aufteilung',
            'party_default_prefix' => 'Partei',
            'list_conjunction' => 'und',
            'relations_title' => 'Verknüpfungen',
            'relations_projects' => 'Projekte',
            'relations_tracks' => 'Tracks',
            'relations_track_alt' => 'auch:',
            'relations_releases' => 'Produkte',
            'relations_intro_default' => 'Folgende Songs sind Bestandteil dieses Vertrages.',
            'relations_credits' => 'Credits',
            'terms_title' => 'Bedingungen',
            'signature_line' => 'Ort, Datum, Unterschrift',
            'party_default_a' => 'Partei 1',
            'party_default_b' => 'Partei 2',
            'generated_on' => 'Generiert am',
            'phone_short' => 'Tel.',
            'page' => 'Seite',
            'page_of' => 'von',
            'preamble_title' => 'Vertragsparteien',
            'preamble_represented_by' => 'vertreten durch',
            'preamble_consisting_of' => 'bestehend aus',
            'preamble_hereinafter' => 'nachfolgend «:role»',
            'preamble_hereinafter_joint' => 'nachfolgend gemeinsam «:role»',
            'preamble_and' => 'und',
            'closing_title' => 'Schlussbestimmungen',
            'parties_total' => 'Total',
        ],
        'en' => [
            'subtitle' => 'Contract',
            'meta_type' => 'Type',
            'meta_status' => 'Status',
            'meta_start' => 'Start date',
            'meta_end' => 'End date',
            'status_draft' => 'Draft',
            'status_active' => 'Active',
            'status_expired' => 'Expired',
            'status_terminated' => 'Terminated',
            'parties_title' => 'Contracting parties',
            'parties_col_party' => 'Party',
            'parties_col_contact' => 'Contact person',
            'parties_col_share' => 'Share',
            'subject_title' => 'Subject of contract',
            'zession_title' => 'Advance payment',
            'zession_note' => 'This amount will be offset against future revenue.',
            'territory_title' => 'Territory',
            'territory_worldwide' => 'Worldwide',
            'rights_title' => 'Remuneration',
            'rights_intro' => 'Revenue is split between :parties as follows:',
            'rights_col_type' => 'Type of right',
            'rights_col_split' => 'Split',
            'party_default_prefix' => 'Party',
            'list_conjunction' => 'and',
            'relations_title' => 'Linked items',
            'relations_projects' => 'Projects',
            'relations_tracks' => 'Tracks',
            'relations_track_alt' => 'also:',
            'relations_releases' => 'Products',
            'relations_intro_default' => 'The following songs are part of this contract.',
            'relations_credits' => 'Credits',
            'terms_title' => 'Terms',
            'signature_line' => 'Place, date, signature',
            'party_default_a' => 'Party 1',
            'party_default_b' => 'Party 2',
            'generated_on' => 'Generated on',
            'phone_short' => 'Phone',
            'page' => 'Page',
            'page_of' => 'of',
            'preamble_title' => 'Contracting parties',
            'preamble_represented_by' => 'represented by',
            'preamble_consisting_of' => 'consisting of',
            'preamble_hereinafter' => 'hereinafter “:role”',
            'preamble_hereinafter_joint' => 'hereinafter jointly “:role”',
            'preamble_and' => 'and',
            'closing_title' => 'Final provisions',
            'parties_total' => 'Total',
        ],
        'es' => [
            'subtitle' => 'Contrato',
            'meta_type' => 'Tipo',
            'meta_status' => 'Estado',
            'meta_start' => 'Fecha de inicio',
            'meta_end' => 'Fecha de finalización',
            'status_draft' => 'Borrador',
            'status_active' => 'Activo',
            'status_expired' => 'Expirado',
            'status_terminated' => 'Rescindido',
            'parties_title' => 'Partes contratantes',
            'parties_col_party' => 'Parte',
            'parties_col_contact' => 'Persona de contacto',
            'parties_col_share' => 'Participación',
            'subject_title' => 'Objeto del contrato',
            'zession_title' => 'Anticipo (pago anticipado)',
            'zession_note' => 'Este importe se compensará con los ingresos futuros.',
            'territory_title' => 'Territorio',
            'territory_worldwide' => 'Mundial',
            'rights_title' => 'Remuneración',
            'rights_intro' => 'Los ingresos se reparten entre :parties de la siguiente manera:',
            'rights_col_type' => 'Tipo de derecho',
            'rights_col_split' => 'Reparto',
            'party_default_prefix' => 'Parte',
            'list_conjunction' => 'y',
            'relations_title' => 'Vínculos',
            'relations_projects' => 'Proyectos',
            'relations_tracks' => 'Pistas',
            'relations_track_alt' => 'también:',
            'relations_releases' => 'Productos',
            'relations_intro_default' => 'Las siguientes canciones forman parte de este contrato.',
            'relations_credits' => 'Créditos',
            'terms_title' => 'Condiciones',
            'signature_line' => 'Lugar, fecha, firma',
            'party_default_a' => 'Parte 1',
            'party_default_b' => 'Parte 2',
            'generated_on' => 'Generado el',
            'phone_short' => 'Tel.',
            'page' => 'Página',
            'page_of' => 'de',
            'preamble_title' => 'Partes contratantes',
            'preamble_represented_by' => 'representada por',
            'preamble_consisting_of' => 'integrada por',
            'preamble_hereinafter' => 'en adelante «:role»',
            'preamble_hereinafter_joint' => 'en adelante conjuntamente «:role»',
            'preamble_and' => 'y',
            'closing_title' => 'Disposiciones finales',
            'parties_total' => 'Total',
        ],
    ];

    /**
     * Get the translation strings for this contract's language.
     */
    public static function pdfStrings(?string $language): array
    {
        return self::PDF_STRINGS[$language] ?? self::PDF_STRINGS['de'];
    }

    /**
     * Contact details of contracting party A (the first party) for the PDF header.
     * Returns null when no usable entity is available.
     */
    public function getHeaderPartyAttribute(): ?array
    {
        $party = $this->parties->first();
        if (!$party) {
            return null;
        }

        $org = $party->organization;
        $contact = $party->contact;
        $entity = $org ?? $contact;
        if (!$entity) {
            return null;
        }

        // Name + optional contact person line
        if ($org) {
            $name = $org->primary_name;
            $contactName = $contact?->full_name;
            $website = collect($org->websites ?? [])->first();
        } else {
            $name = $contact->full_name;
            $contactName = null;
            $website = null;
        }

        $addressLines = [];
        if ($entity->street) {
            $addressLines[] = $entity->street;
        }
        if ($entity->zip || $entity->city) {
            $addressLines[] = trim(($entity->zip ?? '') . ' ' . ($entity->city ?? ''));
        }

        return [
            'name' => $name,
            'contact_name' => $contactName,
            'address_lines' => $addressLines,
            'website' => $website ?: null,
            'phone' => $entity->phone ?: null,
            'email' => $entity->email ?: null,
        ];
    }

    /**
     * Territory presets for quick selection.
     */
    public const TERRITORY_PRESETS = [
        'world' => ['label' => 'Weltweit', 'countries' => ['ALL']],
        'europe' => ['label' => 'Europa', 'countries' => ['AL','AD','AT','BY','BE','BA','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IS','IE','IT','XK','LV','LI','LT','LU','MT','MD','MC','ME','NL','MK','NO','PL','PT','RO','RU','SM','RS','SK','SI','ES','SE','CH','UA','GB','VA']],
        'usa' => ['label' => 'USA', 'countries' => ['US']],
        'gsa' => ['label' => 'GSA (DACH)', 'countries' => ['DE','AT','CH']],
        'uk' => ['label' => 'UK', 'countries' => ['GB']],
        'nordics' => ['label' => 'Nordics', 'countries' => ['DK','FI','IS','NO','SE']],
        'benelux' => ['label' => 'Benelux', 'countries' => ['BE','NL','LU']],
    ];

    /**
     * Get human-readable territory display.
     */
    public function getTerritoryDisplayAttribute(): string
    {
        if (empty($this->territory)) {
            return '';
        }

        if (in_array('ALL', $this->territory)) {
            return 'Weltweit';
        }

        // Check if it matches a preset
        foreach (self::TERRITORY_PRESETS as $key => $preset) {
            if ($preset['countries'] === ['ALL']) continue;
            $presetCountries = $preset['countries'];
            sort($presetCountries);
            $territory = $this->territory;
            sort($territory);
            if ($presetCountries === $territory) {
                return $preset['label'];
            }
        }

        return count($this->territory) . ' Länder';
    }

    public function parties()
    {
        return $this->hasMany(ContractParty::class)->orderBy('sort_order');
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_contract')->withPivot('role');
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "VT-{$year}-";

        $last = static::where('contract_number', 'like', "{$prefix}%")
            ->orderByRaw("CAST(SUBSTR(contract_number, " . (strlen($prefix) + 1) . ") AS INTEGER) DESC")
            ->first();

        if ($last) {
            $nextSeq = (int) substr($last->contract_number, strlen($prefix)) + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_contract');
    }

    public function tracks()
    {
        return $this->belongsToMany(Track::class);
    }

    public function releases()
    {
        return $this->belongsToMany(Release::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class);
    }
}
