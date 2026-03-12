{{-- Organization search component for contact forms --}}
{{-- Usage: @include('admin.partials.organization-search', ['selected' => $contact->organizations ?? collect()]) --}}

@php
    $orgTypeLabelsSearch = ['band' => 'Band', 'label' => 'Label', 'publishing' => 'Publishing', 'venue' => 'Location/Venue', 'event_festival' => 'Veranstalter/Event/Festival', 'media' => 'Media', 'oma' => 'OMA-Kontakt'];
    $orgTypeColorsSearch = ['band' => 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300', 'label' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300', 'publishing' => 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300', 'venue' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300', 'event_festival' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300', 'media' => 'bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300', 'oma' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'];
@endphp

<div x-data="organizationSearch()" class="relative">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Organisationen</label>

    {{-- Search input --}}
    <div class="flex gap-2 mb-2">
        <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(results.length) open = true; else search()"
            placeholder="Organisation suchen..." class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        <select x-model="typeFilter" @change="search()" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Typen</option>
            @foreach($orgTypeLabelsSearch as $v => $l)
                <option value="{{ $v }}">{{ $l }}</option>
            @endforeach
        </select>
    </div>

    {{-- Results dropdown --}}
    <div x-show="open && results.length > 0" @click.away="open = false" x-cloak
        class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
        <template x-for="result in results" :key="result.id">
            <button type="button" @click="addOrganization(result)"
                class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between border-b border-gray-100 last:border-0"
                :class="isSelected(result.id) ? 'opacity-50 cursor-not-allowed' : ''"
                :disabled="isSelected(result.id)">
                <div>
                    <span class="text-sm text-gray-900 dark:text-gray-100" x-text="result.primary_name"></span>
                    <span x-show="result.all_names !== result.primary_name" class="text-xs text-gray-400 ml-1" x-text="'(' + result.all_names + ')'"></span>
                </div>
                <span class="text-xs px-1.5 py-0.5 rounded" :class="typeColorMap[result.type] || 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" x-text="typeLabelMap[result.type] || result.type"></span>
            </button>
        </template>
    </div>

    {{-- No results --}}
    <div x-show="open && results.length === 0 && !loading" x-cloak class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg p-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">Keine Organisationen gefunden.</p>
    </div>

    {{-- Selected organizations --}}
    <div x-show="selected.length > 0" class="flex flex-wrap gap-2 mt-2">
        <template x-for="org in selected" :key="org.id">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium" :class="typeColorMap[org.type] || 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'">
                <span x-text="org.primary_name"></span>
                <button type="button" @click="removeOrganization(org.id)" class="hover:text-red-600">&times;</button>
                <input type="hidden" name="organization_ids[]" :value="org.id">
            </span>
        </template>
    </div>

    {{-- Quick create toggle --}}
    <button type="button" @click="showCreateForm = !showCreateForm" class="mt-2 inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        <span x-text="showCreateForm ? 'Abbrechen' : 'Neue Organisation erstellen'"></span>
    </button>

    {{-- Inline create form --}}
    <div x-show="showCreateForm" x-cloak class="mt-2 p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <select x-model="newType" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Typ wählen *</option>
                @foreach($orgTypeLabelsSearch as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                @endforeach
            </select>
            <input type="text" x-model="newName" placeholder="Name *" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div x-show="createError" x-cloak class="text-xs text-red-500" x-text="createError"></div>
        <div class="flex gap-2">
            <button type="button" @click="createOrganization()" :disabled="creating"
                class="px-3 py-1.5 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 disabled:opacity-50">
                <span x-show="!creating">Erstellen</span>
                <span x-show="creating">Wird erstellt...</span>
            </button>
            <button type="button" @click="showCreateForm = false; newType = ''; newName = ''; createError = ''"
                class="px-3 py-1.5 bg-white text-gray-700 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</button>
        </div>
    </div>
</div>

@php
    $selectedJson = ($selected ?? collect())->map(function ($o) {
        return ['id' => $o->id, 'primary_name' => $o->primary_name, 'all_names' => $o->all_names, 'type' => $o->type];
    })->values();
@endphp
<script>
function organizationSearch() {
    return {
        query: '',
        typeFilter: '',
        results: [],
        selected: @json($selectedJson),
        open: false,
        loading: false,
        typeLabelMap: @json($orgTypeLabelsSearch),
        typeColorMap: @json($orgTypeColorsSearch),

        showCreateForm: false,
        newType: '',
        newName: '',
        creating: false,
        createError: '',

        async search() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ q: this.query });
                if (this.typeFilter) params.append('type', this.typeFilter);
                const response = await fetch(`{{ route('admin.organizations.search') }}?${params}`);
                this.results = await response.json();
                this.open = true;
            } catch (e) {
                this.results = [];
            }
            this.loading = false;
        },

        async createOrganization() {
            this.createError = '';
            if (!this.newType || !this.newName.trim()) {
                this.createError = 'Typ und Name sind Pflichtfelder.';
                return;
            }
            this.creating = true;
            try {
                const response = await fetch(`{{ route('admin.organizations.storeQuick') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ type: this.newType, name: this.newName.trim() }),
                });
                if (!response.ok) {
                    const err = await response.json();
                    this.createError = err.message || 'Fehler beim Erstellen.';
                    this.creating = false;
                    return;
                }
                const org = await response.json();
                this.selected.push(org);
                this.newType = '';
                this.newName = '';
                this.showCreateForm = false;
            } catch (e) {
                this.createError = 'Netzwerkfehler.';
            }
            this.creating = false;
        },

        addOrganization(org) {
            if (!this.isSelected(org.id)) {
                this.selected.push(org);
            }
            this.query = '';
            this.results = [];
            this.open = false;
        },

        removeOrganization(id) {
            this.selected = this.selected.filter(o => o.id !== id);
        },

        isSelected(id) {
            return this.selected.some(o => o.id === id);
        }
    };
}
</script>
