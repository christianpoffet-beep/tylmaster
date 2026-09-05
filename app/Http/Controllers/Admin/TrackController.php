<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Track;
use App\Models\Release;
use App\Models\Genre;
use App\Models\Project;
use App\Models\Contract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TrackController extends Controller
{
    public function index(Request $request)
    {
        $query = Track::with(['releases', 'organizations', 'contacts']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('isrc', 'like', "%{$search}%")
                  ->orWhere('version', 'like', "%{$search}%")
                  ->orWhere('composers', 'like', "%{$search}%")
                  ->orWhere('producers', 'like', "%{$search}%")
                  // Alternative titles sit in a JSON column, so the LIKE runs over its raw text.
                  ->orWhereRaw('LOWER(alternative_titles) LIKE ?', ['%' . mb_strtolower($search) . '%']);
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($genre = $request->input('genre')) {
            $query->where('genre', $genre);
        }

        if ($releaseId = $request->input('release')) {
            $query->whereHas('releases', fn ($q) => $q->where('releases.id', $releaseId));
        }

        // Recording years are stored as text ("2024 - 2026"), so which entries
        // cover the wanted year is worked out in PHP before the query filters.
        if ($recordingYear = $request->input('recording_year')) {
            $query->whereIn('recording_years', $this->recordingYearValues()
                ->filter(fn ($value) => in_array((int) $recordingYear, Track::expandRecordingYears($value), true))
                ->all());
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['title', 'isrc', 'status', 'created_at', 'bpm', 'genre', 'recording_years'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $tracks = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();
        $releases = Release::orderBy('title')->get();
        $genres = Genre::orderBy('name')->get();
        $recordingYears = $this->recordingYearValues()
            ->flatMap(fn ($value) => Track::expandRecordingYears($value))
            ->unique()->sortDesc()->values();

        return view('admin.music.tracks.index', compact('tracks', 'releases', 'genres', 'recordingYears'));
    }

    public function create()
    {
        $releases = Release::orderBy('title')->get();
        $genres = Genre::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $contracts = Contract::orderBy('title')->get();
        return view('admin.music.tracks.create', compact('releases', 'genres', 'projects', 'contracts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'version' => 'nullable|string|max:100',
            'alternative_titles' => 'nullable|array',
            'alternative_titles.*' => 'nullable|string|max:255',
            'isrc' => ['nullable', 'string', 'max:20', 'regex:' . Track::ISRC_REGEX],
            'genre' => 'nullable|string|max:100',
            'duration_seconds' => 'nullable|integer|min:0',
            'status' => 'required|in:draft,released,archived',
            'composers' => 'nullable|string|max:1000',
            'producers' => 'nullable|string|max:1000',
            'language' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:5000',
            'bpm' => 'nullable|integer|min:20|max:300',
            'musical_key' => 'nullable|string|in:' . implode(',', Track::MUSICAL_KEYS),
            'recording_location' => 'nullable|string|max:255',
            'recording_years' => ['nullable', 'string', 'max:50', 'regex:' . Track::RECORDING_YEARS_REGEX],
            'audio_file' => 'nullable|file|mimes:mp3,wav,flac,aac,m4a,ogg|max:307200',
        ], [
            'recording_years.regex' => 'Bitte ein Jahr (z.B. 2026) oder einen Zeitraum (z.B. 2024 - 2026) erfassen.',
        ]);

        // Clean ISRC: strip dashes
        if (!empty($validated['isrc'])) {
            $validated['isrc'] = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $validated['isrc']));
        }

        $validated = $this->normalizeTitlesAndRecording($validated, $request);

        if ($request->hasFile('audio_file')) {
            $file = $request->file('audio_file');
            $ext = strtolower($file->getClientOriginalExtension());
            $name = substr(bin2hex(random_bytes(10)), 0, 20) . '.' . $ext;
            $validated['audio_file_path'] = $file->storeAs('tracks', $name, 'public');
            $validated['audio_format'] = $ext;

            // Extract audio metadata via getID3
            $meta = $this->extractAudioMetadata($file->getRealPath());
            $validated = array_merge($validated, $meta);
        }
        unset($validated['audio_file']);

        $track = Track::create($validated);

        $this->syncRelations($track, $request);

        return $this->withDuplicateTitleWarning(
            redirect()->route('admin.tracks.show', $track)->with('success', 'Track erstellt.'),
            $track
        );
    }

    public function copyMetadata(Track $track)
    {
        $track->load(['contacts', 'organizations', 'releases', 'projects', 'contracts']);
        return response()->json([
            'title' => $track->title,
            'version' => $track->version,
            'alternative_titles' => $track->alternative_titles ?? [],
            'status' => $track->status,
            'genre' => $track->genre,
            'duration_seconds' => $track->duration_seconds,
            'language' => $track->language,
            'bpm' => $track->bpm,
            'musical_key' => $track->musical_key,
            'recording_location' => $track->recording_location,
            'recording_years' => $track->recording_years,
            'description' => $track->description,
            'bands' => $track->organizations->where('type', 'band')->map(fn($o) => [
                'id' => $o->id, 'name' => $o->primary_name,
            ])->values(),
            'labels' => $track->organizations->where('type', 'label')->map(fn($o) => [
                'id' => $o->id, 'name' => $o->primary_name,
            ])->values(),
            'publishers' => $track->organizations->where('type', 'publishing')->map(fn($o) => [
                'id' => $o->id, 'name' => $o->primary_name,
            ])->values(),
            'credits' => $track->contacts->map(function ($c) {
                $pivotIpi = $c->pivot->ipi_number ?? null;
                $matched = null;
                if ($pivotIpi) {
                    $matched = collect($c->ipis ?? [])->first(fn ($i) => ($i['number'] ?? null) === $pivotIpi);
                }
                $displayName = ($matched && !empty($matched['name'])) ? $matched['name'] : $c->full_name;
                return [
                    'contact_id' => $c->id,
                    'name' => $displayName,
                    'contact_name' => $c->full_name,
                    'display_name' => $displayName,
                    'role' => $c->pivot->role,
                    'instrument' => $c->pivot->instrument,
                    'ipi_number' => $pivotIpi,
                    'ipi_name' => $matched['name'] ?? null,
                ];
            })->values(),
            'releases' => $track->releases->map(fn($r) => [
                'id' => $r->id, 'title' => $r->title,
                'track_number' => $r->pivot->track_number,
                'disc_number' => $r->pivot->disc_number,
                'role' => $r->pivot->role,
            ])->values(),
            'projects' => $track->projects->map(fn($p) => [
                'id' => $p->id, 'name' => $p->name,
            ])->values(),
            'contracts' => $track->contracts->map(fn($c) => [
                'id' => $c->id, 'title' => $c->title,
            ])->values(),
        ]);
    }

    public function show(Track $track)
    {
        $track->load(['releases.artworks.images', 'releases.artworks.logos', 'releases.artworks.credits.creditable', 'contacts', 'projects', 'contracts', 'organizations', 'documents']);
        return view('admin.music.tracks.show', compact('track'));
    }

    public function edit(Track $track)
    {
        $releases = Release::orderBy('title')->get();
        $genres = Genre::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $contracts = Contract::orderBy('title')->get();
        $track->load(['contacts', 'releases', 'organizations', 'projects', 'contracts']);
        return view('admin.music.tracks.edit', compact('track', 'releases', 'genres', 'projects', 'contracts'));
    }

    public function update(Request $request, Track $track)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'version' => 'nullable|string|max:100',
            'alternative_titles' => 'nullable|array',
            'alternative_titles.*' => 'nullable|string|max:255',
            'isrc' => ['nullable', 'string', 'max:20', 'regex:' . Track::ISRC_REGEX],
            'genre' => 'nullable|string|max:100',
            'duration_seconds' => 'nullable|integer|min:0',
            'status' => 'required|in:draft,released,archived',
            'composers' => 'nullable|string|max:1000',
            'producers' => 'nullable|string|max:1000',
            'language' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:5000',
            'bpm' => 'nullable|integer|min:20|max:300',
            'musical_key' => 'nullable|string|in:' . implode(',', Track::MUSICAL_KEYS),
            'recording_location' => 'nullable|string|max:255',
            'recording_years' => ['nullable', 'string', 'max:50', 'regex:' . Track::RECORDING_YEARS_REGEX],
            'audio_file' => 'nullable|file|mimes:mp3,wav,flac,aac,m4a,ogg|max:307200',
        ], [
            'recording_years.regex' => 'Bitte ein Jahr (z.B. 2026) oder einen Zeitraum (z.B. 2024 - 2026) erfassen.',
        ]);

        if (!empty($validated['isrc'])) {
            $validated['isrc'] = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $validated['isrc']));
        }

        $validated = $this->normalizeTitlesAndRecording($validated, $request);

        if ($request->hasFile('audio_file')) {
            $file = $request->file('audio_file');
            $ext = strtolower($file->getClientOriginalExtension());
            $name = substr(bin2hex(random_bytes(10)), 0, 20) . '.' . $ext;
            $validated['audio_file_path'] = $file->storeAs('tracks', $name, 'public');
            $validated['audio_format'] = $ext;

            // Extract audio metadata via getID3
            $meta = $this->extractAudioMetadata($file->getRealPath());
            $validated = array_merge($validated, $meta);
        }
        unset($validated['audio_file']);

        $track->update($validated);

        $this->syncRelations($track, $request);

        return $this->withDuplicateTitleWarning(
            redirect()->route('admin.tracks.show', $track)->with('success', 'Track aktualisiert.'),
            $track
        );
    }

    public function download(Track $track)
    {
        if (!$track->audio_file_path || !Storage::disk('public')->exists($track->audio_file_path)) {
            abort(404, 'Audiodatei nicht gefunden.');
        }

        $extension = pathinfo($track->audio_file_path, PATHINFO_EXTENSION);
        $filename = Str::slug($track->display_title) . '.' . $extension;

        return Storage::disk('public')->download($track->audio_file_path, $filename);
    }

    public function destroy(Track $track)
    {
        $track->delete();
        return redirect()->route('admin.tracks.index')->with('success', 'Track gelöscht.');
    }

    public function bulkUpdate(Request $request)
    {
        $ids = $request->input('ids', []);
        $action = $request->input('action');
        $value = $request->input('value');

        if (empty($ids) || !$action) {
            return back()->with('error', 'Keine Tracks ausgewählt oder keine Aktion gewählt.');
        }

        $tracks = Track::whereIn('id', $ids);

        switch ($action) {
            case 'status':
                if (in_array($value, ['draft', 'released', 'archived'])) {
                    $tracks->update(['status' => $value]);
                }
                break;
            case 'genre':
                $tracks->update(['genre' => $value]);
                break;
            case 'delete':
                $tracks->delete();
                return back()->with('success', count($ids) . ' Tracks gelöscht.');
        }

        return back()->with('success', count($ids) . ' Tracks aktualisiert.');
    }

    /**
     * All of these form sections render their inputs through Alpine. If Alpine
     * never ran - a JS error, or the form submitted before it hydrated - the
     * fields are simply absent from the request, and an unguarded sync() would
     * read that as "the user removed everything" and wipe the links.
     *
     * Each section therefore submits a marker that Alpine itself fills in. No
     * marker means the section did not report in, and its relation is left
     * untouched.
     */
    private function syncRelations(Track $track, Request $request): void
    {
        // Bands, labels and publishers share one relation but three sections
        $orgMarkers = ['band_ids_submitted', 'label_ids_submitted', 'publisher_ids_submitted'];
        if (collect($orgMarkers)->contains(fn ($m) => $request->filled($m))) {
            $track->organizations()->sync(array_merge(
                $request->input('band_ids', []),
                $request->input('label_ids', []),
                $request->input('publisher_ids', [])
            ));
        }

        if ($request->filled('credits_submitted')) {
            $this->syncCredits($track, $request);
        }

        if ($request->filled('release_ids_submitted')) {
            $this->syncReleases($track, $request);
        }

        if ($request->filled('project_ids_submitted')) {
            $track->projects()->sync($request->input('project_ids', []));
        }

        if ($request->filled('contract_ids_submitted')) {
            $track->contracts()->sync($request->input('contract_ids', []));
        }
    }

    private function syncReleases(Track $track, Request $request): void
    {
        $releaseIds = $request->input('release_ids', []);
        $trackNumbers = $request->input('release_track_numbers', []);
        $discNumbers = $request->input('release_disc_numbers', []);
        $roles = $request->input('release_roles', []);

        $syncData = [];
        foreach ($releaseIds as $i => $releaseId) {
            if (!$releaseId) continue;
            $syncData[$releaseId] = [
                'track_number' => $trackNumbers[$i] ?? null,
                'disc_number' => $discNumbers[$i] ?? 1,
                'role' => $roles[$i] ?? 'main',
            ];
        }

        $track->releases()->sync($syncData);
    }

    private function syncCredits(Track $track, Request $request): void
    {
        // Delete all existing credits and re-insert (allows same contact with multiple roles / IPIs)
        $track->contacts()->detach();

        $credits = $request->input('credits', []);
        foreach ($credits as $credit) {
            if (!empty($credit['contact_id']) && !empty($credit['role'])) {
                $pivot = [
                    'role' => $credit['role'],
                    'instrument' => null,
                    'ipi_number' => !empty($credit['ipi_number']) ? $credit['ipi_number'] : null,
                ];
                if ($credit['role'] === 'instrumentalist' && !empty($credit['instrument'])) {
                    $pivot['instrument'] = $credit['instrument'];
                }
                $track->contacts()->attach($credit['contact_id'], $pivot);
            }
        }
    }

    /**
     * The alternative titles come from an Alpine repeater, so the same guard as
     * the relation sections applies: without the marker the section never
     * rendered and the stored titles must stay untouched. With the marker an
     * empty list really does mean "all removed".
     */
    private function normalizeTitlesAndRecording(array $validated, Request $request): array
    {
        if ($request->filled('alternative_titles_submitted')) {
            $validated['alternative_titles'] = $this->cleanAlternativeTitles($request->input('alternative_titles', []));
        } else {
            unset($validated['alternative_titles']);
        }

        if (!empty($validated['recording_years'])) {
            $validated['recording_years'] = preg_replace('/\s+/', ' ', trim($validated['recording_years']));
        }

        return $validated;
    }

    /** Every recording-years entry in use, for the filter dropdown and the filter itself. */
    private function recordingYearValues(): \Illuminate\Support\Collection
    {
        return Track::query()->whereNotNull('recording_years')->distinct()->pluck('recording_years');
    }

    /**
     * A doublette in the catalogue usually shows up as an alternative title that
     * another track already carries as its own title. Worth pointing out - but
     * only as a hint, the save itself goes through.
     */
    private function withDuplicateTitleWarning(RedirectResponse $redirect, Track $track): RedirectResponse
    {
        $needles = collect($track->alternative_titles ?? [])
            ->map(fn ($title) => mb_strtolower(trim($title)))
            ->filter()
            ->all();

        if (!$needles) {
            return $redirect;
        }

        $clashes = Track::where('id', '!=', $track->id)
            ->where(function ($q) use ($needles) {
                foreach ($needles as $needle) {
                    $q->orWhereRaw('LOWER(title) = ?', [$needle]);
                }
            })
            ->pluck('title');

        if ($clashes->isEmpty()) {
            return $redirect;
        }

        return $redirect->with('warning', $clashes->count() === 1
            ? 'Achtung: Der Track "' . $clashes->first() . '" trägt diesen Titel bereits. Allenfalls eine Doublette?'
            : 'Achtung: Die Tracks "' . $clashes->join('", "') . '" tragen diese Titel bereits. Allenfalls Doubletten?');
    }

    /** Blank rows and duplicates are dropped so the stored JSON stays a clean list. */
    private function cleanAlternativeTitles(array $titles): array
    {
        $titles = array_filter(
            array_map(fn ($title) => trim((string) $title), $titles),
            fn ($title) => $title !== ''
        );

        return array_values(array_unique($titles));
    }

    private function extractAudioMetadata(string $filePath): array
    {
        $meta = [];

        try {
            $getID3 = new \getID3();
            $info = $getID3->analyze($filePath);

            if (!empty($info['playtime_seconds'])) {
                $meta['duration_seconds'] = (int) round($info['playtime_seconds']);
            }
            if (!empty($info['audio']['bitrate'])) {
                $meta['bitrate'] = (int) round($info['audio']['bitrate'] / 1000); // kbps
            }
            if (!empty($info['audio']['sample_rate'])) {
                $meta['sample_rate'] = (int) $info['audio']['sample_rate'];
            }
            if (!empty($info['audio']['channels'])) {
                $meta['channels'] = (int) $info['audio']['channels'];
            }
            if (!empty($info['fileformat'])) {
                $meta['audio_format'] = strtolower($info['fileformat']);
            }
        } catch (\Exception $e) {
            // Silently fail — metadata is optional
        }

        return $meta;
    }

    public function search(Request $request)
    {
        $query = Track::with('organizations');

        if ($q = $request->input('q')) {
            $query->where(function ($qb) use ($q) {
                $qb->where('title', 'like', "%{$q}%")
                   ->orWhere('isrc', 'like', "%{$q}%")
                   // Alternative titles sit in a JSON column, so the LIKE runs over its raw text.
                   ->orWhereRaw('LOWER(alternative_titles) LIKE ?', ['%' . mb_strtolower($q) . '%']);
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $results = $query->orderBy('title')->limit(50)->get()->map(fn ($t) => [
            'id' => $t->id,
            'title' => $t->display_title,
            'alt' => $t->alternative_titles_list,
            'artist' => $t->organizations->where('type', 'band')->pluck('primary_name')->join(', '),
            'isrc' => $t->isrc_formatted,
            'status' => $t->status,
        ]);

        return response()->json($results);
    }
}
