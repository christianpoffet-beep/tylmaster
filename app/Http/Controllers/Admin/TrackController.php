<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Track;
use App\Models\Release;
use App\Models\Genre;
use App\Models\Project;
use App\Models\Contract;
use Illuminate\Http\Request;

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
                  ->orWhere('producers', 'like', "%{$search}%");
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

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['title', 'isrc', 'status', 'created_at', 'bpm', 'genre'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $tracks = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();
        $releases = Release::orderBy('title')->get();
        $genres = Genre::orderBy('name')->get();
        return view('admin.music.tracks.index', compact('tracks', 'releases', 'genres'));
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
            'audio_file' => 'nullable|file|mimes:mp3,wav,flac,aac,m4a,ogg|max:51200',
        ]);

        // Clean ISRC: strip dashes
        if (!empty($validated['isrc'])) {
            $validated['isrc'] = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $validated['isrc']));
        }

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

        // Sync bands + labels + publishers as organizations
        $orgIds = array_merge(
            $request->input('band_ids', []),
            $request->input('label_ids', []),
            $request->input('publisher_ids', [])
        );
        $track->organizations()->sync($orgIds);

        // Sync credits (contacts with roles)
        $this->syncCredits($track, $request);

        // Sync releases (N:M)
        $this->syncReleases($track, $request);

        // Sync projects & contracts
        $track->projects()->sync($request->input('project_ids', []));
        $track->contracts()->sync($request->input('contract_ids', []));

        return redirect()->route('admin.tracks.show', $track)->with('success', 'Track erstellt.');
    }

    public function copyMetadata(Track $track)
    {
        $track->load(['contacts', 'organizations']);
        return response()->json([
            'genre' => $track->genre,
            'language' => $track->language,
            'bpm' => $track->bpm,
            'musical_key' => $track->musical_key,
            'band_ids' => $track->organizations->where('type', 'band')->pluck('id')->values(),
            'label_ids' => $track->organizations->where('type', 'label')->pluck('id')->values(),
            'publisher_ids' => $track->organizations->where('type', 'publishing')->pluck('id')->values(),
            'credits' => $track->contacts->map(fn($c) => [
                'contact_id' => $c->id,
                'name' => $c->full_name,
                'role' => $c->pivot->role,
                'instrument' => $c->pivot->instrument,
            ])->values(),
        ]);
    }

    public function show(Track $track)
    {
        $track->load(['releases', 'contacts', 'projects', 'contracts', 'organizations', 'documents']);
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
            'audio_file' => 'nullable|file|mimes:mp3,wav,flac,aac,m4a,ogg|max:51200',
        ]);

        if (!empty($validated['isrc'])) {
            $validated['isrc'] = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $validated['isrc']));
        }

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

        // Sync bands + labels + publishers as organizations
        $orgIds = array_merge(
            $request->input('band_ids', []),
            $request->input('label_ids', []),
            $request->input('publisher_ids', [])
        );
        $track->organizations()->sync($orgIds);

        // Sync credits (contacts with roles)
        $this->syncCredits($track, $request);

        // Sync releases (N:M)
        $this->syncReleases($track, $request);

        // Sync projects & contracts
        $track->projects()->sync($request->input('project_ids', []));
        $track->contracts()->sync($request->input('contract_ids', []));

        return redirect()->route('admin.tracks.show', $track)->with('success', 'Track aktualisiert.');
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
        // Delete all existing credits and re-insert (allows same contact with multiple roles)
        $track->contacts()->detach();

        $credits = $request->input('credits', []);
        foreach ($credits as $credit) {
            if (!empty($credit['contact_id']) && !empty($credit['role'])) {
                $pivot = ['role' => $credit['role'], 'instrument' => null];
                if ($credit['role'] === 'instrumentalist' && !empty($credit['instrument'])) {
                    $pivot['instrument'] = $credit['instrument'];
                }
                $track->contacts()->attach($credit['contact_id'], $pivot);
            }
        }
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
}
