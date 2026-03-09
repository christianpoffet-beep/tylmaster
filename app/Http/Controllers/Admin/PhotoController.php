<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Photo;
use App\Models\PhotoFolder;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function index(Request $request)
    {
        $query = PhotoFolder::whereNull('parent_id')->withCount(['photos', 'children']);

        if ($search = $request->input('search')) {
            $query = PhotoFolder::withCount(['photos', 'children'])
                ->where('name', 'like', "%{$search}%");
        }

        $folders = $query->orderBy('name')->paginate(24)->withQueryString();

        return view('admin.photos.index', compact('folders'));
    }

    public function createFolder(Request $request)
    {
        $parentFolders = PhotoFolder::orderBy('name')->get();
        $contacts = Contact::orderBy('last_name')->get();
        $organizations = Organization::orderBy('names')->get();
        $projects = Project::orderBy('name')->get();
        $parentId = $request->input('parent_id');

        return view('admin.photos.folders.create', compact('parentFolders', 'contacts', 'organizations', 'projects', 'parentId'));
    }

    public function storeFolder(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:photo_folders,id',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
            'organization_ids' => 'nullable|array',
            'organization_ids.*' => 'exists:organizations,id',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
        ]);

        $folder = PhotoFolder::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $folder->contacts()->sync($request->input('contact_ids', []));
        $folder->organizations()->sync($request->input('organization_ids', []));
        $folder->projects()->sync($request->input('project_ids', []));

        return redirect()->route('admin.photos.folders.show', $folder)->with('success', 'Ordner erstellt.');
    }

    public function showFolder(PhotoFolder $folder)
    {
        $folder->load(['children' => function ($q) {
            $q->withCount('photos');
        }, 'photos', 'contacts', 'organizations', 'projects', 'parent']);

        return view('admin.photos.folders.show', compact('folder'));
    }

    public function editFolder(PhotoFolder $folder)
    {
        $folder->load(['contacts', 'organizations', 'projects']);
        $parentFolders = PhotoFolder::where('id', '!=', $folder->id)->orderBy('name')->get();
        $contacts = Contact::orderBy('last_name')->get();
        $organizations = Organization::orderBy('names')->get();
        $projects = Project::orderBy('name')->get();

        return view('admin.photos.folders.edit', compact('folder', 'parentFolders', 'contacts', 'organizations', 'projects'));
    }

    public function updateFolder(Request $request, PhotoFolder $folder)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:photo_folders,id',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
            'organization_ids' => 'nullable|array',
            'organization_ids.*' => 'exists:organizations,id',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
        ]);

        $folder->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $folder->contacts()->sync($request->input('contact_ids', []));
        $folder->organizations()->sync($request->input('organization_ids', []));
        $folder->projects()->sync($request->input('project_ids', []));

        return redirect()->route('admin.photos.folders.show', $folder)->with('success', 'Ordner aktualisiert.');
    }

    public function destroyFolder(PhotoFolder $folder)
    {
        // Delete all photo files and their document records
        foreach ($folder->photos as $photo) {
            Storage::disk('public')->delete($photo->file_path);
            Document::where('documentable_type', Photo::class)
                ->where('documentable_id', $photo->id)
                ->delete();
        }

        $folder->delete();

        return redirect()->route('admin.photos.index')->with('success', 'Ordner und alle Fotos gelöscht.');
    }

    public function uploadPhotos(Request $request, PhotoFolder $folder)
    {
        $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'file|image|max:51200',
        ]);

        $count = 0;
        foreach ($request->file('photos', []) as $file) {
            $path = $file->store('photos/' . $folder->slug, 'public');

            $photo = $folder->photos()->create([
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'sort_order' => $folder->photos()->count(),
            ]);

            // Create document record
            Document::create([
                'title' => $file->getClientOriginalName(),
                'category' => 'photo',
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'documentable_type' => Photo::class,
                'documentable_id' => $photo->id,
            ]);

            $count++;
        }

        return back()->with('success', $count . ' Foto(s) hochgeladen.');
    }

    public function showPhoto(Photo $photo)
    {
        $photo->load('folder');
        return view('admin.photos.show', compact('photo'));
    }

    public function updatePhoto(Request $request, Photo $photo)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'photo_date' => 'nullable|date',
            'story' => 'nullable|string',
            'info' => 'nullable|string',
            'photographer' => 'nullable|string|max:255',
            'graphic_artist' => 'nullable|string|max:255',
        ]);

        $photo->update($validated);

        // Update document title if photo title changed
        Document::where('documentable_type', Photo::class)
            ->where('documentable_id', $photo->id)
            ->update(['title' => $photo->display_title]);

        return back()->with('success', 'Foto aktualisiert.');
    }

    public function destroyPhoto(Photo $photo)
    {
        $folderId = $photo->photo_folder_id;

        Storage::disk('public')->delete($photo->file_path);

        Document::where('documentable_type', Photo::class)
            ->where('documentable_id', $photo->id)
            ->delete();

        $photo->delete();

        return redirect()->route('admin.photos.folders.show', $folderId)->with('success', 'Foto gelöscht.');
    }

    public function generateShareLink(PhotoFolder $folder)
    {
        $folder->generateShareToken();
        return back()->with('success', 'Share-Link erstellt.');
    }

    public function revokeShareLink(PhotoFolder $folder)
    {
        $folder->revokeShareToken();
        return back()->with('success', 'Share-Link widerrufen.');
    }
}
