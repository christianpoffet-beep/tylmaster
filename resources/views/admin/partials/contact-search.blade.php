{{-- Contact search component --}}
{{-- Usage: @include('admin.partials.contact-search', ['selected' => $model->contacts ?? collect(), 'inputName' => 'contact_ids[]']) --}}

@php
    $inputName = $inputName ?? 'contact_ids[]';
    $label = $contactSearchLabel ?? 'Kontakte';
    $componentId = preg_replace('/[^a-zA-Z0-9]/', '', Str::camel($inputName));
@endphp

<div x-data="contactSearch_{{ $componentId }}()" class="relative">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $label }}</label>

    {{-- Search input --}}
    <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(query.length >= 1 || results.length) open = true; else { search(); }"
        placeholder="Kontakt suchen..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 mb-2">

    {{-- Results dropdown --}}
    <div x-show="open && results.length > 0" @click.away="open = false" x-cloak
        class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
        <template x-for="result in results" :key="result.id">
            <button type="button" @click="addContact(result)"
                class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between border-b border-gray-100 last:border-0"
                :class="isSelected(result.id) ? 'opacity-50 cursor-not-allowed' : ''"
                :disabled="isSelected(result.id)">
                <div>
                    <span class="text-sm text-gray-900 dark:text-gray-100" x-text="result.name"></span>
                    <span x-show="result.email" class="text-xs text-gray-400 ml-1" x-text="result.email"></span>
                </div>
            </button>
        </template>
    </div>

    {{-- No results --}}
    <div x-show="open && results.length === 0 && query.length >= 1 && !loading" x-cloak class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg p-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">Keine Kontakte gefunden.</p>
    </div>

    {{-- Selected contacts --}}
    <div x-show="selected.length > 0" class="flex flex-wrap gap-2 mt-2">
        <template x-for="contact in selected" :key="contact.id">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                <span x-text="contact.name"></span>
                <button type="button" @click="removeContact(contact.id)" class="hover:text-red-600">&times;</button>
                <input type="hidden" name="{{ $inputName }}" :value="contact.id">
            </span>
        </template>
    </div>

    {{-- Quick create toggle --}}
    <button type="button" @click="showCreateForm = !showCreateForm" class="mt-2 inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        <span x-text="showCreateForm ? 'Abbrechen' : 'Neuen Kontakt erstellen'"></span>
    </button>

    {{-- Inline create form --}}
    <div x-show="showCreateForm" x-cloak class="mt-2 p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <input type="text" x-model="newFirstName" placeholder="Vorname *" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <input type="text" x-model="newLastName" placeholder="Nachname *" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div x-show="createError" x-cloak class="text-xs text-red-500" x-text="createError"></div>
        <div class="flex gap-2">
            <button type="button" @click="createContact()" :disabled="creating"
                class="px-3 py-1.5 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 disabled:opacity-50">
                <span x-show="!creating">Erstellen</span>
                <span x-show="creating">Wird erstellt...</span>
            </button>
            <button type="button" @click="showCreateForm = false; newFirstName = ''; newLastName = ''; createError = ''"
                class="px-3 py-1.5 bg-white text-gray-700 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</button>
        </div>
    </div>
</div>

@php
    $selectedContactsJson = ($selected ?? collect())->map(function ($c) {
        return ['id' => $c->id, 'name' => $c->full_name, 'email' => $c->email];
    })->values();
@endphp
<script>
function contactSearch_{{ $componentId }}() {
    return {
        query: '',
        results: [],
        selected: @json($selectedContactsJson),
        open: false,
        loading: false,

        showCreateForm: false,
        newFirstName: '',
        newLastName: '',
        creating: false,
        createError: '',

        async search() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ q: this.query });
                const response = await fetch(`{{ route('admin.contacts.search') }}?${params}`);
                this.results = await response.json();
                this.open = true;
            } catch (e) {
                this.results = [];
            }
            this.loading = false;
        },

        async createContact() {
            this.createError = '';
            if (!this.newFirstName.trim() || !this.newLastName.trim()) {
                this.createError = 'Vorname und Nachname sind Pflichtfelder.';
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
                this.showCreateForm = false;
            } catch (e) {
                this.createError = 'Netzwerkfehler.';
            }
            this.creating = false;
        },

        addContact(contact) {
            if (!this.isSelected(contact.id)) {
                this.selected.push(contact);
            }
            this.query = '';
            this.results = [];
            this.open = false;
        },

        removeContact(id) {
            this.selected = this.selected.filter(c => c.id !== id);
        },

        isSelected(id) {
            return this.selected.some(c => c.id === id);
        }
    };
}
</script>
