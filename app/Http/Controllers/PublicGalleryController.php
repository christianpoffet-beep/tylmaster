<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\PhotoFolder;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class PublicGalleryController extends Controller
{
    public function showPhoto(string $path)
    {
        $segments = explode('/', $path);
        $photoSlug = array_pop($segments);

        // Walk the folder hierarchy
        $folder = null;
        foreach ($segments as $slug) {
            $folder = PhotoFolder::where('slug', $slug)
                ->where('parent_id', $folder?->id)
                ->firstOrFail();
        }

        $photo = Photo::where('public_slug', $photoSlug)
            ->where('photo_folder_id', $folder->id)
            ->firstOrFail();

        $photo->load('folder');

        return view('public.photo', compact('photo'));
    }

    public function showGallery(string $token)
    {
        $folder = PhotoFolder::where('share_token', $token)->firstOrFail();
        $folder->load(['photos.folder', 'children' => function ($q) {
            $q->withCount('photos');
        }]);

        return view('public.gallery', compact('folder'));
    }

    public function downloadPhoto(string $path)
    {
        $segments = explode('/', $path);
        $photoSlug = array_pop($segments);

        $folder = null;
        foreach ($segments as $slug) {
            $folder = PhotoFolder::where('slug', $slug)
                ->where('parent_id', $folder?->id)
                ->firstOrFail();
        }

        $photo = Photo::where('public_slug', $photoSlug)
            ->where('photo_folder_id', $folder->id)
            ->firstOrFail();

        return Storage::disk('public')->download($photo->file_path, $photo->original_name);
    }

    public function downloadFolder(string $token)
    {
        $folder = PhotoFolder::where('share_token', $token)->firstOrFail();

        // Determine which folder IDs to include
        $folderIdsParam = request('folders');
        if ($folderIdsParam) {
            $requestedIds = array_map('intval', explode(',', $folderIdsParam));
            // Only allow the main folder and its direct children
            $allowedIds = collect([$folder->id])
                ->merge($folder->children()->pluck('id'))
                ->toArray();
            $folderIds = array_intersect($requestedIds, $allowedIds);
        } else {
            $folderIds = [$folder->id];
        }

        if (empty($folderIds)) {
            return back()->with('error', 'Keine Ordner ausgewählt.');
        }

        // Collect photos from all selected folders
        $folders = PhotoFolder::whereIn('id', $folderIds)->with('photos')->get();
        $hasMultipleFolders = $folders->count() > 1;

        $totalPhotos = $folders->sum(fn ($f) => $f->photos->count());
        if ($totalPhotos === 0) {
            return back()->with('error', 'Keine Fotos zum Herunterladen.');
        }

        $zipName = $folder->slug . '.zip';
        $zipPath = storage_path('app/temp/' . $zipName);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($folders as $f) {
            foreach ($f->photos as $photo) {
                $filePath = Storage::disk('public')->path($photo->file_path);
                if (file_exists($filePath)) {
                    // Use subfolder in ZIP when multiple folders selected
                    $zipEntryName = $hasMultipleFolders
                        ? $f->name . '/' . $photo->original_name
                        : $photo->original_name;
                    $zip->addFile($filePath, $zipEntryName);
                }
            }
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }
}
