{{-- Project search component --}}
{{-- Usage: @include('admin.partials.project-search', ['selected' => $model->projects ?? collect()]) --}}

@php
    $projectInputName = $projectInputName ?? 'project_ids[]';
    $statusLabels = ['planned' => 'Geplant', 'in_progress' => 'In Arbeit', 'completed' => 'Abgeschlossen', 'paused' => 'Pausiert'];
    $statusColors = ['planned' => 'bg-gray-100 text-gray-700', 'in_progress' => 'bg-blue-100 text-blue-700', 'completed' => 'bg-green-100 text-green-700', 'paused' => 'bg-yellow-100 text-yellow-700'];
@endphp

<div x-data="projectSearch()" class="relative">
    <label class="block text-sm font-medium text-gray-700 mb-1">Projekte</label>

    <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(query.length >= 1 || results.length) open = true; else { search(); }"
        placeholder="Projekt suchen..." class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 mb-2">

    {{-- Results dropdown --}}
    <div x-show="open && results.length > 0" @click.away="open = false" x-cloak
        class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
        <template x-for="result in results" :key="result.id">
            <button type="button" @click="addProject(result)"
                class="w-full text-left px-3 py-2 hover:bg-gray-50 flex items-center justify-between border-b border-gray-100 last:border-0"
                :class="isSelected(result.id) ? 'opacity-50 cursor-not-allowed' : ''"
                :disabled="isSelected(result.id)">
                <span class="text-sm text-gray-900" x-text="result.name"></span>
                <span class="text-xs px-1.5 py-0.5 rounded" :class="statusColorMap[result.status] || 'bg-gray-100 text-gray-600'" x-text="statusLabelMap[result.status] || result.status"></span>
            </button>
        </template>
    </div>

    {{-- No results --}}
    <div x-show="open && results.length === 0 && query.length >= 1 && !loading" x-cloak class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg p-3">
        <p class="text-sm text-gray-500">Keine Projekte gefunden.</p>
    </div>

    {{-- Selected projects --}}
    <div x-show="selected.length > 0" class="flex flex-wrap gap-2 mt-2">
        <template x-for="project in selected" :key="project.id">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium" :class="statusColorMap[project.status] || 'bg-gray-100 text-gray-600'">
                <span x-text="project.name"></span>
                <button type="button" @click="removeProject(project.id)" class="hover:text-red-600">&times;</button>
                <input type="hidden" name="{{ $projectInputName }}" :value="project.id">
            </span>
        </template>
    </div>
</div>

@php
    $selectedProjectsJson = ($selected ?? collect())->map(function ($p) {
        return ['id' => $p->id, 'name' => $p->name, 'status' => $p->status];
    })->values();
@endphp
<script>
function projectSearch() {
    return {
        query: '',
        results: [],
        selected: @json($selectedProjectsJson),
        open: false,
        loading: false,
        statusLabelMap: @json($statusLabels),
        statusColorMap: @json($statusColors),

        async search() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ q: this.query });
                const response = await fetch(`{{ route('admin.projects.search') }}?${params}`);
                this.results = await response.json();
                this.open = true;
            } catch (e) {
                this.results = [];
            }
            this.loading = false;
        },

        addProject(project) {
            if (!this.isSelected(project.id)) {
                this.selected.push(project);
            }
            this.query = '';
            this.results = [];
            this.open = false;
        },

        removeProject(id) {
            this.selected = this.selected.filter(p => p.id !== id);
        },

        isSelected(id) {
            return this.selected.some(p => p.id === id);
        }
    };
}
</script>
