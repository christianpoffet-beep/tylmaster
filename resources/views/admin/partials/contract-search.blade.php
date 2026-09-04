{{-- Contract search component --}}
{{-- Usage: @include('admin.partials.contract-search', ['selected' => $model->contracts ?? collect()]) --}}

@php
    $contractInputName = $contractInputName ?? 'contract_ids[]';
    $contractStatusLabels = ['draft' => 'Entwurf', 'active' => 'Aktiv', 'expired' => 'Abgelaufen', 'terminated' => 'Beendet'];
    $contractStatusColors = ['draft' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300', 'active' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300', 'expired' => 'bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300', 'terminated' => 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300'];
@endphp

<div x-data="contractSearch()" class="relative" @paste-contracts.window="$event.detail.forEach(c => { if (!isSelected(c.id)) selected.push(c); })">
    {{-- Marker: set by Alpine, so a section that never rendered cannot trigger a sync that wipes existing links --}}
    <input type="hidden" name="{{ str_replace('[]', '', $contractInputName) }}_submitted" value="" x-bind:value="'1'">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Verträge</label>

    <div class="flex gap-2 mb-2">
        <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(query.length >= 1 || results.length) open = true; else { search(); }"
            placeholder="Titel oder Vertragsnummer suchen..." class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        <select x-model="statusFilter" @change="search()" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Status</option>
            <option value="draft">Entwurf</option>
            <option value="active">Aktiv</option>
            <option value="expired">Abgelaufen</option>
            <option value="terminated">Beendet</option>
        </select>
    </div>

    {{-- Results dropdown --}}
    <div x-show="open && results.length > 0" @click.away="open = false" x-cloak
        class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
        <template x-for="result in results" :key="result.id">
            <button type="button" @click="addContract(result)"
                class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between border-b border-gray-100 dark:border-gray-700 last:border-0"
                :class="isSelected(result.id) ? 'opacity-50 cursor-not-allowed' : ''"
                :disabled="isSelected(result.id)">
                <div>
                    <span class="text-sm text-gray-900 dark:text-gray-100" x-text="result.title"></span>
                    <span x-show="result.contract_number" class="text-xs text-gray-400 ml-1" x-text="result.contract_number"></span>
                </div>
                <span class="text-xs px-1.5 py-0.5 rounded" :class="statusColorMap[result.status] || 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" x-text="statusLabelMap[result.status] || result.status"></span>
            </button>
        </template>
    </div>

    {{-- No results --}}
    <div x-show="open && results.length === 0 && !loading" x-cloak class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg p-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">Keine Verträge gefunden.</p>
    </div>

    {{-- Selected contracts --}}
    <div x-show="selected.length > 0" class="flex flex-wrap gap-2 mt-2">
        <template x-for="contract in selected" :key="contract.id">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium" :class="statusColorMap[contract.status] || 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'">
                <span x-text="contract.title"></span>
                <span x-show="contract.contract_number" class="opacity-60" x-text="contract.contract_number"></span>
                <button type="button" @click="removeContract(contract.id)" class="hover:text-red-600">&times;</button>
                <input type="hidden" name="{{ $contractInputName }}" :value="contract.id">
            </span>
        </template>
    </div>
</div>

@php
    $selectedContractsJson = ($selected ?? collect())->map(function ($c) {
        return ['id' => $c->id, 'title' => $c->title, 'contract_number' => $c->contract_number, 'status' => $c->status];
    })->values();
@endphp
<script>
function contractSearch() {
    return {
        query: '',
        statusFilter: '',
        results: [],
        selected: @json($selectedContractsJson),
        open: false,
        loading: false,
        statusLabelMap: @json($contractStatusLabels),
        statusColorMap: @json($contractStatusColors),

        async search() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ q: this.query });
                if (this.statusFilter) params.append('status', this.statusFilter);
                const response = await fetch(`{{ route('admin.contracts.search') }}?${params}`);
                this.results = await response.json();
                this.open = true;
            } catch (e) {
                this.results = [];
            }
            this.loading = false;
        },

        addContract(contract) {
            if (!this.isSelected(contract.id)) {
                this.selected.push(contract);
            }
            this.query = '';
            this.results = [];
            this.open = false;
        },

        removeContract(id) {
            this.selected = this.selected.filter(c => c.id !== id);
        },

        isSelected(id) {
            return this.selected.some(c => c.id === id);
        }
    };
}
</script>
