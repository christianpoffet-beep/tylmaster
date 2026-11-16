{{--
    Party search: one field for organizations, persons and persons within an
    organization ("Red Tape Redemption · Tobias Kalt"). Sits inside the
    parties x-for loop and uses `party` and getOrgContacts() from the form's
    Alpine scope. `party` is handed to every call rather than kept: applying
    a template swaps the party objects under the same loop rows. The data and contractPartyPicker() come from
    contract-party-picker-script, included once per page.
--}}
<div x-data="contractPartyPicker()" class="relative" @click.outside="close()">
    {{-- The chosen party --}}
    <div x-show="hasSelection(party) && !editing" class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/40 px-3 py-2">
        <div class="min-w-0">
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="selection(party).name"></span>
                <span class="text-[10px] px-1.5 py-0.5 rounded flex-shrink-0" :class="badgeClass(selection(party))" x-text="selection(party).badge"></span>
            </div>
            <p x-show="selection(party).detail" class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="selection(party).detail"></p>
        </div>
        <button type="button" @click="start()" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 flex-shrink-0">Ändern</button>
    </div>

    {{-- Search --}}
    <div x-show="!hasSelection(party) || editing">
        <input type="text" x-ref="search" x-model="query" autocomplete="off"
               @focus="open = true" @input="open = true; active = 0"
               @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
               @keydown.enter.prevent="pickActive(party)" @keydown.escape.prevent="close()"
               placeholder="Organisation oder Person suchen …"
               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">

        <div x-show="open" x-cloak x-ref="list" class="absolute z-20 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-72 overflow-y-auto">
            <template x-for="(entry, i) in results" :key="entry.key">
                <button type="button" @mousedown.prevent @click="choose(party, entry)" @mouseenter="active = i" :data-index="i"
                        class="w-full text-left px-3 py-2 flex items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700 last:border-0"
                        :class="i === active ? 'bg-blue-50 dark:bg-blue-900/30' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                    <span class="min-w-0">
                        <span class="block text-sm text-gray-900 dark:text-gray-100 truncate" x-text="entry.name"></span>
                        <span x-show="entry.detail" class="block text-xs text-gray-500 dark:text-gray-400 truncate" x-text="entry.detail"></span>
                    </span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded flex-shrink-0" :class="badgeClass(entry)" x-text="entry.badge"></span>
                </button>
            </template>
            <p x-show="results.length === 0" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">Nichts gefunden.</p>
            <p x-show="query.trim() === '' && results.length > 0" class="px-3 py-1.5 text-[11px] text-gray-400 border-t border-gray-100 dark:border-gray-700">Tippen, um auch Personen innerhalb einer Organisation zu finden, z.&nbsp;B. ein Bandmitglied.</p>
        </div>
    </div>

    {{-- Contact person of an organization --}}
    <div x-show="party.type === 'organization' && party.organization_id && getOrgContacts(party.organization_id).length > 0" class="mt-2">
        <label class="block text-xs text-gray-500 mb-1">Ansprechperson (optional)</label>
        <select x-model="party.contact_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">— Keine Person —</option>
            <template x-for="c in getOrgContacts(party.organization_id)" :key="c.id">
                <option :value="c.id" x-text="c.name"></option>
            </template>
        </select>
    </div>
</div>
