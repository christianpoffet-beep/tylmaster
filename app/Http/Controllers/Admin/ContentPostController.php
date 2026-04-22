<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\ArtworkLogo;
use App\Models\ContentPost;
use App\Models\ContentPostImage;
use App\Models\Photo;
use App\Models\PhotoFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContentPostController extends Controller
{
    public function index(Request $request)
    {
        $query = ContentPost::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('caption', 'like', "%{$search}%")
                  ->orWhere('hashtags', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($platform = $request->input('platform')) {
            $query->where('platform', $platform);
        }

        $sortCol = $request->input('sort', 'scheduled_at');
        $sortDir = $request->input('dir', 'desc');
        $allowed = ['title', 'platform', 'status', 'scheduled_at', 'created_at'];
        if (!in_array($sortCol, $allowed)) $sortCol = 'scheduled_at';

        $query->orderByRaw("CASE WHEN scheduled_at IS NULL THEN 1 ELSE 0 END")
              ->orderBy($sortCol, $sortDir);

        $posts = $query->with('images')->paginate(20)->withQueryString();

        return view('admin.content-posts.index', compact('posts'));
    }

    public function create()
    {
        return view('admin.content-posts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:' . implode(',', array_keys(ContentPost::PLATFORMS)),
            'title' => 'nullable|string|max:255',
            'caption' => 'required|string',
            'hashtags' => 'nullable|string',
            'status' => 'required|in:' . implode(',', array_keys(ContentPost::STATUSES)),
            'scheduled_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'uploaded_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        unset($validated['uploaded_images']);
        $post = ContentPost::create($validated);

        $this->syncImages($post, $request);

        return redirect()->route('admin.content-posts.index')
            ->with('success', 'Beitrag erstellt.');
    }

    public function show(ContentPost $contentPost)
    {
        $contentPost->load('images');
        return view('admin.content-posts.show', compact('contentPost'));
    }

    public function edit(ContentPost $contentPost)
    {
        $contentPost->load('images');
        return view('admin.content-posts.edit', compact('contentPost'));
    }

    public function update(Request $request, ContentPost $contentPost)
    {
        $validated = $request->validate([
            'platform' => 'required|in:' . implode(',', array_keys(ContentPost::PLATFORMS)),
            'title' => 'nullable|string|max:255',
            'caption' => 'required|string',
            'hashtags' => 'nullable|string',
            'status' => 'required|in:' . implode(',', array_keys(ContentPost::STATUSES)),
            'scheduled_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'uploaded_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        unset($validated['uploaded_images']);
        $contentPost->update($validated);

        $this->syncImages($contentPost, $request);

        return redirect()->route('admin.content-posts.show', $contentPost)
            ->with('success', 'Beitrag aktualisiert.');
    }

    public function destroy(ContentPost $contentPost)
    {
        foreach ($contentPost->images as $img) {
            if ($img->source_type === 'upload' && $img->file_path) {
                Storage::disk('public')->delete($img->file_path);
            }
        }

        $contentPost->delete();

        return redirect()->route('admin.content-posts.index')
            ->with('success', 'Beitrag gelöscht.');
    }

    public function duplicate(ContentPost $contentPost)
    {
        $copy = $contentPost->replicate(['published_at', 'share_token']);
        $copy->status = 'draft';
        $copy->title = ($copy->title ? $copy->title . ' (Kopie)' : 'Kopie');
        $copy->save();

        foreach ($contentPost->images as $img) {
            $newImg = $img->replicate();
            $newImg->content_post_id = $copy->id;

            if ($img->source_type === 'upload' && $img->file_path) {
                $ext = pathinfo($img->file_path, PATHINFO_EXTENSION);
                $newPath = 'content-posts/' . uniqid() . '.' . $ext;
                Storage::disk('public')->copy($img->file_path, $newPath);
                $newImg->file_path = $newPath;
            }

            $newImg->save();
        }

        return redirect()->route('admin.content-posts.edit', $copy)
            ->with('success', 'Beitrag dupliziert.');
    }

    public function markPublished(ContentPost $contentPost)
    {
        $contentPost->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return redirect()->route('admin.content-posts.show', $contentPost)
            ->with('success', 'Beitrag als veröffentlicht markiert.');
    }

    public function share(ContentPost $contentPost)
    {
        $contentPost->generateShareToken();

        return redirect()->route('admin.content-posts.show', $contentPost)
            ->with('success', 'Teilen-Link erstellt.');
    }

    public function unshare(ContentPost $contentPost)
    {
        $contentPost->revokeShareToken();

        return redirect()->route('admin.content-posts.show', $contentPost)
            ->with('success', 'Teilen-Link deaktiviert.');
    }

    public function removeImage(ContentPost $contentPost, ContentPostImage $image)
    {
        if ($image->content_post_id !== $contentPost->id) {
            abort(404);
        }

        if ($image->source_type === 'upload' && $image->file_path) {
            Storage::disk('public')->delete($image->file_path);
        }

        $image->delete();

        return response()->json(['success' => true]);
    }

    public function reorderImages(Request $request, ContentPost $contentPost)
    {
        $order = $request->validate(['order' => 'required|array'])['order'];

        foreach ($order as $i => $id) {
            ContentPostImage::where('id', $id)
                ->where('content_post_id', $contentPost->id)
                ->update(['sort_order' => $i]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * JSON API: Browse photos and artworks for image picker.
     */
    public function imageBrowser(Request $request)
    {
        $tab = $request->input('tab', 'photos');
        $folderId = $request->input('folder_id');

        if ($tab === 'photos') {
            if ($folderId) {
                $folder = PhotoFolder::findOrFail($folderId);
                $subfolders = $folder->children()->orderBy('name')->get(['id', 'name']);
                $photos = $folder->photos()->get()->map(fn ($p) => [
                    'id' => $p->id,
                    'type' => 'photo',
                    'title' => $p->display_title,
                    'thumb' => $p->photo_url,
                ]);
                return response()->json([
                    'folder' => ['id' => $folder->id, 'name' => $folder->name, 'parent_id' => $folder->parent_id],
                    'subfolders' => $subfolders,
                    'items' => $photos,
                ]);
            }

            $rootFolders = PhotoFolder::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
            return response()->json([
                'folder' => null,
                'subfolders' => $rootFolders,
                'items' => [],
            ]);
        }

        if ($tab === 'artworks') {
            $artworks = Artwork::with(['images', 'logos'])->orderBy('title')->get()->map(fn ($a) => [
                'id' => $a->id,
                'type' => 'artwork',
                'title' => $a->title,
                'thumb' => $a->artwork_url,
                'logos' => $a->logos->map(fn ($l) => [
                    'id' => $l->id,
                    'type' => 'artwork_logo',
                    'title' => $l->original_name . ($l->comment ? ' – ' . $l->comment : ''),
                    'thumb' => $l->url,
                ]),
            ]);

            return response()->json(['items' => $artworks]);
        }

        return response()->json([]);
    }

    private function syncImages(ContentPost $post, Request $request): void
    {
        // Existing images to keep (JSON array of {id, sort_order})
        $keepImages = json_decode($request->input('keep_images', '[]'), true) ?: [];
        $keepIds = collect($keepImages)->pluck('id')->all();

        // Remove images not in keep list
        foreach ($post->images as $img) {
            if (!in_array($img->id, $keepIds)) {
                if ($img->source_type === 'upload' && $img->file_path) {
                    Storage::disk('public')->delete($img->file_path);
                }
                $img->delete();
            }
        }

        // Update sort order of kept images
        foreach ($keepImages as $item) {
            ContentPostImage::where('id', $item['id'])
                ->where('content_post_id', $post->id)
                ->update(['sort_order' => $item['sort_order'] ?? 0]);
        }

        $nextSort = count($keepImages);

        // Add selected library images (JSON: [{type, id, thumb, title}])
        $selectedImages = json_decode($request->input('selected_images', '[]'), true) ?: [];
        foreach ($selectedImages as $sel) {
            ContentPostImage::create([
                'content_post_id' => $post->id,
                'source_type' => $sel['type'],
                'source_id' => $sel['id'],
                'sort_order' => $nextSort++,
            ]);
        }

        // Handle uploaded files
        if ($request->hasFile('uploaded_images')) {
            foreach ($request->file('uploaded_images') as $file) {
                $path = $file->store('content-posts', 'public');
                ContentPostImage::create([
                    'content_post_id' => $post->id,
                    'source_type' => 'upload',
                    'file_path' => $path,
                    'sort_order' => $nextSort++,
                ]);
            }
        }
    }
}
