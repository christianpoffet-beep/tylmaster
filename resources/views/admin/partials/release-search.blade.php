{{-- Release/Product search component --}}
{{-- Usage: @include('admin.partials.release-search', ['selected' => $model->releases ?? collect()]) --}}

@php
    $releaseInputName = $releaseInputName ?? 'release_ids[]';
@endphp

<div x-data="releaseSearch()" class="relative" @paste-releases.window="$event.detail.forEach(r => { if (!isSelected(r.id)) selected.push(r); })">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Produkte</label>

    <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(query.length >= 1 || results.length) open = true; else { search(); }"
        placeholder="Produkt suchen..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 mb-2">

    {{-- Results dropdown --}}
    <div x-show="open && results.length > 0" @click.away="open = false" x-cloak
        class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
        <template x-for="result in results" :key="result.id">
            <button type="button" @click="addRelease(result)"
                class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between border-b border-gray-100 dark:border-gray-700 last:border-0"
                :class="isSelected(result.id) ? 'opacity-50 cursor-not-allowed' : ''"
                :disabled="isSelected(result.id)">
                <span class="text-sm text-gray-900 dark:text-gray-100" x-text="result.title"></span>
                <span x-show="result.product_type" class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300" x-text="result.product_type"></span>
            </button>
        </template>
    </div>

    {{-- No results --}}
    <div x-show="open && results.length === 0 && !loading" x-cloak class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg p-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">Keine Produkte gefunden.</p>
    </div>

    {{-- Selected releases --}}
    <div x-show="selected.length > 0" class="flex flex-wrap gap-2 mt-2">
        <template x-for="release in selected" :key="release.id">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-cyan-100 dark:bg-cyan-900/50 text-cyan-700 dark:text-cyan-300">
                <span x-text="release.title"></span>
                <button type="button" @click="removeRelease(release.id)" class="hover:text-red-600">&times;</button>
                <input type="hidden" name="{{ $releaseInputName }}" :value="release.id">
            </span>
        </template>
    </div>
</div>

@php
    $selectedReleasesJson = ($selected ?? collect())->map(function ($r) {
        return ['id' => $r->id, 'title' => $r->title, 'product_type' => $r->product_type ?? null];
    })->values();
@endphp
<script>
function releaseSearch() {
    return {
        query: '',
        results: [],
        selected: @json($selectedReleasesJson),
        open: false,
        loading: false,

        async search() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ q: this.query });
                const response = await fetch(`{{ route('admin.releases.search') }}?${params}`);
                this.results = await response.json();
                this.open = true;
            } catch (e) { this.results = []; }
            this.loading = false;
        },

        addRelease(release) {
            if (!this.isSelected(release.id)) this.selected.push(release);
            this.query = ''; this.results = []; this.open = false;
        },

        removeRelease(id) { this.selected = this.selected.filter(r => r.id !== id); },
        isSelected(id) { return this.selected.some(r => r.id === id); }
    };
}
</script>
