<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use App\Models\Contact;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Release::withCount('tracks');

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('upc', 'like', "%{$search}%");
        }

        $sortField = $request->input('sort', 'release_date');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['title', 'upc', 'release_date', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'release_date';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $releases = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();
        return view('admin.music.releases.index', compact('releases'));
    }

    public function create()
    {
        $contacts = Contact::where('type', 'artist')->orderBy('last_name')->get();
        return view('admin.music.releases.create', compact('contacts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'upc' => 'nullable|string|max:20',
            'release_date' => 'nullable|date',
            'label' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = $request->file('cover_image')->store('covers', 'public');
        }

        $release = Release::create($validated);

        if ($request->has('artists')) {
            foreach ($request->input('artists') as $contactId) {
                $release->contacts()->attach($contactId, ['role' => 'artist']);
            }
        }

        return redirect()->route('admin.releases.show', $release)->with('success', 'Release erstellt.');
    }

    public function show(Release $release)
    {
        $release->load(['tracks', 'contacts']);
        return view('admin.music.releases.show', compact('release'));
    }

    public function edit(Release $release)
    {
        $contacts = Contact::where('type', 'artist')->orderBy('last_name')->get();
        $release->load('contacts');
        return view('admin.music.releases.edit', compact('release', 'contacts'));
    }

    public function update(Request $request, Release $release)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'upc' => 'nullable|string|max:20',
            'release_date' => 'nullable|date',
            'label' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = $request->file('cover_image')->store('covers', 'public');
        }

        $release->update($validated);

        if ($request->has('artists')) {
            $release->contacts()->sync(
                collect($request->input('artists'))->mapWithKeys(fn($id) => [$id => ['role' => 'artist']])->toArray()
            );
        }

        return redirect()->route('admin.releases.show', $release)->with('success', 'Release aktualisiert.');
    }

    public function destroy(Release $release)
    {
        $release->delete();
        return redirect()->route('admin.releases.index')->with('success', 'Release gelöscht.');
    }
}
