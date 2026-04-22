{{-- Music credits component with role selection --}}
{{-- Usage: @include('admin.partials.music-credits', ['selectedCredits' => $track->contacts ?? collect()]) --}}

@php
    $creditRoles = \App\Models\Setting::creditRoles();
    $instruments = \App\Models\Setting::instruments();

    // Group existing credits by (contact_id, ipi_number). Each group has its own role list.
    $grouped = [];
    foreach (($selectedCredits ?? collect()) as $c) {
        $pivotIpi = $c->pivot->ipi_number ?? null;
        $key = $c->id . '|' . ($pivotIpi ?? '');

        if (!isset($grouped[$key])) {
            $ipis = $c->ipis ?? [];
            $matchedIpi = null;
            if ($pivotIpi !== null && $pivotIpi !== '') {
                $matchedIpi = collect($ipis)->first(fn ($i) => ($i['number'] ?? null) === $pivotIpi);
            }

            $grouped[$key] = [
                'contact_id' => $c->id,
                'contact_name' => $c->full_name,
                'ipi_number' => $pivotIpi,
                'ipi_name' => $matchedIpi['name'] ?? null,
                'display_name' => ($matchedIpi && !empty($matchedIpi['name'])) ? $matchedIpi['name'] : $c->full_name,
                'kind' => ($pivotIpi !== null && $pivotIpi !== '') ? 'ipi' : 'contact',
                'roles' => [],
            ];
        }
        $grouped[$key]['roles'][] = [
            'role' => $c->pivot->role ?? 'instrumentalist',
            'instrument' => $c->pivot->instrument ?? '',
        ];
    }
    $existingCredits = array_values($grouped);
@endphp

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

        isSelected(result) {
            return this.contacts.some(c =>
                c.contact_id === result.contact_id
                && (c.ipi_number || '') === (result.ipi_number || '')
            );
        },

        addFromSearch(result) {
            if (!this.isSelected(result)) {
                this.contacts.push({
                    contact_id: result.contact_id,
                    contact_name: result.kind === 'ipi' ? result.subtitle : result.name,
                    ipi_number: result.ipi_number || null,
                    ipi_name: result.ipi_name || null,
                    display_name: result.name,
                    kind: result.kind || 'contact',
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

        pasteCredits(credits) {
            const grouped = {};
            credits.forEach(c => {
                const ipiNum = c.ipi_number || '';
                const key = c.contact_id + '|' + ipiNum;
                if (!grouped[key]) {
                    grouped[key] = {
                        contact_id: c.contact_id,
                        contact_name: c.contact_name || c.name || '',
                        ipi_number: c.ipi_number || null,
                        ipi_name: c.ipi_name || null,
                        display_name: c.display_name || c.name || c.contact_name || '',
                        kind: c.ipi_number ? 'ipi' : 'contact',
                        roles: [],
                    };
                }
                grouped[key].roles.push({ role: c.role || 'instrumentalist', instrument: c.instrument || '' });
            });
            Object.values(grouped).forEach(newEntry => {
                const existing = this.contacts.find(x =>
                    x.contact_id === newEntry.contact_id
                    && (x.ipi_number || '') === (newEntry.ipi_number || '')
                );
                if (existing) {
                    newEntry.roles.forEach(nr => {
                        const hasRole = existing.roles.some(r => r.role === nr.role && r.instrument === nr.instrument);
                        if (!hasRole) existing.roles = [...existing.roles, nr];
                    });
                } else {
                    this.contacts = [...this.contacts, newEntry];
                }
            });
            this.contacts = [...this.contacts];
        },

        flatIndex(ci, ri) {
            let idx = 0;
            for (let i = 0; i < ci; i++) idx += this.contacts[i].roles.length;
            return idx + ri;
        }
    };
}
</script>

<div x-data="musicCredits()" class="space-y-4" @paste-credits.window="pasteCredits($event.detail)">
    {{-- Credits list grouped by (contact, ipi) --}}
    <div x-show="contacts.length > 0" class="space-y-3">
        <template x-for="(contact, ci) in contacts" :key="ci">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 space-y-2">
                {{-- Header --}}
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="contact.display_name"></span>
                            <template x-if="contact.kind === 'ipi' && contact.ipi_number">
                                <span class="inline-flex items-center gap-1 text-xs" :title="contact.ipi_name ? 'IPI: ' + contact.ipi_name : ''">
                                    <span class="px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-mono">IPI</span>
                                    <span class="font-mono text-gray-700 dark:text-gray-200" x-text="contact.ipi_number"></span>
                                </span>
                            </template>
                        </div>
                        <template x-if="contact.kind === 'ipi' && contact.contact_name && contact.contact_name !== contact.display_name">
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="'Kontakt: ' + contact.contact_name"></span>
                        </template>
                    </div>
                    <button type="button" @click="removeContact(ci)" class="text-red-400 hover:text-red-600 text-xs flex-shrink-0">Entfernen</button>
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
                        <input type="hidden" :name="'credits[' + flatIndex(ci, ri) + '][ipi_number]'" :value="contact.ipi_number || ''">
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- Search to add contact or IPI --}}
    <div class="relative">
        <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(query.length >= 1 || results.length) open = true; else { search(); }"
            placeholder="Kontakt oder IPI-Name suchen..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">

        <div x-show="open && results.length > 0" @click.away="open = false" x-cloak
            class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-72 overflow-y-auto mt-1">
            <template x-for="result in results" :key="result.key">
                <button type="button" @click="addFromSearch(result)"
                    :disabled="isSelected(result)"
                    :class="isSelected(result) ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                    class="w-full text-left px-3 py-2 flex items-center justify-between border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm text-gray-900 dark:text-gray-100" x-text="result.name"></span>
                            <template x-if="result.kind === 'ipi' && result.ipi_number">
                                <span class="inline-flex items-center gap-1 text-xs">
                                    <span class="px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-mono">IPI</span>
                                    <span class="font-mono text-gray-600 dark:text-gray-300" x-text="result.ipi_number"></span>
                                </span>
                            </template>
                        </div>
                        <span x-show="result.subtitle" class="block text-xs text-gray-400 mt-0.5" x-text="result.subtitle"></span>
                    </div>
                    <span class="text-[10px] uppercase tracking-wide flex-shrink-0 ml-2"
                        :class="result.kind === 'ipi' ? 'text-indigo-500' : 'text-gray-400'"
                        x-text="result.kind === 'ipi' ? 'IPI' : 'Kontakt'"></span>
                </button>
            </template>
        </div>

        <div x-show="open && results.length === 0 && query.length >= 1 && !loading" x-cloak
            class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg p-3 mt-1">
            <p class="text-sm text-gray-500 dark:text-gray-400">Keine Kontakte oder IPIs gefunden.</p>
        </div>
    </div>
</div>
