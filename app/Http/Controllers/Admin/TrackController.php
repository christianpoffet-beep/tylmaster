<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Track;
use App\Models\TrackAudioFile;
use App\Models\Release;
use App\Models\Genre;
use App\Models\Project;
use App\Models\Contract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TrackController extends Controller
{
    public function index(Request $request)
    {
        $query = Track::with(['releases', 'organizations', 'contacts', 'mainAudioFile']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('isrc', 'like', "%{$search}%")
                  // The ISRC of any version finds its track, typed with or without dashes.
                  ->when(Track::normalizeIsrc($search), fn ($q, $isrc) => $q->orWhereHas('audioFiles', fn ($a) => $a->where('isrc', 'like', "%{$isrc}%")))
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
            ...$this->audioFileRules(),
        ], [
            'recording_years.regex' => 'Bitte ein Jahr (z.B. 2026) oder einen Zeitraum (z.B. 2024 - 2026) erfassen.',
            ...$this->audioFileMessages(),
        ]);

        // Clean ISRC: strip dashes
        if (!empty($validated['isrc'])) {
            $validated['isrc'] = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $validated['isrc']));
        }

        $validated = $this->normalizeTitlesAndRecording($validated, $request);

        $track = Track::create($this->withoutAudioInput($validated));

        $this->syncAudioFiles($track, $request);
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
        $track->load(['releases.artworks.images', 'releases.artworks.logos', 'releases.artworks.credits.creditable', 'contacts', 'projects', 'contracts', 'organizations', 'documents', 'audioFiles']);
        return view('admin.music.tracks.show', compact('track'));
    }

    public function edit(Track $track)
    {
        $releases = Release::orderBy('title')->get();
        $genres = Genre::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $contracts = Contract::orderBy('title')->get();
        $track->load(['contacts', 'releases', 'organizations', 'projects', 'contracts', 'audioFiles']);
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
            ...$this->audioFileRules(),
        ], [
            'recording_years.regex' => 'Bitte ein Jahr (z.B. 2026) oder einen Zeitraum (z.B. 2024 - 2026) erfassen.',
            ...$this->audioFileMessages(),
        ]);

        if (!empty($validated['isrc'])) {
            $validated['isrc'] = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $validated['isrc']));
        }

        $validated = $this->normalizeTitlesAndRecording($validated, $request);

        $track->update($this->withoutAudioInput($validated));

        $this->syncAudioFiles($track, $request);
        $this->syncRelations($track, $request);

        return $this->withDuplicateTitleWarning(
            redirect()->route('admin.tracks.show', $track)->with('success', 'Track aktualisiert.'),
            $track
        );
    }

    /** The main version - what the download icon in the tracklist hands out. */
    public function download(Track $track)
    {
        $audioFile = $track->mainAudioFile;

        if (!$audioFile) {
            abort(404, 'Audiodatei nicht gefunden.');
        }

        return $this->sendAudioFile($audioFile);
    }

    public function downloadAudioFile(Track $track, TrackAudioFile $audioFile)
    {
        if ((int) $audioFile->track_id !== (int) $track->id) {
            abort(404);
        }

        return $this->sendAudioFile($audioFile);
    }

    private function sendAudioFile(TrackAudioFile $audioFile)
    {
        if (!Storage::disk('public')->exists($audioFile->file_path)) {
            abort(404, 'Audiodatei nicht gefunden.');
        }

        return Storage::disk('public')->download($audioFile->file_path, $audioFile->download_name);
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

    private function audioFileRules(): array
    {
        $isrc = ['nullable', 'string', 'max:20', 'regex:' . Track::ISRC_REGEX];

        return [
            'audio_files' => 'nullable|array',
            'audio_files.*.file' => 'nullable|file|mimes:mp3,wav,flac,aac,m4a,ogg|max:307200',
            'audio_files.*.title' => 'nullable|string|max:255',
            'audio_files.*.isrc' => $isrc,
            'audio_files.*.notes' => 'nullable|string|max:2000',
            'existing_audio_files' => 'nullable|array',
            'existing_audio_files.*.title' => 'nullable|string|max:255',
            'existing_audio_files.*.isrc' => $isrc,
            'existing_audio_files.*.notes' => 'nullable|string|max:2000',
            'existing_audio_files.*.delete' => 'nullable|boolean',
            'main_audio_file' => ['nullable', 'string', 'regex:/^(existing|new):[A-Za-z0-9_-]+$/'],
        ];
    }

    private function audioFileMessages(): array
    {
        $isrc = 'Bitte einen gültigen ISRC erfassen (z.B. CH-TYL-26-00001).';

        return [
            'audio_files.*.isrc.regex' => $isrc,
            'existing_audio_files.*.isrc.regex' => $isrc,
            'audio_files.*.file.mimes' => 'Erlaubt sind MP3, WAV, FLAC, AAC, M4A und OGG.',
            'audio_files.*.file.max' => 'Eine Audiodatei darf höchstens 300 MB gross sein.',
            'audio_files.*.file.uploaded' => 'Die Datei kam nicht vollständig an, vermutlich ist sie grösser, als der Server annimmt.',
        ];
    }

    /** The version fields are handled by syncAudioFiles(), not written onto the track. */
    private function withoutAudioInput(array $validated): array
    {
        unset($validated['audio_files'], $validated['existing_audio_files'], $validated['main_audio_file']);

        return $validated;
    }

    /**
     * Existing versions only change in the fields the form actually sent, and
     * only get removed when their delete flag (set by Alpine) says so. A save
     * without the audio section therefore leaves every version as it was.
     */
    private function syncAudioFiles(Track $track, Request $request): void
    {
        $mainBefore = $track->audioFiles()->where('is_main', true)->value('id');

        foreach ((array) $request->input('existing_audio_files', []) as $id => $data) {
            $audioFile = $track->audioFiles()->whereKey($id)->first();
            if (!$audioFile || !is_array($data)) continue;

            if (!empty($data['delete'])) {
                $this->deleteAudioFile($audioFile);
                continue;
            }

            $audioFile->update($this->audioFileFields($data));
        }

        // New uploads, remembered by the row key the form gave them so the
        // main-version radio ("new:<key>") can point at one of them.
        $rows = (array) $request->input('audio_files', []);
        $created = [];
        $sortOrder = (int) $track->audioFiles()->reorder()->max('sort_order');

        foreach (array_keys($rows + (array) $request->file('audio_files', [])) as $key) {
            $upload = $request->file("audio_files.{$key}.file");
            if (!$upload instanceof UploadedFile) continue;

            $created[(string) $key] = $this->storeAudioFile($track, $upload, (array) ($rows[$key] ?? []), ++$sortOrder);
        }

        $this->applyMainSelection($track, $request->input('main_audio_file'), $created);

        // As before with the single file: the duration follows the audio. Now
        // it is the main version's, whenever a different one takes that place.
        $main = $track->audioFiles()->where('is_main', true)->first();
        if ($main && $main->id !== (int) $mainBefore && $main->duration_seconds) {
            $track->update(['duration_seconds' => $main->duration_seconds]);
        }
    }

    /** Title, ISRC and remark - each only if it was submitted. */
    private function audioFileFields(array $data): array
    {
        $fields = [];

        foreach (['title', 'notes'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[$field] = filled($data[$field]) ? trim($data[$field]) : null;
            }
        }

        if (array_key_exists('isrc', $data)) {
            $fields['isrc'] = Track::normalizeIsrc($data['isrc']);
        }

        return $fields;
    }

    private function storeAudioFile(Track $track, UploadedFile $upload, array $data, int $sortOrder): TrackAudioFile
    {
        $ext = strtolower($upload->getClientOriginalExtension());
        $meta = $this->extractAudioMetadata($upload->getRealPath());
        $path = $upload->storeAs('tracks', bin2hex(random_bytes(10)) . '.' . $ext, 'public');

        return $track->audioFiles()->create(array_merge([
            'file_path' => $path,
            'original_name' => $upload->getClientOriginalName(),
            'file_size' => $upload->getSize(),
            'audio_format' => $ext ?: null,
            'is_main' => false,
            'sort_order' => $sortOrder,
        ], $meta, $this->audioFileFields($data)));
    }

    /** "existing:<id>" or "new:<row key>"; anything that does not resolve keeps the current main. */
    private function applyMainSelection(Track $track, ?string $selector, array $created): void
    {
        $chosen = null;

        if ($selector && preg_match('/^(existing|new):(.+)$/', $selector, $m)) {
            $chosen = $m[1] === 'existing'
                ? $track->audioFiles()->whereKey((int) $m[2])->first()
                : ($created[$m[2]] ?? null);
        }

        if ($chosen) {
            $track->audioFiles()->reorder()->where('id', '!=', $chosen->id)->update(['is_main' => false]);
            $chosen->update(['is_main' => true]);
        }

        $this->ensureExactlyOneMain($track);
    }

    /** As soon as a track has audio, exactly one version is the main one. */
    private function ensureExactlyOneMain(Track $track): void
    {
        $byOrder = fn () => $track->audioFiles()->reorder()->orderBy('sort_order')->orderBy('id');

        $mains = $byOrder()->where('is_main', true)->pluck('id');

        if ($mains->count() > 1) {
            $track->audioFiles()->reorder()->whereIn('id', $mains->slice(1)->all())->update(['is_main' => false]);
        } elseif ($mains->isEmpty()) {
            $byOrder()->first()?->update(['is_main' => true]);
        }
    }

    /**
     * Removes the version and its file. Only files this form uploaded itself
     * (under tracks/) are deleted from storage, and only once no other version
     * points at them - an imported submission keeps its own file.
     */
    private function deleteAudioFile(TrackAudioFile $audioFile): void
    {
        $path = $audioFile->file_path;
        $audioFile->delete();

        if (str_starts_with($path, 'tracks/') && !TrackAudioFile::where('file_path', $path)->exists()) {
            Storage::disk('public')->delete($path);
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
            // A WAV reports its container ("riff") as fileformat; the data format is what matters.
            if (!empty($info['audio']['dataformat']) || !empty($info['fileformat'])) {
                $meta['audio_format'] = strtolower($info['audio']['dataformat'] ?? $info['fileformat']);
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
                   ->when(Track::normalizeIsrc($q), fn ($qb, $isrc) => $qb->orWhereHas('audioFiles', fn ($a) => $a->where('isrc', 'like', "%{$isrc}%")))
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
