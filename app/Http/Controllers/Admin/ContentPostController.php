<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentPost;
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
        $sortDir = $request->input('direction', 'desc');
        $allowed = ['title', 'platform', 'status', 'scheduled_at', 'created_at'];
        if (!in_array($sortCol, $allowed)) $sortCol = 'scheduled_at';

        $query->orderByRaw("CASE WHEN scheduled_at IS NULL THEN 1 ELSE 0 END")
              ->orderBy($sortCol, $sortDir);

        $posts = $query->paginate(20)->withQueryString();

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
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'status' => 'required|in:' . implode(',', array_keys(ContentPost::STATUSES)),
            'scheduled_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('content-posts', 'public');
        }

        ContentPost::create($validated);

        return redirect()->route('admin.content-posts.index')
            ->with('success', 'Beitrag erstellt.');
    }

    public function show(ContentPost $contentPost)
    {
        return view('admin.content-posts.show', compact('contentPost'));
    }

    public function edit(ContentPost $contentPost)
    {
        return view('admin.content-posts.edit', compact('contentPost'));
    }

    public function update(Request $request, ContentPost $contentPost)
    {
        $validated = $request->validate([
            'platform' => 'required|in:' . implode(',', array_keys(ContentPost::PLATFORMS)),
            'title' => 'nullable|string|max:255',
            'caption' => 'required|string',
            'hashtags' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'status' => 'required|in:' . implode(',', array_keys(ContentPost::STATUSES)),
            'scheduled_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            if ($contentPost->image) {
                Storage::disk('public')->delete($contentPost->image);
            }
            $validated['image'] = $request->file('image')->store('content-posts', 'public');
        }

        $contentPost->update($validated);

        return redirect()->route('admin.content-posts.show', $contentPost)
            ->with('success', 'Beitrag aktualisiert.');
    }

    public function destroy(ContentPost $contentPost)
    {
        if ($contentPost->image) {
            Storage::disk('public')->delete($contentPost->image);
        }

        $contentPost->delete();

        return redirect()->route('admin.content-posts.index')
            ->with('success', 'Beitrag gelöscht.');
    }

    public function duplicate(ContentPost $contentPost)
    {
        $copy = $contentPost->replicate(['published_at']);
        $copy->status = 'draft';
        $copy->title = ($copy->title ? $copy->title . ' (Kopie)' : 'Kopie');

        if ($contentPost->image) {
            $ext = pathinfo($contentPost->image, PATHINFO_EXTENSION);
            $newPath = 'content-posts/' . uniqid() . '.' . $ext;
            Storage::disk('public')->copy($contentPost->image, $newPath);
            $copy->image = $newPath;
        }

        $copy->save();

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
}
