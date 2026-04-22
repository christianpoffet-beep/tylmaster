<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\ArtworkCredit;
use App\Models\ArtworkImage;
use App\Models\ArtworkLogo;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArtworkController extends Controller
{
    public function index(Request $request)
    {
        $query = Artwork::with(['images', 'logos'])->withCount(['images', 'logos']);

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['title', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $artworks = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();

        return view('admin.artworks.index', compact('artworks'));
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get();
        return view('admin.artworks.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateArtworkRequest($request);

        $artwork = Artwork::create([
            'title' => $validated['title'],
            'yoc' => $validated['yoc'] ?? null,
        ]);

        $artwork->projects()->sync($request->input('project_ids', []));
        $this->syncCredits($artwork, $request->input('credits', []));

        $dpiWarnings = [];
        $this->storeImages($request, $artwork, $dpiWarnings);
        $this->ensureExactlyOnePrimaryImage($artwork);
        $this->syncArtworkDocument($artwork);

        $this->storeLogos($request, $artwork);
        $this->ensureExactlyOnePrimaryLogo($artwork);

        $redirect = redirect()->route('admin.artworks.show', $artwork)->with('success', 'Artwork erstellt.');

        if (!empty($dpiWarnings)) {
            $redirect = $redirect->with('warning', implode(' ', $dpiWarnings));
        }

        return $redirect;
    }

    public function show(Artwork $artwork)
    {
        $artwork->load(['images', 'logos', 'projects', 'credits.creditable']);
        return view('admin.artworks.show', compact('artwork'));
    }

    public function edit(Artwork $artwork)
    {
        $artwork->load(['images', 'logos', 'projects', 'credits.creditable']);
        $projects = Project::orderBy('name')->get();
        return view('admin.artworks.edit', compact('artwork', 'projects'));
    }

    public function update(Request $request, Artwork $artwork)
    {
        $validated = $this->validateArtworkRequest($request);

        $artwork->update([
            'title' => $validated['title'],
            'yoc' => $validated['yoc'] ?? null,
        ]);

        $artwork->projects()->sync($request->input('project_ids', []));
        $this->syncCredits($artwork, $request->input('credits', []));

        $this->updateExistingImages($artwork, $request->input('existing_images', []));

        $dpiWarnings = [];
        $this->storeImages($request, $artwork, $dpiWarnings);

        $this->applyPrimarySelection($artwork, $request->input('primary_image_selector'));
        $this->ensureExactlyOnePrimaryImage($artwork);
        $this->syncArtworkDocument($artwork);

        $this->updateExistingLogos($artwork, $request->input('existing_logos', []));
        $this->storeLogos($request, $artwork);
        $this->applyPrimaryLogoSelection($artwork, $request->input('primary_logo_selector'));
        $this->ensureExactlyOnePrimaryLogo($artwork);

        $redirect = redirect()->route('admin.artworks.show', $artwork)->with('success', 'Artwork aktualisiert.');

        if (!empty($dpiWarnings)) {
            $redirect = $redirect->with('warning', implode(' ', $dpiWarnings));
        }

        return $redirect;
    }

    public function destroy(Artwork $artwork)
    {
        // Delete all image files
        foreach ($artwork->images as $image) {
            if ($image->file_path) {
                Storage::disk('public')->delete($image->file_path);
            }
        }

        // Legacy artwork file (if still present)
        if ($artwork->getRawOriginal('artwork_path')) {
            Storage::disk('public')->delete($artwork->getRawOriginal('artwork_path'));
        }

        // Delete logo files and their documents
        foreach ($artwork->logos as $logo) {
            Storage::disk('public')->delete($logo->file_path);
            Document::where('documentable_type', ArtworkLogo::class)
                ->where('documentable_id', $logo->id)
                ->delete();
        }

        // Delete artwork document
        Document::where('documentable_type', Artwork::class)
            ->where('documentable_id', $artwork->id)
            ->delete();

        $artwork->delete();

        return redirect()->route('admin.artworks.index')->with('success', 'Artwork gelöscht.');
    }

    public function destroyImage(Artwork $artwork, ArtworkImage $image)
    {
        if ($image->artwork_id !== $artwork->id) abort(404);

        $wasPrimary = $image->is_primary;
        if ($image->file_path) {
            Storage::disk('public')->delete($image->file_path);
        }
        $image->delete();

        if ($wasPrimary) {
            $next = $artwork->images()->orderBy('sort_order')->orderBy('id')->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        $this->syncArtworkDocument($artwork->fresh());

        return back()->with('success', 'Bildvariante gelöscht.');
    }

    public function thumbnail(Artwork $artwork)
    {
        $artwork->load(['images', 'logos']);

        // Primary image → first image → legacy artwork_path → primary logo → first logo
        $imagePath = $artwork->primary_image?->file_path
            ?? $artwork->images->first()?->file_path
            ?? $artwork->getRawOriginal('artwork_path');

        if (!$imagePath || !Storage::disk('public')->exists($imagePath)) {
            $logo = $artwork->primary_logo ?? $artwork->logos->first();
            $imagePath = $logo?->file_path;
        }

        if (!$imagePath || !Storage::disk('public')->exists($imagePath)) {
            abort(404);
        }

        $thumbDir = 'artworks/thumbs';
        $thumbPath = $thumbDir . '/' . basename($imagePath);

        // Return cached thumbnail if exists
        if (Storage::disk('public')->exists($thumbPath)) {
            return response(Storage::disk('public')->get($thumbPath), 200, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        $sourcePath = Storage::disk('public')->path($imagePath);
        $mime = mime_content_type($sourcePath);

        $source = match (true) {
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => @imagecreatefromjpeg($sourcePath),
            str_contains($mime, 'png') => @imagecreatefrompng($sourcePath),
            str_contains($mime, 'webp') => @imagecreatefromwebp($sourcePath),
            default => null,
        };

        if (!$source) {
            // Fallback: serve original
            return response(Storage::disk('public')->get($imagePath), 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        $origW = imagesx($source);
        $origH = imagesy($source);
        $size = 600;
        $thumbW = $origW >= $origH ? $size : (int) ($origW * $size / $origH);
        $thumbH = $origH >= $origW ? $size : (int) ($origH * $size / $origW);

        $thumb = imagecreatetruecolor($thumbW, $thumbH);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbW, $thumbH, $origW, $origH);
        imagedestroy($source);

        // Save to storage
        Storage::disk('public')->makeDirectory($thumbDir);
        $fullThumbPath = Storage::disk('public')->path($thumbPath);
        imagejpeg($thumb, $fullThumbPath, 80);
        imagedestroy($thumb);

        return response(Storage::disk('public')->get($thumbPath), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    public function destroyLogo(Artwork $artwork, ArtworkLogo $logo)
    {
        Storage::disk('public')->delete($logo->file_path);

        // Delete associated document
        Document::where('documentable_type', ArtworkLogo::class)
            ->where('documentable_id', $logo->id)
            ->delete();

        $logo->delete();

        return back()->with('success', 'Logo gelöscht.');
    }

    public function creditSearch(Request $request)
    {
        $q = $request->input('q', '');
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $parts = array_values(array_filter(preg_split('/\s+/', trim($q))));
        $contacts = Contact::where(function ($query) use ($q, $parts) {
            $query->where('first_name', 'like', "%{$q}%")
                  ->orWhere('last_name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('ipis', 'like', "%{$q}%");
            if (count($parts) >= 2) {
                $query->orWhere(function ($q2) use ($parts) {
                    foreach ($parts as $part) {
                        $q2->where(function ($q3) use ($part) {
                            $q3->where('first_name', 'like', "%{$part}%")
                               ->orWhere('last_name', 'like', "%{$part}%");
                        });
                    }
                });
            }
        })->limit(50)->get();

        $needle = mb_strtolower($q);
        $contactResults = [];
        foreach ($contacts as $c) {
            $contactMatches = str_contains(mb_strtolower($c->first_name ?? ''), $needle)
                || str_contains(mb_strtolower($c->last_name ?? ''), $needle)
                || str_contains(mb_strtolower($c->email ?? ''), $needle);

            if ($contactMatches) {
                $contactResults[] = [
                    'id' => $c->id,
                    'type' => 'contact',
                    'name' => $c->full_name,
                    'detail' => $c->email,
                    'ipi_number' => null,
                    'ipi_name' => null,
                    'kind' => 'contact',
                ];
            }

            foreach ($c->ipis ?? [] as $ipi) {
                $ipiNumber = $ipi['number'] ?? '';
                $ipiName = $ipi['name'] ?? '';
                if ($ipiNumber === '' && $ipiName === '') continue;

                $ipiMatches = str_contains(mb_strtolower($ipiName), $needle)
                    || str_contains(mb_strtolower($ipiNumber), $needle);

                if (!$ipiMatches && !$contactMatches) continue;

                $contactResults[] = [
                    'id' => $c->id,
                    'type' => 'contact',
                    'name' => $ipiName !== '' ? $ipiName : $c->full_name,
                    'detail' => $c->full_name,
                    'ipi_number' => $ipiNumber !== '' ? $ipiNumber : null,
                    'ipi_name' => $ipiName !== '' ? $ipiName : null,
                    'kind' => 'ipi',
                ];
            }
        }

        $organizations = Organization::where(function ($query) use ($q) {
            $query->whereRaw('LOWER(CAST(names AS CHAR)) LIKE ?', ['%' . mb_strtolower($q) . '%'])
                  ->orWhere('email', 'like', "%{$q}%");
        })->limit(50)->get()->map(fn ($o) => [
            'id' => $o->id,
            'type' => 'organization',
            'name' => $o->primary_name,
            'detail' => $o->type,
            'ipi_number' => null,
            'ipi_name' => null,
            'kind' => 'organization',
        ]);

        return response()->json(collect($contactResults)->concat($organizations)->values());
    }

    private function syncCredits(Artwork $artwork, array $credits): void
    {
        $artwork->credits()->delete();

        foreach ($credits as $role => $entries) {
            if (!is_array($entries)) continue;
            foreach ($entries as $entry) {
                if (!$entry || !preg_match('/^(contact|organization):(\d+)(?::ipi:(.+))?$/', $entry, $m)) continue;
                $type = $m[1] === 'contact' ? Contact::class : Organization::class;
                $artwork->credits()->create([
                    'role' => $role,
                    'creditable_type' => $type,
                    'creditable_id' => (int) $m[2],
                    'ipi_number' => $m[3] ?? null,
                ]);
            }
        }
    }

    private function validateArtworkRequest(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'yoc' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',

            'images' => 'nullable|array',
            'images.*.file' => 'nullable|file|mimes:jpg,jpeg,png,tiff,tif,webp|max:51200',
            'images.*.purpose' => 'nullable|string|max:100',

            'existing_images' => 'nullable|array',
            'existing_images.*.purpose' => 'nullable|string|max:100',

            'primary_image_selector' => 'nullable|string|max:50',

            'logos' => 'nullable|array',
            'logos.*.file' => 'nullable|file|image|max:51200',
            'logos.*.comment' => 'nullable|string|max:255',
            'logos.*.purpose' => 'nullable|string|max:100',

            'existing_logos' => 'nullable|array',
            'existing_logos.*.comment' => 'nullable|string|max:255',
            'existing_logos.*.purpose' => 'nullable|string|max:100',

            'primary_logo_selector' => 'nullable|string|max:50',

            'credits' => 'nullable|array',
            'credits.*' => 'nullable|array',
            'credits.*.*' => ['nullable', 'string', 'regex:/^(contact|organization):\d+(?::ipi:.+)?$/'],
        ]);
    }

    private function storeImages(Request $request, Artwork $artwork, array &$dpiWarnings = []): void
    {
        foreach ($request->input('images', []) as $index => $imageData) {
            $file = $request->file("images.{$index}.file");
            if (!$file) continue;

            $meta = $this->extractImageMetadata($file);
            $path = $file->store('artworks', 'public');

            $artwork->images()->create([
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'width' => $meta['width'],
                'height' => $meta['height'],
                'dpi' => $meta['dpi'],
                'purpose' => $imageData['purpose'] ?? null,
                'is_primary' => false,
                'sort_order' => $artwork->images()->max('sort_order') + 1,
            ]);

            if ($meta['dpi'] !== null && $meta['dpi'] < 300) {
                $dpiWarnings[] = "Hinweis: \"{$file->getClientOriginalName()}\" hat nur {$meta['dpi']} DPI (empfohlen: 300+).";
            }
        }
    }

    private function updateExistingImages(Artwork $artwork, array $existing): void
    {
        foreach ($existing as $id => $data) {
            $image = $artwork->images()->find($id);
            if (!$image) continue;

            if (!empty($data['delete'])) {
                if ($image->file_path) {
                    Storage::disk('public')->delete($image->file_path);
                }
                $image->delete();
                continue;
            }

            $image->update([
                'purpose' => $data['purpose'] ?? null,
            ]);
        }
    }

    private function applyPrimarySelection(Artwork $artwork, ?string $selector): void
    {
        if (!$selector) return;
        $artwork->images()->update(['is_primary' => false]);

        if (preg_match('/^existing:(\d+)$/', $selector, $m)) {
            $artwork->images()->where('id', (int) $m[1])->update(['is_primary' => true]);
            return;
        }
        // "new:<index>" or similar — fall back to most recent image
        if (str_starts_with($selector, 'new:')) {
            $newest = $artwork->images()->orderByDesc('id')->first();
            if ($newest) $newest->update(['is_primary' => true]);
        }
    }

    private function ensureExactlyOnePrimaryImage(Artwork $artwork): void
    {
        $artwork->load('images');
        $primaries = $artwork->images->where('is_primary', true);

        if ($primaries->count() === 1) return;

        if ($primaries->count() > 1) {
            $keep = $primaries->first();
            $artwork->images()->where('is_primary', true)->where('id', '!=', $keep->id)->update(['is_primary' => false]);
            return;
        }

        $first = $artwork->images->first();
        if ($first) {
            $first->update(['is_primary' => true]);
        }
    }

    private function storeLogos(Request $request, Artwork $artwork): void
    {
        foreach ($request->input('logos', []) as $index => $logoData) {
            $file = $request->file("logos.{$index}.file");
            if (!$file) continue;

            $path = $file->store('artwork-logos', 'public');
            $logo = $artwork->logos()->create([
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'comment' => $logoData['comment'] ?? null,
                'purpose' => $logoData['purpose'] ?? null,
                'is_primary' => false,
                'sort_order' => $artwork->logos()->max('sort_order') + 1,
            ]);

            Document::create([
                'title' => $artwork->title . ' — ' . $file->getClientOriginalName(),
                'category' => 'music',
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'documentable_type' => ArtworkLogo::class,
                'documentable_id' => $logo->id,
            ]);
        }
    }

    private function updateExistingLogos(Artwork $artwork, array $existing): void
    {
        foreach ($existing as $id => $data) {
            $logo = $artwork->logos()->find($id);
            if (!$logo) continue;

            $logo->update([
                'comment' => $data['comment'] ?? null,
                'purpose' => $data['purpose'] ?? null,
            ]);
        }
    }

    private function applyPrimaryLogoSelection(Artwork $artwork, ?string $selector): void
    {
        if (!$selector) return;
        $artwork->logos()->update(['is_primary' => false]);

        if (preg_match('/^existing:(\d+)$/', $selector, $m)) {
            $artwork->logos()->where('id', (int) $m[1])->update(['is_primary' => true]);
            return;
        }
        if (str_starts_with($selector, 'new:')) {
            $newest = $artwork->logos()->orderByDesc('id')->first();
            if ($newest) $newest->update(['is_primary' => true]);
        }
    }

    private function ensureExactlyOnePrimaryLogo(Artwork $artwork): void
    {
        $artwork->load('logos');
        $primaries = $artwork->logos->where('is_primary', true);

        if ($primaries->count() === 1) return;

        if ($primaries->count() > 1) {
            $keep = $primaries->first();
            $artwork->logos()->where('is_primary', true)->where('id', '!=', $keep->id)->update(['is_primary' => false]);
            return;
        }

        $first = $artwork->logos->first();
        if ($first) {
            $first->update(['is_primary' => true]);
        }
    }

    private function extractImageMetadata(\Illuminate\Http\UploadedFile $file): array
    {
        $meta = ['width' => null, 'height' => null, 'dpi' => null];

        try {
            $info = @getimagesize($file->getPathname());
            if ($info) {
                $meta['width'] = $info[0] ?? null;
                $meta['height'] = $info[1] ?? null;
            }

            $mime = $file->getMimeType();
            if (in_array($mime, ['image/jpeg', 'image/jpg', 'image/tiff'])) {
                $exif = @exif_read_data($file->getPathname());
                if ($exif && isset($exif['XResolution'])) {
                    $meta['dpi'] = $this->parseDpiValue($exif['XResolution']);
                }
            }
        } catch (\Throwable $e) {
            // best-effort
        }

        return $meta;
    }

    private function syncArtworkDocument(Artwork $artwork): void
    {
        $artwork->load('images');
        $primary = $artwork->primary_image;

        if (!$primary || !$primary->file_path) {
            Document::where('documentable_type', Artwork::class)
                ->where('documentable_id', $artwork->id)
                ->delete();
            return;
        }

        Document::updateOrCreate(
            [
                'documentable_type' => Artwork::class,
                'documentable_id' => $artwork->id,
            ],
            [
                'title' => $artwork->title . ' — Artwork',
                'category' => 'music',
                'file_path' => $primary->file_path,
                'file_size' => $primary->file_size,
                'mime_type' => $primary->mime_type,
            ]
        );
    }

    private function checkDpi($file): ?string
    {
        try {
            $mime = $file->getMimeType();

            if (in_array($mime, ['image/jpeg', 'image/jpg', 'image/tiff'])) {
                $exif = @exif_read_data($file->getPathname());
                if ($exif && isset($exif['XResolution'])) {
                    $dpi = $this->parseDpiValue($exif['XResolution']);
                    if ($dpi && $dpi < 300) {
                        return "Achtung: Die Auflösung des Artworks beträgt {$dpi} DPI (empfohlen: mind. 300 DPI).";
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently fail — DPI check is best-effort
        }

        return null;
    }

    private function parseDpiValue($value): ?int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        // EXIF stores DPI as fraction like "300/1"
        if (is_string($value) && str_contains($value, '/')) {
            [$num, $den] = explode('/', $value, 2);
            if ((int) $den > 0) {
                return (int) ((int) $num / (int) $den);
            }
        }

        return null;
    }

    public function search(Request $request)
    {
        $query = Artwork::query();

        if ($q = $request->input('q')) {
            $query->where('title', 'like', "%{$q}%");
        }

        $results = $query->orderBy('title')->limit(50)->get()->map(fn ($a) => [
            'id' => $a->id,
            'title' => $a->title,
        ]);

        return response()->json($results);
    }
}
