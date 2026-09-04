{{-- Submission search component --}}
{{-- Usage: @include('admin.partials.submission-search', ['selected' => $model->submissions ?? collect()]) --}}

@php
    $submissionInputName = $submissionInputName ?? 'submission_ids[]';
    $subStatusLabels = ['new' => 'Neu', 'reviewing' => 'In Prüfung', 'accepted' => 'Akzeptiert', 'rejected' => 'Abgelehnt'];
    $subStatusColors = ['new' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300', 'reviewing' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300', 'accepted' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300', 'rejected' => 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300'];
@endphp

<div x-data="submissionSearch()" class="relative">
    {{-- Marker: set by Alpine. Without it the section never rendered and the controller must not sync. --}}
    <input type="hidden" name="{{ str_replace('[]', '', $submissionInputName) }}_submitted" value="" x-bind:value="'1'">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Submissions</label>

    <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(query.length >= 1 || results.length) open = true; else { search(); }"
        placeholder="Artist oder Track suchen..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 mb-2">

    {{-- Results dropdown --}}
    <div x-show="open && results.length > 0" @click.away="open = false" x-cloak
        class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
        <template x-for="result in results" :key="result.id">
            <button type="button" @click="addSubmission(result)"
                class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between border-b border-gray-100 dark:border-gray-700 last:border-0"
                :class="isSelected(result.id) ? 'opacity-50 cursor-not-allowed' : ''"
                :disabled="isSelected(result.id)">
                <span class="text-sm text-gray-900 dark:text-gray-100" x-text="result.title"></span>
                <span class="text-xs px-1.5 py-0.5 rounded" :class="statusColorMap[result.status] || 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" x-text="statusLabelMap[result.status] || result.status"></span>
            </button>
        </template>
    </div>

    {{-- No results --}}
    <div x-show="open && results.length === 0 && !loading" x-cloak class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg p-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">Keine Submissions gefunden.</p>
    </div>

    {{-- Selected submissions --}}
    <div x-show="selected.length > 0" class="flex flex-wrap gap-2 mt-2">
        <template x-for="sub in selected" :key="sub.id">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium" :class="statusColorMap[sub.status] || 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'">
                <span x-text="sub.title"></span>
                <button type="button" @click="removeSubmission(sub.id)" class="hover:text-red-600">&times;</button>
                <input type="hidden" name="{{ $submissionInputName }}" :value="sub.id">
            </span>
        </template>
    </div>
</div>

@php
    $selectedSubsJson = ($selected ?? collect())->map(function ($s) {
        return ['id' => $s->id, 'title' => $s->artist_name . ' — ' . $s->track_title, 'status' => $s->status];
    })->values();
@endphp
<script>
function submissionSearch() {
    return {
        query: '',
        results: [],
        selected: @json($selectedSubsJson),
        open: false,
        loading: false,
        statusLabelMap: @json($subStatusLabels),
        statusColorMap: @json($subStatusColors),

        async search() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ q: this.query });
                const response = await fetch(`{{ route('admin.submissions.search') }}?${params}`);
                this.results = await response.json();
                this.open = true;
            } catch (e) { this.results = []; }
            this.loading = false;
        },

        addSubmission(sub) {
            if (!this.isSelected(sub.id)) this.selected.push(sub);
            this.query = ''; this.results = []; this.open = false;
        },

        removeSubmission(id) { this.selected = this.selected.filter(s => s.id !== id); },
        isSelected(id) { return this.selected.some(s => s.id === id); }
    };
}
</script>
