<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SyncsFormRelations;
use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\ProductTemplate;
use App\Models\Project;
use App\Models\Release;
use App\Models\Track;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    use SyncsFormRelations;

    /**
     * The product list. Band, label and type each take several values: within
     * one filter any of them matches, across filters all have to - two bands
     * and a label give the products of either band on that label. A band or
     * label counts when it is linked to the product or to one of its tracks.
     */
    public function index(Request $request)
    {
        $query = Release::withCount('tracks')->with([
            'organizations' => fn ($q) => $q->whereIn('type', ['band', 'label']),
            // the album for the play button, the bands of the tracks, the main artwork
            'tracks.mainAudioFile', 'tracks.organizations',
            'artworks.images', 'artworks.logos',
        ]);

        if ($search = $request->input('search')) {
            $needle = mb_strtolower(trim($search));
            $orgIds = Organization::whereIn('type', ['band', 'label'])->get()
                ->filter(fn ($org) => str_contains(mb_strtolower($org->all_names), $needle))
                ->pluck('id');

            $query->where(function ($q) use ($search, $orgIds) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('ean_upc', 'like', "%{$search}%")
                    ->orWhere('upc', 'like', "%{$search}%")
                    ->orWhere('catalog_number', 'like', "%{$search}%")
                    ->orWhere('label_name', 'like', "%{$search}%")
                    ->orWhere('main_artist', 'like', "%{$search}%")
                    ->orWhereHas('organizations', fn ($o) => $o->whereIn('organizations.id', $orgIds));
            });
        }

        $filters = [
            'band' => $this->idList($request->input('band')),
            'label' => $this->idList($request->input('label')),
            // product_type: the single filter of before, still in old links
            'type' => collect((array) $request->input('type', []))
                ->push($request->input('product_type'))
                ->filter(fn ($type) => is_string($type) && $type !== '')
                ->unique()->values()->all(),
        ];

        foreach (['band', 'label'] as $kind) {
            if ($ids = $filters[$kind]) {
                $query->where(fn ($q) => $q
                    ->whereHas('organizations', fn ($o) => $o->whereIn('organizations.id', $ids))
                    ->orWhereHas('tracks.organizations', fn ($o) => $o->whereIn('organizations.id', $ids)));
            }
        }

        if ($types = $filters['type']) {
            $query->where(function ($q) use ($types) {
                foreach ($types as $type) {
                    $q->orWhereJsonContains('product_type', $type);
                }
            });
        }

        $sortField = $request->input('sort', 'release_date');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['title', 'release_date', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'release_date';
        }
        if (! in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'desc';
        }

        $releases = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();
        $filterOptions = $this->filterOptions();
        $activeFilters = $this->activeFilters($request, $filters, $filterOptions);

        return view('admin.music.releases.index', compact('releases', 'filters', 'filterOptions', 'activeFilters'));
    }

    /** @return int[] */
    private function idList(mixed $value): array
    {
        return collect((array) $value)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }

    /** Only what occurs: bands and labels on a product or its tracks, types in use. */
    private function filterOptions(): array
    {
        $organizations = Organization::whereIn('type', ['band', 'label'])
            ->where(fn ($q) => $q->whereHas('releases')->orWhereHas('tracks', fn ($t) => $t->whereHas('releases')))
            ->get()
            ->sortBy(fn ($org) => mb_strtolower($org->primary_name));

        $options = fn (string $type) => $organizations->where('type', $type)
            ->map(fn ($org) => ['value' => (string) $org->id, 'label' => $org->primary_name])
            ->values()->all();

        $types = Release::pluck('product_type')->flatten()
            ->filter(fn ($type) => is_string($type) && $type !== '')
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn ($type) => ['value' => $type, 'label' => $type])
            ->values()->all();

        return ['band' => $options('band'), 'label' => $options('label'), 'type' => $types];
    }

    /** The chosen filters as chips, each with the link that drops it. */
    private function activeFilters(Request $request, array $filters, array $options): array
    {
        $chips = [];
        foreach (['band' => 'Band', 'label' => 'Label', 'type' => 'Typ'] as $key => $name) {
            $labels = collect($options[$key])->pluck('label', 'value');

            foreach ($filters[$key] as $value) {
                $query = array_merge($request->except(['page', 'product_type', $key]), [
                    $key => array_values(array_diff($filters[$key], [$value])),
                ]);

                $chips[] = [
                    'label' => $name . ': ' . ($labels[(string) $value] ?? $value),
                    'url' => route('admin.releases.index', array_filter($query, fn ($v) => $v !== [] && $v !== null && $v !== '')),
                ];
            }
        }

        return $chips;
    }

    public function create()
    {
        $territoryPresets = Contract::TERRITORY_PRESETS;
        $projects = Project::orderBy('name')->get();
        $contracts = Contract::orderBy('title')->get();
        $artworks = Artwork::orderBy('title')->get();
        $templates = ProductTemplate::orderBy('sort_order')->get();
        $tracklist = $this->tracklistConfig(null);
        $catalogs = Organization::where('type', 'label')->whereNotNull('catalogs')->get()
            ->flatMap(fn ($org) => collect($org->catalogs)->map(fn ($c) => ['prefix' => $c, 'label' => $org->primary_name]))
            ->unique('prefix')->sortBy('prefix')->values();

        return view('admin.music.releases.create', compact('territoryPresets', 'projects', 'contracts', 'artworks', 'templates', 'tracklist', 'catalogs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_language' => 'nullable|string|max:10',
            'product_type' => 'nullable|array',
            'product_type.*' => 'nullable|string|max:100',
            'ean_upc' => 'nullable|string|max:30',
            'release_date' => 'nullable|date',
            'catalog_number' => 'nullable|string|max:50',
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

        // Before the cover is stored, so a rejected form leaves no file behind
        $this->validateTracklist($request);

        if ($request->input('territory_worldwide')) {
            $validated['territory'] = ['ALL'];
        } else {
            $validated['territory'] = $request->input('territory', []);
        }

        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = $request->file('cover_image')->store('covers', 'public');
        }
        unset($validated['cover_image']);

        $this->applyProductTypes($request, $validated);

        $release = Release::create($validated);

        // Bands, labels and publishers share one relation but three sections
        if ($this->sectionSubmitted($request, 'band_ids', 'label_ids', 'publisher_ids')) {
            $release->organizations()->sync(array_merge(
                $request->input('band_ids', []),
                $request->input('label_ids', []),
                $request->input('publisher_ids', [])
            ));
        }

        // Sync credits
        if ($this->sectionSubmitted($request, 'credits')) {
            $release->contacts()->detach();
            foreach ($request->input('credits', []) as $credit) {
                if (! empty($credit['contact_id']) && ! empty($credit['role'])) {
                    $pivot = ['role' => $credit['role'], 'instrument' => null];
                    if ($credit['role'] === 'instrumentalist' && ! empty($credit['instrument'])) {
                        $pivot['instrument'] = $credit['instrument'];
                    }
                    $release->contacts()->attach($credit['contact_id'], $pivot);
                }
            }
        }

        // Sync tracks
        $this->syncTracks($release, $request);

        // Sync artworks
        $this->syncArtworks($release, $request);

        // Sync projects & contracts
        $this->syncSubmitted($request, $release->projects(), 'project_ids');
        $this->syncSubmitted($request, $release->contracts(), 'contract_ids');

        // Without a label the product takes its tracks' labels; old text gives way to linked organizations
        $release->load('tracks.organizations');
        $release->syncLabelFromTracks();
        $release->clearSupersededNames();

        return redirect()->route('admin.releases.show', $release)->with('success', 'Produkt erstellt.');
    }

    public function show(Release $release)
    {
        $release->load([
            'tracks.organizations', 'tracks.contacts', 'tracks.mainAudioFile',
            'contacts', 'organizations', 'projects', 'contracts',
            'artworks.images', 'artworks.logos', 'artworks.credits.creditable',
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
        $tracklist = $this->tracklistConfig($release);
        $catalogs = Organization::where('type', 'label')->whereNotNull('catalogs')->get()
            ->flatMap(fn ($org) => collect($org->catalogs)->map(fn ($c) => ['prefix' => $c, 'label' => $org->primary_name]))
            ->unique('prefix')->sortBy('prefix')->values();

        return view('admin.music.releases.edit', compact('release', 'territoryPresets', 'projects', 'contracts', 'artworks', 'templates', 'tracklist', 'catalogs'));
    }

    public function update(Request $request, Release $release)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_language' => 'nullable|string|max:10',
            'product_type' => 'nullable|array',
            'product_type.*' => 'nullable|string|max:100',
            'ean_upc' => 'nullable|string|max:30',
            'release_date' => 'nullable|date',
            'catalog_number' => 'nullable|string|max:50',
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

        // Before the cover is stored, so a rejected form leaves no file behind
        $this->validateTracklist($request);

        if ($request->input('territory_worldwide')) {
            $validated['territory'] = ['ALL'];
        } else {
            $validated['territory'] = $request->input('territory', []);
        }

        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = $request->file('cover_image')->store('covers', 'public');
        }
        unset($validated['cover_image']);

        $this->applyProductTypes($request, $validated);

        $release->update($validated);

        // Bands, labels and publishers share one relation but three sections
        if ($this->sectionSubmitted($request, 'band_ids', 'label_ids', 'publisher_ids')) {
            $release->organizations()->sync(array_merge(
                $request->input('band_ids', []),
                $request->input('label_ids', []),
                $request->input('publisher_ids', [])
            ));
        }

        // Sync credits
        if ($this->sectionSubmitted($request, 'credits')) {
            $release->contacts()->detach();
            foreach ($request->input('credits', []) as $credit) {
                if (! empty($credit['contact_id']) && ! empty($credit['role'])) {
                    $pivot = ['role' => $credit['role'], 'instrument' => null];
                    if ($credit['role'] === 'instrumentalist' && ! empty($credit['instrument'])) {
                        $pivot['instrument'] = $credit['instrument'];
                    }
                    $release->contacts()->attach($credit['contact_id'], $pivot);
                }
            }
        }

        // Sync tracks
        $this->syncTracks($release, $request);

        // Sync artworks
        $this->syncArtworks($release, $request);

        // Sync projects & contracts
        $this->syncSubmitted($request, $release->projects(), 'project_ids');
        $this->syncSubmitted($request, $release->contracts(), 'contract_ids');

        // Without a label the product takes its tracks' labels; old text gives way to linked organizations
        $release->load('tracks.organizations');
        $release->syncLabelFromTracks();
        $release->clearSupersededNames();

        return redirect()->route('admin.releases.show', $release)->with('success', 'Produkt aktualisiert.');
    }

    public function destroy(Release $release)
    {
        $release->delete();

        return redirect()->route('admin.releases.index')->with('success', 'Produkt gelöscht.');
    }

    /**
     * The type field builds its inputs in the browser and reports in with a
     * marker. Without marker and types the section never rendered, and the
     * stored types stay as they are; with the marker an empty field clears them.
     */
    private function applyProductTypes(Request $request, array &$validated): void
    {
        if (! $request->has('product_type') && ! $this->sectionSubmitted($request, 'product_type')) {
            unset($validated['product_type']);

            return;
        }

        $validated['product_type'] = collect($validated['product_type'] ?? [])
            ->filter(fn ($type) => filled($type))
            ->unique()
            ->values()
            ->all();
    }

    private function validateTracklist(Request $request): void
    {
        $request->validate([
            'track_ids' => 'nullable|array',
            'track_ids.*' => 'integer|exists:tracks,id',
            'track_numbers' => 'nullable|array',
            'track_numbers.*' => 'nullable|integer|min:1|max:9999',
        ], [
            'track_numbers.*.integer' => 'Tracknummern müssen ganze Zahlen sein.',
            'track_numbers.*.min' => 'Tracknummern beginnen bei 1.',
        ]);
    }

    /**
     * Only runs when the tracklist reported in. Disc number and role of a
     * track already on the product are kept - the track form can set them.
     */
    private function syncTracks(Release $release, Request $request): void
    {
        if (! $this->sectionSubmitted($request, 'track_ids')) {
            return;
        }

        $numbers = $request->input('track_numbers', []);
        $current = $release->tracks()->get()->keyBy('id');

        $syncData = [];
        foreach (array_unique(array_filter(array_map('intval', $request->input('track_ids', [])))) as $trackId) {
            $pivot = $current->get($trackId)?->pivot;
            $number = $numbers[$trackId] ?? null;

            $syncData[$trackId] = [
                'track_number' => filled($number) ? (int) $number : null,
                'disc_number' => $pivot->disc_number ?? 1,
                'role' => $pivot->role ?? 'main',
            ];
        }

        $release->tracks()->sync($syncData);
    }

    /**
     * Data for the tracklist field: the rows on the product (or the ones just
     * submitted, after a validation error) and every track with what the
     * picker searches and filters by.
     */
    private function tracklistConfig(?Release $release): array
    {
        $tracks = Track::with(Track::SEARCH_RELATIONS)->orderBy('title')->get();

        if (old('track_ids_submitted')) {
            $numbers = (array) old('track_numbers', []);
            $rows = collect((array) old('track_ids', []))
                ->map(fn ($id) => ['id' => (int) $id, 'number' => filled($numbers[$id] ?? null) ? (int) $numbers[$id] : null]);
        } elseif ($release) {
            // Numbered tracks first, in order; tracks without a number after them
            $rows = $release->tracks
                ->map(fn ($track) => ['id' => $track->id, 'number' => $track->pivot->track_number])
                ->sortBy(fn ($row) => [$row['number'] === null ? 1 : 0, (int) $row['number']])
                ->values();
        } else {
            $rows = collect();
        }

        $bands = $tracks->flatMap(fn ($track) => $track->organizations->where('type', 'band'))
            ->unique('id')
            ->sortBy(fn ($band) => mb_strtolower($band->primary_name))
            ->map(fn ($band) => ['id' => $band->id, 'name' => $band->primary_name])
            ->values()
            ->all();

        $releases = $tracks->flatMap(fn ($track) => $track->releases)
            ->unique('id')
            ->reject(fn ($other) => $release && $other->id === $release->id)
            ->sortBy(fn ($other) => mb_strtolower($other->title))
            ->map(fn ($other) => ['id' => $other->id, 'title' => $other->title.($other->catalog_number ? " ({$other->catalog_number})" : '')])
            ->values()
            ->all();

        return [
            'rows' => $rows->values()->all(),
            'bands' => $bands,
            'releases' => $releases,
            'tracks' => $tracks->map(fn (Track $track) => [
                'id' => $track->id,
                'title' => $track->display_title,
                'bands' => $track->band_names,
                'bandIds' => $track->organizations->where('type', 'band')->pluck('id')->values()->all(),
                'releases' => $track->releases->map(fn ($other) => ['id' => $other->id, 'number' => $other->pivot->track_number])->values()->all(),
                'isrc' => $track->isrc_formatted,
                'duration' => $track->duration_seconds ? $track->formatted_duration : null,
                'seconds' => (int) $track->duration_seconds,
                'haystack' => $track->search_haystack,
            ])->values()->all(),
        ];
    }

    private function syncArtworks(Release $release, Request $request): void
    {
        $artworkIds = $request->input('artwork_ids', []);
        $primaryId = $request->input('primary_artwork_id');

        if (! $this->sectionSubmitted($request, 'artwork_ids')) {
            return;
        }

        $syncData = [];
        foreach ($artworkIds as $id) {
            $syncData[$id] = ['is_primary' => ($id == $primaryId)];
        }
        $release->artworks()->sync($syncData);
    }

    public function nextCatalogNumber(Request $request)
    {
        $prefix = $request->input('prefix', '');
        if (! $prefix) {
            // Return all available catalogs from label organizations
            $catalogs = Organization::where('type', 'label')
                ->whereNotNull('catalogs')
                ->get()
                ->flatMap(function ($org) {
                    return collect($org->catalogs)->map(fn ($c) => [
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
        $existing = Release::where('catalog_number', 'like', $prefix.'%')
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

        $catalogNumber = $prefix.str_pad($next, 3, '0', STR_PAD_LEFT);

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
