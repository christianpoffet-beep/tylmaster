<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\ArtworkCredit;
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
        $query = Artwork::withCount('logos');

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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'artwork_file' => 'nullable|file|mimes:jpg,jpeg,tiff,tif|max:51200',
            'yoc' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'logos' => 'nullable|array',
            'logos.*.file' => 'nullable|file|image|max:51200',
            'logos.*.comment' => 'nullable|string|max:255',
            'credits' => 'nullable|array',
            'credits.*' => 'nullable|array',
            'credits.*.*' => 'nullable|string|regex:/^(contact|organization):\d+$/',
        ]);

        $artworkData = [
            'title' => $validated['title'],
            'yoc' => $validated['yoc'] ?? null,
        ];

        // Handle artwork file upload
        $dpiWarning = null;
        if ($request->hasFile('artwork_file')) {
            $file = $request->file('artwork_file');

            // Validate dimensions (3000x3000)
            $imageInfo = getimagesize($file->getPathname());
            if (!$imageInfo || $imageInfo[0] !== 3000 || $imageInfo[1] !== 3000) {
                return back()->withInput()->withErrors(['artwork_file' => 'Das Artwork muss exakt 3000 × 3000 Pixel gross sein. Aktuell: ' . ($imageInfo ? $imageInfo[0] . ' × ' . $imageInfo[1] : 'unbekannt') . ' px.']);
            }

            // Best-effort DPI check
            $dpiWarning = $this->checkDpi($file);

            $path = $file->store('artworks', 'public');
            $artworkData['artwork_path'] = $path;
            $artworkData['artwork_file_size'] = $file->getSize();
            $artworkData['artwork_mime_type'] = $file->getMimeType();
            $artworkData['artwork_original_name'] = $file->getClientOriginalName();
        }

        $artwork = Artwork::create($artworkData);

        // Sync projects
        $artwork->projects()->sync($request->input('project_ids', []));

        // Sync credits
        $this->syncCredits($artwork, $request->input('credits', []));

        // Handle logo uploads
        $this->storeLogos($request, $artwork);

        // Create document record for artwork file
        if ($artwork->artwork_path) {
            $this->syncArtworkDocument($artwork);
        }

        $redirect = redirect()->route('admin.artworks.show', $artwork)->with('success', 'Artwork erstellt.');

        if ($dpiWarning) {
            $redirect = $redirect->with('warning', $dpiWarning);
        }

        return $redirect;
    }

    public function show(Artwork $artwork)
    {
        $artwork->load(['logos', 'projects', 'credits.creditable']);
        return view('admin.artworks.show', compact('artwork'));
    }

    public function edit(Artwork $artwork)
    {
        $artwork->load(['logos', 'projects', 'credits.creditable']);
        $projects = Project::orderBy('name')->get();
        return view('admin.artworks.edit', compact('artwork', 'projects'));
    }

    public function update(Request $request, Artwork $artwork)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'artwork_file' => 'nullable|file|mimes:jpg,jpeg,tiff,tif|max:51200',
            'yoc' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'logos' => 'nullable|array',
            'logos.*.file' => 'nullable|file|image|max:51200',
            'logos.*.comment' => 'nullable|string|max:255',
            'credits' => 'nullable|array',
            'credits.*' => 'nullable|array',
            'credits.*.*' => 'nullable|string|regex:/^(contact|organization):\d+$/',
        ]);

        $artworkData = [
            'title' => $validated['title'],
            'yoc' => $validated['yoc'] ?? null,
        ];

        // Handle artwork file upload
        $dpiWarning = null;
        if ($request->hasFile('artwork_file')) {
            $file = $request->file('artwork_file');

            // Validate dimensions (3000x3000)
            $imageInfo = getimagesize($file->getPathname());
            if (!$imageInfo || $imageInfo[0] !== 3000 || $imageInfo[1] !== 3000) {
                return back()->withInput()->withErrors(['artwork_file' => 'Das Artwork muss exakt 3000 × 3000 Pixel gross sein. Aktuell: ' . ($imageInfo ? $imageInfo[0] . ' × ' . $imageInfo[1] : 'unbekannt') . ' px.']);
            }

            // Best-effort DPI check
            $dpiWarning = $this->checkDpi($file);

            // Delete old file
            if ($artwork->artwork_path) {
                Storage::disk('public')->delete($artwork->artwork_path);
            }

            $path = $file->store('artworks', 'public');
            $artworkData['artwork_path'] = $path;
            $artworkData['artwork_file_size'] = $file->getSize();
            $artworkData['artwork_mime_type'] = $file->getMimeType();
            $artworkData['artwork_original_name'] = $file->getClientOriginalName();
        }

        $artwork->update($artworkData);

        // Sync projects
        $artwork->projects()->sync($request->input('project_ids', []));

        // Sync credits
        $this->syncCredits($artwork, $request->input('credits', []));

        // Handle new logo uploads
        $this->storeLogos($request, $artwork);

        // Sync document record for artwork file
        if ($artwork->artwork_path) {
            $this->syncArtworkDocument($artwork);
        }

        $redirect = redirect()->route('admin.artworks.show', $artwork)->with('success', 'Artwork aktualisiert.');

        if ($dpiWarning) {
            $redirect = $redirect->with('warning', $dpiWarning);
        }

        return $redirect;
    }

    public function destroy(Artwork $artwork)
    {
        // Delete artwork file
        if ($artwork->artwork_path) {
            Storage::disk('public')->delete($artwork->artwork_path);
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

        $contacts = Contact::where(function ($query) use ($q) {
            $query->where('first_name', 'like', "%{$q}%")
                  ->orWhere('last_name', 'like', "%{$q}%")
                  ->orWhere('company', 'like', "%{$q}%");
        })->limit(10)->get()->map(fn ($c) => [
            'id' => $c->id,
            'type' => 'contact',
            'name' => $c->full_name,
            'detail' => $c->company,
        ]);

        $organizations = Organization::where('names', 'like', "%{$q}%")
            ->limit(10)->get()->map(fn ($o) => [
            'id' => $o->id,
            'type' => 'organization',
            'name' => $o->primary_name,
            'detail' => $o->type,
        ]);

        return response()->json($contacts->concat($organizations)->values());
    }

    private function syncCredits(Artwork $artwork, array $credits): void
    {
        $artwork->credits()->delete();

        foreach ($credits as $role => $entries) {
            if (!is_array($entries)) continue;
            foreach ($entries as $entry) {
                if (!$entry || !preg_match('/^(contact|organization):(\d+)$/', $entry, $m)) continue;
                $type = $m[1] === 'contact' ? Contact::class : Organization::class;
                $artwork->credits()->create([
                    'role' => $role,
                    'creditable_type' => $type,
                    'creditable_id' => (int) $m[2],
                ]);
            }
        }
    }

    private function storeLogos(Request $request, Artwork $artwork): void
    {
        if (!$request->has('logos')) {
            return;
        }

        foreach ($request->input('logos', []) as $index => $logoData) {
            $file = $request->file("logos.{$index}.file");
            if (!$file) {
                continue;
            }

            $path = $file->store('artwork-logos', 'public');
            $logo = $artwork->logos()->create([
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'comment' => $logoData['comment'] ?? null,
            ]);

            // Create document record for logo
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

    private function syncArtworkDocument(Artwork $artwork): void
    {
        Document::updateOrCreate(
            [
                'documentable_type' => Artwork::class,
                'documentable_id' => $artwork->id,
            ],
            [
                'title' => $artwork->title . ' — Artwork',
                'category' => 'music',
                'file_path' => $artwork->artwork_path,
                'file_size' => $artwork->artwork_file_size,
                'mime_type' => $artwork->artwork_mime_type,
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
}
