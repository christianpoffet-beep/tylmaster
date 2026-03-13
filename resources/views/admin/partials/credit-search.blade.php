{{-- Credit search component for artwork forms --}}
{{-- Usage: @include('admin.partials.credit-search', ['role' => 'photographer', 'label' => 'Fotograf:in', 'selected' => $artwork->creditsForRole('photographer')]) --}}

@php
    $selectedJson = ($selected ?? collect())->map(function ($credit) {
        return [
            'id' => $credit->creditable_id,
            'type' => $credit->creditable_type === \App\Models\Contact::class ? 'contact' : 'organization',
            'name' => $credit->display_name,
            'detail' => $credit->creditable_type === \App\Models\Contact::class
                ? ($credit->creditable->company ?? null)
                : ($credit->creditable->type ?? null),
        ];
    })->values();
@endphp

<div x-data="creditSearch_{{ $role }}()" class="relative">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $label }}</label>

    {{-- Search input --}}
    <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="open = results.length > 0"
        placeholder="Kontakt oder Organisation suchen..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">

    {{-- Results dropdown --}}
    <div x-show="open && results.length > 0" @click.away="open = false" x-cloak
        class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
        <template x-for="result in results" :key="result.type + ':' + result.id">
            <button type="button" @click="addCredit(result)"
                class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between border-b border-gray-100 dark:border-gray-700 last:border-0"
                :class="isSelected(result) ? 'opacity-50 cursor-not-allowed' : ''"
                :disabled="isSelected(result)">
                <div class="min-w-0">
                    <span class="text-sm text-gray-900 dark:text-gray-100" x-text="result.name"></span>
                    <span x-show="result.detail" class="text-xs text-gray-400 ml-1" x-text="result.detail"></span>
                </div>
                <span class="text-xs px-1.5 py-0.5 rounded flex-shrink-0 ml-2"
                    :class="result.type === 'contact' ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300'"
                    x-text="result.type === 'contact' ? 'Kontakt' : 'Organisation'"></span>
            </button>
        </template>
    </div>

    {{-- No results + quick create --}}
    <div x-show="open && results.length === 0 && query.length >= 2 && !loading" x-cloak
        class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg p-3">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Keine Ergebnisse gefunden.</p>
        <div class="flex gap-2">
            <button type="button" @click="showCreateContact = true; open = false" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Neuer Kontakt</button>
            <button type="button" @click="showCreateOrg = true; open = false" class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300">+ Neue Organisation</button>
        </div>
    </div>

    {{-- Selected credits as chips --}}
    <div x-show="selected.length > 0" class="flex flex-wrap gap-2 mt-2">
        <template x-for="item in selected" :key="item.type + ':' + item.id">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium"
                :class="item.type === 'contact' ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300'">
                <span x-text="item.name"></span>
                <button type="button" @click="removeCredit(item)" class="hover:text-red-600">&times;</button>
                <input type="hidden" :name="'credits[{{ $role }}][]'" :value="item.type + ':' + item.id">
            </span>
        </template>
    </div>

    {{-- Quick create buttons (when search has results but user wants new) --}}
    <div x-show="!showCreateContact && !showCreateOrg" class="flex gap-3 mt-2">
        <button type="button" @click="showCreateContact = true" class="inline-flex items-center text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
            <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Neuer Kontakt
        </button>
        <button type="button" @click="showCreateOrg = true" class="inline-flex items-center text-xs text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300">
            <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Neue Organisation
        </button>
    </div>

    {{-- Inline create contact --}}
    <div x-show="showCreateContact" x-cloak class="mt-2 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg space-y-2">
        <p class="text-xs font-medium text-green-700 dark:text-green-300">Neuer Kontakt</p>
        <div class="grid grid-cols-2 gap-2">
            <input type="text" x-model="newFirstName" placeholder="Vorname *" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-green-500 focus:ring-green-500">
            <input type="text" x-model="newLastName" placeholder="Nachname *" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-green-500 focus:ring-green-500">
        </div>
        <div x-show="createError" x-cloak class="text-xs text-red-500" x-text="createError"></div>
        <div class="flex gap-2">
            <button type="button" @click="createContact()" :disabled="creating"
                class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 disabled:opacity-50">
                <span x-show="!creating">Erstellen</span>
                <span x-show="creating">Wird erstellt...</span>
            </button>
            <button type="button" @click="showCreateContact = false; newFirstName = ''; newLastName = ''; createError = ''"
                class="px-3 py-1.5 bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm rounded-lg border border-gray-300 dark:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500">Abbrechen</button>
        </div>
    </div>

    {{-- Inline create organization --}}
    <div x-show="showCreateOrg" x-cloak class="mt-2 p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg space-y-2">
        <p class="text-xs font-medium text-purple-700 dark:text-purple-300">Neue Organisation</p>
        <div class="grid grid-cols-2 gap-2">
            <select x-model="newOrgType" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-purple-500 focus:ring-purple-500">
                <option value="">Typ wählen *</option>
                <option value="band">Band</option>
                <option value="label">Label</option>
                <option value="publishing">Publishing</option>
                <option value="venue">Location/Venue</option>
                <option value="event_festival">Veranstalter/Event/Festival</option>
                <option value="media">Media</option>
                <option value="oma">OMA-Kontakt</option>
            </select>
            <input type="text" x-model="newOrgName" placeholder="Name *" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-purple-500 focus:ring-purple-500">
        </div>
        <div x-show="createError" x-cloak class="text-xs text-red-500" x-text="createError"></div>
        <div class="flex gap-2">
            <button type="button" @click="createOrganization()" :disabled="creating"
                class="px-3 py-1.5 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 disabled:opacity-50">
                <span x-show="!creating">Erstellen</span>
                <span x-show="creating">Wird erstellt...</span>
            </button>
            <button type="button" @click="showCreateOrg = false; newOrgType = ''; newOrgName = ''; createError = ''"
                class="px-3 py-1.5 bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm rounded-lg border border-gray-300 dark:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500">Abbrechen</button>
        </div>
    </div>
</div>

<script>
function creditSearch_{{ $role }}() {
    return {
        query: '',
        results: [],
        selected: @json($selectedJson),
        open: false,
        loading: false,

        showCreateContact: false,
        showCreateOrg: false,
        newFirstName: '',
        newLastName: '',
        newOrgType: '',
        newOrgName: '',
        creating: false,
        createError: '',

        async search() {
            if (this.query.length < 2) {
                this.results = [];
                this.open = false;
                return;
            }
            this.loading = true;
            try {
                const response = await fetch(`{{ route('admin.credits.search') }}?q=${encodeURIComponent(this.query)}`);
                this.results = await response.json();
                this.open = true;
            } catch (e) {
                this.results = [];
            }
            this.loading = false;
        },

        addCredit(item) {
            if (!this.isSelected(item)) {
                this.selected.push({...item});
            }
            this.query = '';
            this.results = [];
            this.open = false;
        },

        removeCredit(item) {
            this.selected = this.selected.filter(s => !(s.type === item.type && s.id === item.id));
        },

        isSelected(item) {
            return this.selected.some(s => s.type === item.type && s.id === item.id);
        },

        async createContact() {
            this.createError = '';
            if (!this.newFirstName.trim() || !this.newLastName.trim()) {
                this.createError = 'Vor- und Nachname sind Pflichtfelder.';
                return;
            }
            this.creating = true;
            try {
                const response = await fetch(`{{ route('admin.contacts.storeQuick') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ first_name: this.newFirstName.trim(), last_name: this.newLastName.trim() }),
                });
                if (!response.ok) {
                    const err = await response.json();
                    this.createError = err.message || 'Fehler beim Erstellen.';
                    this.creating = false;
                    return;
                }
                const contact = await response.json();
                this.selected.push(contact);
                this.newFirstName = '';
                this.newLastName = '';
                this.showCreateContact = false;
            } catch (e) {
                this.createError = 'Netzwerkfehler.';
            }
            this.creating = false;
        },

        async createOrganization() {
            this.createError = '';
            if (!this.newOrgType || !this.newOrgName.trim()) {
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
                    body: JSON.stringify({ type: this.newOrgType, name: this.newOrgName.trim() }),
                });
                if (!response.ok) {
                    const err = await response.json();
                    this.createError = err.message || 'Fehler beim Erstellen.';
                    this.creating = false;
                    return;
                }
                const org = await response.json();
                this.selected.push({ id: org.id, type: 'organization', name: org.primary_name, detail: org.type });
                this.newOrgType = '';
                this.newOrgName = '';
                this.showCreateOrg = false;
            } catch (e) {
                this.createError = 'Netzwerkfehler.';
            }
            this.creating = false;
        },
    };
}
</script>
