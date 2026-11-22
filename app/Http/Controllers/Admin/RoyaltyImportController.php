<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use App\Models\RoyaltyImport;
use App\Models\RoyaltyLine;
use App\Models\Track;
use App\Services\DocumentStorage;
use App\Services\Royalties\RebeatImporter;
use App\Services\Royalties\RoyaltyImportException;
use App\Services\Royalties\RoyaltyMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Rebeat-Matcher: the accounting export of Rebeat is read in, its lines are
 * assigned to the tracks (by ISRC, else by hand) and the import confirmed.
 */
class RoyaltyImportController extends Controller
{
    private const SOURCE = RebeatImporter::SOURCE;

    public function index()
    {
        $imports = RoyaltyImport::where('source', self::SOURCE)
            ->withCount(['lines', 'lines as open_count' => fn ($q) => $q->whereNull('track_id')->whereNull('release_id')])
            ->withSum('lines', 'amount')
            ->latest()->get();

        // Per month in SQL (works the same in SQLite and MySQL), per year in PHP
        $years = RoyaltyLine::where('source', self::SOURCE)
            ->selectRaw('period, count(*) as n, sum(quantity) as quantity, sum(amount) as amount,
                sum(case when track_id is null and release_id is null then amount else 0 end) as open_amount')
            ->groupBy('period')->get()
            ->groupBy(fn ($row) => substr((string) $row->getRawOriginal('period'), 0, 4))
            ->map(fn (Collection $months) => [
                'lines' => $months->sum('n'),
                'quantity' => $months->sum('quantity'),
                'amount' => (float) $months->sum('amount'),
                'open_amount' => (float) $months->sum('open_amount'),
            ])->sortKeys();

        return view('admin.royalties.index', [
            'imports' => $imports,
            'years' => $years,
            'uploadLimit' => self::uploadLimit(),
        ]);
    }

    public function store(Request $request, RebeatImporter $importer)
    {
        $request->validate([
            'file' => 'required|file|max:409600',
        ], [
            'file.required' => 'Bitte die CSV-Datei aus Rebeat wählen.',
            'file.uploaded' => 'Die Datei ist grösser, als der Server annimmt.',
        ]);

        $file = $request->file('file');
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true)) {
            return back()->with('error', 'Bitte die CSV-Datei aus Rebeat hochladen (nicht in Excel öffnen und speichern).');
        }

        @set_time_limit(300);
        $path = $file->storeAs('royalty-imports', now()->format('Ymd-His') . '-' . Str::random(6) . '.csv', DocumentStorage::DISK);

        try {
            $import = $importer->import($file->getRealPath(), $file->getClientOriginalName(), $path ?: null);
        } catch (RoyaltyImportException $e) {
            DocumentStorage::delete($path ?: null);

            return back()->with('error', $e->getMessage());
        }

        if ($import->rows_new === 0) {
            DocumentStorage::delete($import->file_path);
            $import->delete();

            return back()->with('error', "Alle {$import->rows_duplicate} Zeilen dieser Datei sind schon importiert.");
        }

        $message = number_format($import->rows_new, 0, '.', "'") . ' Zeilen eingelesen';
        if ($import->rows_duplicate) $message .= ', ' . number_format($import->rows_duplicate, 0, '.', "'") . ' waren schon da';
        if ($import->rows_presettled) $message .= ', ' . number_format($import->rows_presettled, 0, '.', "'") . ' sind vor dem Stichtag schon abgerechnet';

        return redirect()->route('admin.royalties.show', $import)->with('success', $message . '.');
    }

    public function show(RoyaltyImport $royaltyImport, RoyaltyMatcher $matcher)
    {
        $groups = $this->groups($royaltyImport);
        [$open, $assigned] = $groups->partition(fn ($g) => !$g->track_id && !$g->release_id);

        $knownIsrcs = Track::whereIn('isrc', $open->pluck('isrc')->filter())->pluck('isrc')->flip();
        $open = $open->sortByDesc('amount')->values()->map(function ($group) use ($matcher, $knownIsrcs) {
            $group->suggestions = $matcher->suggestions($group->isrc, $group->artist, $group->title);
            $group->isrc_taken = $group->isrc && $knownIsrcs->has($group->isrc);

            return $group;
        });

        $lines = $royaltyImport->lines();
        $totals = (clone $lines)->selectRaw('count(*) as n, sum(quantity) as quantity, sum(amount_gross) as gross, sum(amount) as amount')->first();

        return view('admin.royalties.show', [
            'import' => $royaltyImport,
            'open' => $open,
            'assigned' => $assigned->sortBy(fn ($g) => mb_strtolower(($g->track?->title ?? $g->release?->title ?? '') . ' ' . $g->isrc))->values(),
            'totals' => $totals,
            'byLabel' => (clone $lines)->selectRaw('label, count(*) as n, sum(amount) as amount')->groupBy('label')->orderByDesc('amount')->get(),
            'byType' => (clone $lines)->selectRaw('sales_type, sales_category, count(*) as n, sum(quantity) as quantity, sum(amount) as amount')
                ->groupBy('sales_type', 'sales_category')->orderByDesc('amount')->get(),
        ]);
    }

    public function assign(Request $request, RoyaltyImport $royaltyImport, RoyaltyMatcher $matcher)
    {
        $data = $request->validate([
            'isrc' => 'nullable|string|max:15',
            'artist' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'track_id' => 'nullable|integer|exists:tracks,id',
            'release_id' => 'nullable|integer|exists:releases,id',
        ]);

        $count = $matcher->assign(self::SOURCE, $data['isrc'] ?? null, $data['artist'] ?? null, $data['title'] ?? null,
            $data['track_id'] ?? null, $data['release_id'] ?? null);

        if (!empty($data['track_id'])) {
            $message = "{$count} Zeilen dem Track «" . Track::find($data['track_id'])->display_title . '» zugeordnet.';
        } elseif (!empty($data['release_id'])) {
            $message = "{$count} Zeilen dem Produkt «" . Release::find($data['release_id'])->title . '» zugeordnet.';
        } else {
            $message = 'Zuordnung entfernt.';
        }

        return redirect()->to(route('admin.royalties.show', $royaltyImport) . '#offen')->with('success', $message);
    }

    public function createTrack(Request $request, RoyaltyImport $royaltyImport, RoyaltyMatcher $matcher)
    {
        $data = $request->validate([
            'isrc' => 'required|string|max:15',
            'artist' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
        ]);

        if ($existing = Track::where('isrc', Track::normalizeIsrc($data['isrc']))->first()) {
            return back()->with('error', "Den Track mit dieser ISRC gibt es schon: «{$existing->display_title}». Bitte ihm zuordnen.");
        }

        $track = $matcher->createTrack(self::SOURCE, $data['isrc'], $data['artist'] ?? null, $data['title'], $data['label'] ?? null);

        return redirect()->to(route('admin.royalties.show', $royaltyImport) . '#offen')
            ->with('success', "Track «{$track->title}» angelegt und zugeordnet." . ($track->organizations()->exists() ? '' : ' Band und Label bitte beim Track ergänzen.'));
    }

    public function rematch(RoyaltyMatcher $matcher)
    {
        $count = $matcher->apply(self::SOURCE);

        return back()->with('success', $count ? "{$count} weitere Zeilen über die ISRC zugeordnet." : 'Keine weiteren Zeilen gefunden, die über die ISRC zu einem Track passen.');
    }

    public function confirm(RoyaltyImport $royaltyImport)
    {
        if ($royaltyImport->is_confirmed) {
            $royaltyImport->update(['confirmed_at' => null]);

            return back()->with('success', 'Bestätigung aufgehoben.');
        }

        $royaltyImport->update(['confirmed_at' => now()]);
        $open = $royaltyImport->lines()->whereNull('track_id')->whereNull('release_id')->count();

        return back()->with('success', 'Import bestätigt.' . ($open ? " {$open} Zeilen haben noch keinen Track – ihr Anteil bleibt bei Yelling Light, bis sie zugeordnet sind." : ''));
    }

    public function file(RoyaltyImport $royaltyImport)
    {
        $path = DocumentStorage::path($royaltyImport->file_path) ?? abort(404);

        return DocumentStorage::privately(response()->download($path, $royaltyImport->file_name));
    }

    public function destroy(RoyaltyImport $royaltyImport)
    {
        $royaltyImport->lines()->delete();
        DocumentStorage::delete($royaltyImport->file_path);
        $royaltyImport->delete();

        return redirect()->route('admin.royalties.index')->with('success', 'Import entfernt.');
    }

    /**
     * The import's lines per ISRC (album lines without one: per artist and
     * title), with what they are assigned to and their sums.
     */
    private function groups(RoyaltyImport $import): Collection
    {
        $sums = 'count(*) as n, sum(quantity) as quantity, sum(amount) as amount';

        $withIsrc = $import->lines()->whereNotNull('isrc')
            ->selectRaw("isrc, min(artist) as artist, min(title) as title, min(label) as label, track_id, release_id, match_type, {$sums}")
            ->groupBy('isrc', 'track_id', 'release_id', 'match_type')->toBase()->get();
        $withoutIsrc = $import->lines()->whereNull('isrc')
            ->selectRaw("isrc, artist, title, min(label) as label, track_id, release_id, match_type, {$sums}")
            ->groupBy('isrc', 'artist', 'title', 'track_id', 'release_id', 'match_type')->toBase()->get();

        $groups = $withIsrc->concat($withoutIsrc);
        $tracks = Track::with('organizations')->findMany($groups->pluck('track_id')->filter()->unique())->keyBy('id');
        $releases = Release::findMany($groups->pluck('release_id')->filter()->unique())->keyBy('id');

        return $groups->map(function ($group) use ($tracks, $releases) {
            $group->track = $group->track_id ? $tracks->get($group->track_id) : null;
            $group->release = $group->release_id ? $releases->get($group->release_id) : null;
            $group->amount = (float) $group->amount;

            return $group;
        });
    }

    /** What the server accepts in one upload, in MB */
    private static function uploadLimit(): ?int
    {
        $bytes = fn ($v) => (int) $v * match (strtolower(substr(trim((string) $v), -1))) { 'g' => 1024 ** 3, 'm' => 1024 ** 2, 'k' => 1024, default => 1 };
        $limits = array_filter([$bytes(ini_get('upload_max_filesize')), $bytes(ini_get('post_max_size'))]);

        return $limits ? (int) floor(min($limits) / 1024 ** 2) : null;
    }
}
