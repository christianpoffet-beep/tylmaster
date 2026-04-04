<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use App\Models\Track;
use App\Models\Contract;
use App\Models\Project;
use App\Models\Artwork;
use App\Models\ProductTemplate;
use App\Models\Setting;
use App\Models\Organization;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Release::withCount('tracks')->with(['organizations' => fn($q) => $q->where('type', 'label')]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('ean_upc', 'like', "%{$search}%")
                  ->orWhere('upc', 'like', "%{$search}%")
                  ->orWhere('catalog_number', 'like', "%{$search}%")
                  ->orWhere('label_name', 'like', "%{$search}%")
                  ->orWhere('main_artist', 'like', "%{$search}%");
            });
        }

        if ($productType = $request->input('product_type')) {
            $query->whereJsonContains('product_type', $productType);
        }

        $sortField = $request->input('sort', 'release_date');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['title', 'release_date', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'release_date';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $releases = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();
        return view('admin.music.releases.index', compact('releases'));
    }

    public function create()
    {
        $territoryPresets = Contract::TERRITORY_PRESETS;
        $projects = Project::orderBy('name')->get();
        $contracts = Contract::orderBy('title')->get();
        $artworks = Artwork::orderBy('title')->get();
        $artworks = Artwork::orderBy('title')->get();
        $templates = ProductTemplate::orderBy('sort_order')->get();
        $allTracks = Track::orderBy('title')->get();
        $catalogs = Organization::where('type', 'label')->whereNotNull('catalogs')->get()
            ->flatMap(fn($org) => collect($org->catalogs)->map(fn($c) => ['prefix' => $c, 'label' => $org->primary_name]))
            ->unique('prefix')->sortBy('prefix')->values();
        return view('admin.music.releases.create', compact('territoryPresets', 'projects', 'contracts', 'artworks', 'templates', 'allTracks', 'catalogs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_language' => 'nullable|string|max:10',
            'product_type' => 'nullable|array',
            'ean_upc' => 'nullable|string|max:30',
            'release_date' => 'nullable|date',
            'label_name' => 'nullable|string|max:255',
            'catalog_number' => 'nullable|string|max:50',
            'main_artist' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'release_info' => 'nullable|string|max:5000',
            'release_info_internal' => 'nullable|string|max:5000',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'depth' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|in:CHF,EUR,USD,GBP',
            'stock' => 'nullable|integer|min:0',
            'cover_image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        if ($request->input('territory_worldwide')) {
            $validated['territory'] = ['ALL'];
        } else {
            $validated['territory'] = $request->input('territory', []);
        }

        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = $request->file('cover_image')->store('covers', 'public');
        }
        unset($validated['cover_image']);

        $release = Release::create($validated);

        // Sync organizations
        $orgIds = array_merge(
            $request->input('band_ids', []),
            $request->input('label_ids', []),
            $request->input('publisher_ids', [])
        );
        $release->organizations()->sync($orgIds);

        // Sync credits
        $release->contacts()->detach();
        foreach ($request->input('credits', []) as $credit) {
            if (!empty($credit['contact_id']) && !empty($credit['role'])) {
                $pivot = ['role' => $credit['role'], 'instrument' => null];
                if ($credit['role'] === 'instrumentalist' && !empty($credit['instrument'])) {
                    $pivot['instrument'] = $credit['instrument'];
                }
                $release->contacts()->attach($credit['contact_id'], $pivot);
            }
        }

        // Sync tracks
        $this->syncTracks($release, $request);

        // Sync artworks
        $this->syncArtworks($release, $request);

        // Sync projects & contracts
        $release->projects()->sync($request->input('project_ids', []));
        $release->contracts()->sync($request->input('contract_ids', []));

        // Auto-fill label_name from tracks
        $release->load('tracks.organizations');
        $release->syncLabelFromTracks();

        return redirect()->route('admin.releases.show', $release)->with('success', 'Produkt erstellt.');
    }

    public function show(Release $release)
    {
        $release->load([
            'tracks.organizations', 'tracks.contacts',
            'contacts', 'organizations', 'projects', 'contracts',
            'artworks.logos', 'artworks.credits.creditable',
        ]);
        return view('admin.music.releases.show', compact('release'));
    }

    public function edit(Release $release)
    {
        $release->load(['contacts', 'organizations', 'tracks', 'projects', 'contracts', 'artworks']);
        $territoryPresets = Contract::TERRITORY_PRESETS;
        $projects = Project::orderBy('name')->get();
        $contracts = Contract::orderBy('title')->get();
        $artworks = Artwork::orderBy('title')->get();
        $templates = ProductTemplate::orderBy('sort_order')->get();
        $allTracks = Track::orderBy('title')->get();
        $catalogs = Organization::where('type', 'label')->whereNotNull('catalogs')->get()
            ->flatMap(fn($org) => collect($org->catalogs)->map(fn($c) => ['prefix' => $c, 'label' => $org->primary_name]))
            ->unique('prefix')->sortBy('prefix')->values();
        return view('admin.music.releases.edit', compact('release', 'territoryPresets', 'projects', 'contracts', 'artworks', 'templates', 'allTracks', 'catalogs'));
    }

    public function update(Request $request, Release $release)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_language' => 'nullable|string|max:10',
            'product_type' => 'nullable|array',
            'ean_upc' => 'nullable|string|max:30',
            'release_date' => 'nullable|date',
            'label_name' => 'nullable|string|max:255',
            'catalog_number' => 'nullable|string|max:50',
            'main_artist' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'release_info' => 'nullable|string|max:5000',
            'release_info_internal' => 'nullable|string|max:5000',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'depth' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|in:CHF,EUR,USD,GBP',
            'stock' => 'nullable|integer|min:0',
            'cover_image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        if ($request->input('territory_worldwide')) {
            $validated['territory'] = ['ALL'];
        } else {
            $validated['territory'] = $request->input('territory', []);
        }

        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = $request->file('cover_image')->store('covers', 'public');
        }
        unset($validated['cover_image']);

        // Ensure product_type is always set (even as empty array)
        if (!isset($validated['product_type'])) {
            $validated['product_type'] = [];
        }

        $release->update($validated);

        // Sync organizations
        $orgIds = array_merge(
            $request->input('band_ids', []),
            $request->input('label_ids', []),
            $request->input('publisher_ids', [])
        );
        $release->organizations()->sync($orgIds);

        // Sync credits
        $release->contacts()->detach();
        foreach ($request->input('credits', []) as $credit) {
            if (!empty($credit['contact_id']) && !empty($credit['role'])) {
                $pivot = ['role' => $credit['role'], 'instrument' => null];
                if ($credit['role'] === 'instrumentalist' && !empty($credit['instrument'])) {
                    $pivot['instrument'] = $credit['instrument'];
                }
                $release->contacts()->attach($credit['contact_id'], $pivot);
            }
        }

        // Sync tracks
        $this->syncTracks($release, $request);

        // Sync artworks
        $this->syncArtworks($release, $request);

        // Sync projects & contracts
        $release->projects()->sync($request->input('project_ids', []));
        $release->contracts()->sync($request->input('contract_ids', []));

        // Auto-fill label_name from tracks
        $release->load('tracks.organizations');
        $release->syncLabelFromTracks();

        return redirect()->route('admin.releases.show', $release)->with('success', 'Produkt aktualisiert.');
    }

    public function destroy(Release $release)
    {
        $release->delete();
        return redirect()->route('admin.releases.index')->with('success', 'Produkt gelöscht.');
    }

    private function syncTracks(Release $release, Request $request): void
    {
        $trackIds = $request->input('track_ids', []);
        $trackNumbers = $request->input('track_numbers', []);

        $syncData = [];
        foreach ($trackIds as $trackId) {
            if (!$trackId) continue;
            $syncData[$trackId] = [
                'track_number' => $trackNumbers[$trackId] ?? null,
                'disc_number' => 1,
                'role' => 'main',
            ];
        }
        $release->tracks()->sync($syncData);
    }

    private function syncArtworks(Release $release, Request $request): void
    {
        $artworkIds = $request->input('artwork_ids', []);
        $primaryId = $request->input('primary_artwork_id');

        $syncData = [];
        foreach ($artworkIds as $id) {
            $syncData[$id] = ['is_primary' => ($id == $primaryId)];
        }
        $release->artworks()->sync($syncData);
    }

    public function nextCatalogNumber(Request $request)
    {
        $prefix = $request->input('prefix', '');
        if (!$prefix) {
            // Return all available catalogs from label organizations
            $catalogs = Organization::where('type', 'label')
                ->whereNotNull('catalogs')
                ->get()
                ->flatMap(function ($org) {
                    return collect($org->catalogs)->map(fn($c) => [
                        'prefix' => $c,
                        'label' => $org->primary_name,
                    ]);
                })
                ->unique('prefix')
                ->sortBy('prefix')
                ->values();

            return response()->json(['catalogs' => $catalogs]);
        }

        // Find all existing catalog numbers with this prefix
        $existing = Release::where('catalog_number', 'like', $prefix . '%')
            ->pluck('catalog_number')
            ->map(function ($cn) use ($prefix) {
                $numPart = substr($cn, strlen($prefix));
                return is_numeric($numPart) ? (int) $numPart : null;
            })
            ->filter()
            ->sort()
            ->values();

        // Find lowest unused number starting from 1
        $next = 1;
        foreach ($existing as $num) {
            if ($num === $next) {
                $next++;
            } else {
                break;
            }
        }

        $catalogNumber = $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);

        return response()->json(['catalog_number' => $catalogNumber]);
    }

    public function search(Request $request)
    {
        $query = Release::query();

        if ($q = $request->input('q')) {
            $query->where('title', 'like', "%{$q}%");
        }

        $results = $query->orderBy('title')->limit(50)->get()->map(fn ($r) => [
            'id' => $r->id,
            'title' => $r->title,
            'product_type' => $r->product_type,
        ]);

        return response()->json($results);
    }
}
