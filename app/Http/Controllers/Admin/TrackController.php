<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Track;
use App\Models\Release;
use App\Models\Contact;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    public function index(Request $request)
    {
        $query = Track::with(['release', 'contacts']);

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('isrc', 'like', "%{$search}%");
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['title', 'isrc', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $tracks = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();
        return view('admin.music.tracks.index', compact('tracks'));
    }

    public function create()
    {
        $releases = Release::orderBy('title')->get();
        $contacts = Contact::where('type', 'artist')->orderBy('last_name')->get();
        return view('admin.music.tracks.create', compact('releases', 'contacts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'isrc' => 'nullable|string|max:20',
            'genre' => 'nullable|string|max:100',
            'duration_seconds' => 'nullable|integer|min:0',
            'status' => 'required|in:draft,released,archived',
            'release_id' => 'nullable|exists:releases,id',
        ]);

        if ($request->hasFile('audio_file')) {
            $validated['audio_file_path'] = $request->file('audio_file')->store('tracks', 'public');
        }

        $track = Track::create($validated);

        if ($request->has('artists')) {
            foreach ($request->input('artists') as $contactId) {
                $track->contacts()->attach($contactId, ['role' => 'artist']);
            }
        }

        return redirect()->route('admin.tracks.show', $track)->with('success', 'Track erstellt.');
    }

    public function show(Track $track)
    {
        $track->load(['release', 'contacts', 'projects']);
        return view('admin.music.tracks.show', compact('track'));
    }

    public function edit(Track $track)
    {
        $releases = Release::orderBy('title')->get();
        $contacts = Contact::where('type', 'artist')->orderBy('last_name')->get();
        $track->load('contacts');
        return view('admin.music.tracks.edit', compact('track', 'releases', 'contacts'));
    }

    public function update(Request $request, Track $track)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'isrc' => 'nullable|string|max:20',
            'genre' => 'nullable|string|max:100',
            'duration_seconds' => 'nullable|integer|min:0',
            'status' => 'required|in:draft,released,archived',
            'release_id' => 'nullable|exists:releases,id',
        ]);

        if ($request->hasFile('audio_file')) {
            $validated['audio_file_path'] = $request->file('audio_file')->store('tracks', 'public');
        }

        $track->update($validated);

        if ($request->has('artists')) {
            $track->contacts()->sync(
                collect($request->input('artists'))->mapWithKeys(fn($id) => [$id => ['role' => 'artist']])->toArray()
            );
        }

        return redirect()->route('admin.tracks.show', $track)->with('success', 'Track aktualisiert.');
    }

    public function destroy(Track $track)
    {
        $track->delete();
        return redirect()->route('admin.tracks.index')->with('success', 'Track gelöscht.');
    }
}
