{{-- Music credits component with role selection --}}
{{-- Usage: @include('admin.partials.music-credits', ['selectedCredits' => $track->contacts ?? collect()]) --}}

@php
    $creditRoles = \App\Models\Setting::creditRoles();
    $instruments = \App\Models\Setting::instruments();

    // Group existing credits by contact, each contact has array of roles
    $grouped = [];
    foreach (($selectedCredits ?? collect()) as $c) {
        $cid = $c->id;
        if (!isset($grouped[$cid])) {
            $grouped[$cid] = [
                'contact_id' => $c->id,
                'name' => $c->full_name,
                'email' => $c->email,
                'roles' => [],
            ];
        }
        $grouped[$cid]['roles'][] = [
            'role' => $c->pivot->role ?? 'instrumentalist',
            'instrument' => $c->pivot->instrument ?? '',
        ];
    }
    $existingCredits = array_values($grouped);
@endphp

<div x-data="musicCredits()" class="space-y-4">
    {{-- Credits list grouped by contact --}}
    <div x-show="contacts.length > 0" class="space-y-3">
        <template x-for="(contact, ci) in contacts" :key="ci">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 space-y-2">
                {{-- Contact header --}}
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="contact.name"></span>
                    <button type="button" @click="removeContact(ci)" class="text-red-400 hover:text-red-600 text-xs">Entfernen</button>
                </div>

                {{-- Roles list --}}
                <template x-for="(entry, ri) in contact.roles" :key="ri">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <select x-model="entry.role" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach($creditRoles as $group => $roles)
                                    <optgroup label="{{ $group }}">
                                        @foreach($roles as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <button type="button" x-show="contact.roles.length > 1" @click="removeRole(ci, ri)" class="text-red-500 hover:text-red-700 flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        {{-- Instrument dropdown --}}
                        <div x-show="entry.role === 'instrumentalist'" x-transition>
                            <select x-model="entry.instrument" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Instrument wählen...</option>
                                @foreach($instruments as $group => $items)
                                    <optgroup label="{{ $group }}">
                                        @foreach($items as $item)
                                            <option value="{{ $item }}">{{ $item }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </template>

                {{-- Add role button --}}
                <button type="button" @click="addRole(ci)" class="inline-flex items-center text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mt-1">
                    <svg class="w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Rolle hinzufügen
                </button>

                {{-- Hidden inputs --}}
                <template x-for="(entry, ri) in contact.roles" :key="'h' + ri">
                    <div>
                        <input type="hidden" :name="'credits[' + flatIndex(ci, ri) + '][contact_id]'" :value="contact.contact_id">
                        <input type="hidden" :name="'credits[' + flatIndex(ci, ri) + '][role]'" :value="entry.role">
                        <input type="hidden" :name="'credits[' + flatIndex(ci, ri) + '][instrument]'" :value="entry.role === 'instrumentalist' ? entry.instrument : ''">
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- Search to add contact --}}
    <div class="relative">
        <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(query.length >= 1 || results.length) open = true; else { search(); }"
            placeholder="Kontakt suchen..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">

        <div x-show="open && results.length > 0" @click.away="open = false" x-cloak
            class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto mt-1">
            <template x-for="result in results" :key="result.id">
                <button type="button" @click="addContact(result)"
                    class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <div>
                        <span class="text-sm text-gray-900 dark:text-gray-100" x-text="result.name"></span>
                        <span x-show="result.email" class="text-xs text-gray-400 ml-1" x-text="result.email"></span>
                    </div>
                </button>
            </template>
        </div>

        <div x-show="open && results.length === 0 && query.length >= 1 && !loading" x-cloak
            class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg p-3 mt-1">
            <p class="text-sm text-gray-500 dark:text-gray-400">Keine Kontakte gefunden.</p>
        </div>
    </div>
</div>

<script>
function musicCredits() {
    return {
        contacts: @json($existingCredits),
        query: '',
        results: [],
        open: false,
        loading: false,

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

        addContact(contact) {
            // Check if contact already exists, if so just scroll to it
            const existing = this.contacts.find(c => c.contact_id === contact.id);
            if (existing) {
                existing.roles.push({ role: 'instrumentalist', instrument: '' });
            } else {
                this.contacts.push({
                    contact_id: contact.id,
                    name: contact.name,
                    email: contact.email,
                    roles: [{ role: 'instrumentalist', instrument: '' }],
                });
            }
            this.query = '';
            this.results = [];
            this.open = false;
        },

        addRole(ci) {
            this.contacts[ci].roles.push({ role: 'instrumentalist', instrument: '' });
        },

        removeRole(ci, ri) {
            this.contacts[ci].roles.splice(ri, 1);
        },

        removeContact(ci) {
            this.contacts.splice(ci, 1);
        },

        flatIndex(ci, ri) {
            let idx = 0;
            for (let i = 0; i < ci; i++) {
                idx += this.contacts[i].roles.length;
            }
            return idx + ri;
        }
    };
}
</script>
