{{-- Track search component --}}
{{-- Usage: @include('admin.partials.track-search', ['selected' => $model->tracks ?? collect()]) --}}

@php
    $trackInputName = $trackInputName ?? 'track_ids[]';
    $trackStatusLabels = ['draft' => 'Draft', 'released' => 'Released', 'archived' => 'Archived'];
    $trackStatusColors = ['draft' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300', 'released' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300', 'archived' => 'bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300'];
@endphp

<div x-data="trackSearch()" class="relative" @paste-tracks.window="$event.detail.forEach(t => { if (!isSelected(t.id)) selected.push(t); })">
    {{-- Marker: set by Alpine. Without it the section never rendered and the controller must not sync. --}}
    <input type="hidden" name="{{ str_replace('[]', '', $trackInputName) }}_submitted" value="" x-bind:value="'1'">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tracks</label>

    <div class="flex gap-2 mb-2">
        <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(query.length >= 1 || results.length) open = true; else { search(); }"
            placeholder="Titel oder ISRC suchen..." class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        <select x-model="statusFilter" @change="search()" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Status</option>
            <option value="draft">Draft</option>
            <option value="released">Released</option>
            <option value="archived">Archived</option>
        </select>
    </div>

    {{-- Results dropdown --}}
    <div x-show="open && results.length > 0" @click.away="open = false" x-cloak
        class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
        <template x-for="result in results" :key="result.id">
            <button type="button" @click="addTrack(result)"
                class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between border-b border-gray-100 dark:border-gray-700 last:border-0"
                :class="isSelected(result.id) ? 'opacity-50 cursor-not-allowed' : ''"
                :disabled="isSelected(result.id)">
                <div>
                    <span class="text-sm text-gray-900 dark:text-gray-100" x-text="result.title"></span>
                    <span x-show="result.artist" class="text-xs text-gray-400 ml-1" x-text="result.artist"></span>
                    <span x-show="result.isrc" class="text-xs text-gray-400 ml-1 font-mono" x-text="result.isrc"></span>
                </div>
                <span class="text-xs px-1.5 py-0.5 rounded" :class="statusColorMap[result.status] || 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" x-text="statusLabelMap[result.status] || result.status"></span>
            </button>
        </template>
    </div>

    {{-- No results --}}
    <div x-show="open && results.length === 0 && !loading" x-cloak class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg p-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">Keine Tracks gefunden.</p>
    </div>

    {{-- Selected tracks --}}
    <div x-show="selected.length > 0" class="flex flex-wrap gap-2 mt-2">
        <template x-for="track in selected" :key="track.id">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium" :class="statusColorMap[track.status] || 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'">
                <span x-text="track.title"></span>
                <button type="button" @click="removeTrack(track.id)" class="hover:text-red-600">&times;</button>
                <input type="hidden" name="{{ $trackInputName }}" :value="track.id">
            </span>
        </template>
    </div>
</div>

@php
    $selectedTracksJson = ($selected ?? collect())->map(function ($t) {
        return ['id' => $t->id, 'title' => $t->display_title ?? $t->title, 'status' => $t->status];
    })->values();
@endphp
<script>
function trackSearch() {
    return {
        query: '',
        statusFilter: '',
        results: [],
        selected: @json($selectedTracksJson),
        open: false,
        loading: false,
        statusLabelMap: @json($trackStatusLabels),
        statusColorMap: @json($trackStatusColors),

        async search() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ q: this.query });
                if (this.statusFilter) params.append('status', this.statusFilter);
                const response = await fetch(`{{ route('admin.tracks.search') }}?${params}`);
                this.results = await response.json();
                this.open = true;
            } catch (e) { this.results = []; }
            this.loading = false;
        },

        addTrack(track) {
            if (!this.isSelected(track.id)) this.selected.push(track);
            this.query = ''; this.results = []; this.open = false;
        },

        removeTrack(id) { this.selected = this.selected.filter(t => t.id !== id); },
        isSelected(id) { return this.selected.some(t => t.id === id); }
    };
}
</script>
