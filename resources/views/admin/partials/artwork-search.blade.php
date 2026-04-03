{{-- Artwork search component --}}
{{-- Usage: @include('admin.partials.artwork-search', ['selected' => $model->artworks ?? collect()]) --}}

@php
    $artworkInputName = $artworkInputName ?? 'artwork_ids[]';
@endphp

<div x-data="artworkSearch()" class="relative" @paste-artworks.window="$event.detail.forEach(a => { if (!isSelected(a.id)) selected.push(a); })">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Artworks</label>

    <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(query.length >= 1 || results.length) open = true; else { search(); }"
        placeholder="Artwork suchen..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 mb-2">

    {{-- Results dropdown --}}
    <div x-show="open && results.length > 0" @click.away="open = false" x-cloak
        class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
        <template x-for="result in results" :key="result.id">
            <button type="button" @click="addArtwork(result)"
                class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 last:border-0"
                :class="isSelected(result.id) ? 'opacity-50 cursor-not-allowed' : ''"
                :disabled="isSelected(result.id)">
                <span class="text-sm text-gray-900 dark:text-gray-100" x-text="result.title"></span>
            </button>
        </template>
    </div>

    {{-- No results --}}
    <div x-show="open && results.length === 0 && !loading" x-cloak class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg p-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">Keine Artworks gefunden.</p>
    </div>

    {{-- Selected artworks --}}
    <div x-show="selected.length > 0" class="flex flex-wrap gap-2 mt-2">
        <template x-for="artwork in selected" :key="artwork.id">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300">
                <span x-text="artwork.title"></span>
                <button type="button" @click="removeArtwork(artwork.id)" class="hover:text-red-600">&times;</button>
                <input type="hidden" name="{{ $artworkInputName }}" :value="artwork.id">
            </span>
        </template>
    </div>
</div>

@php
    $selectedArtworksJson = ($selected ?? collect())->map(function ($a) {
        return ['id' => $a->id, 'title' => $a->title];
    })->values();
@endphp
<script>
function artworkSearch() {
    return {
        query: '',
        results: [],
        selected: @json($selectedArtworksJson),
        open: false,
        loading: false,

        async search() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ q: this.query });
                const response = await fetch(`{{ route('admin.artworks.search') }}?${params}`);
                this.results = await response.json();
                this.open = true;
            } catch (e) { this.results = []; }
            this.loading = false;
        },

        addArtwork(artwork) {
            if (!this.isSelected(artwork.id)) this.selected.push(artwork);
            this.query = ''; this.results = []; this.open = false;
        },

        removeArtwork(id) { this.selected = this.selected.filter(a => a.id !== id); },
        isSelected(id) { return this.selected.some(a => a.id === id); }
    };
}
</script>
