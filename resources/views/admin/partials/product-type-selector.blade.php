{{--
    Product type field: the chosen types as chips, a type-ahead list grouped
    like the settings, and quick picks for the types used most.
    $allProductTypes: groups from Setting::productTypes()
    $selectedTypes:   the current types

    The inputs are built by Alpine, so the section reports in with a marker;
    without it the controller leaves the types alone.
--}}
@php
    $productTypeConfig = [
        'groups' => collect($allProductTypes)->map(fn ($types, $group) => ['name' => $group, 'types' => array_values($types)])->values()->all(),
        'selected' => array_values(array_filter((array) ($selectedTypes ?? []), fn ($type) => filled($type))),
        'frequent' => \App\Models\Release::frequentProductTypes(),
    ];
@endphp

<div x-data="productTypeSelector(@js($productTypeConfig))" @click.outside="open = false" @keydown.escape="open = false" class="relative">
    <label for="product_type_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Typ</label>

    <div @click="$refs.search.focus(); open = true"
         class="flex flex-wrap items-center gap-1.5 min-h-[42px] w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 cursor-text focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500">
        <template x-for="type in selected" :key="type">
            <span class="inline-flex items-center gap-1 pl-2.5 pr-1 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                <span x-text="type"></span>
                <button type="button" @click.stop="toggle(type)" class="w-4 h-4 inline-flex items-center justify-center rounded-full hover:bg-blue-200 dark:hover:bg-blue-800" :aria-label="'Typ ' + type + ' entfernen'">&times;</button>
            </span>
        </template>
        <input type="text" id="product_type_search" x-ref="search" x-model="search" autocomplete="off" role="combobox" :aria-expanded="open"
               @focus="open = true" @input="open = true; active = 0"
               @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
               @keydown.enter.prevent="pickActive()" @keydown.backspace="removeLastIfEmpty()" @keydown.tab="open = false"
               :placeholder="selected.length ? 'Weiteren Typ hinzufügen …' : 'Typ suchen oder aus der Liste wählen …'"
               class="flex-1 min-w-[12rem] border-0 bg-transparent px-1 py-1 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-0">
        <button type="button" @click.stop="open = !open; if (open) $refs.search.focus()" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="Alle Typen anzeigen">
            <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
    </div>

    {{-- All types, grouped and filtered by what is typed --}}
    <div x-show="open" x-cloak x-ref="list" role="listbox" aria-multiselectable="true"
         class="absolute z-30 mt-1 w-full max-h-80 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg py-1">
        <template x-for="group in filteredGroups" :key="group.name">
            <div>
                <div class="px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500" x-text="group.name"></div>
                <template x-for="item in group.items" :key="item.type">
                    <button type="button" role="option" :aria-selected="isSelected(item.type)" :data-active="active === item.index"
                            @click="toggle(item.type); clearSearch()" @mouseenter="active = item.index"
                            :class="active === item.index ? 'bg-blue-50 dark:bg-gray-700' : ''"
                            class="w-full flex items-center gap-2 px-3 py-1.5 text-left text-sm text-gray-700 dark:text-gray-200">
                        <span class="w-4 h-4 flex-shrink-0 rounded border flex items-center justify-center"
                              :class="isSelected(item.type) ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 dark:border-gray-500'">
                            <svg x-show="isSelected(item.type)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <span x-text="item.type"></span>
                    </button>
                </template>
            </div>
        </template>
        <p x-show="!flat.length" class="px-3 py-2 text-sm text-gray-400">
            Kein Typ gefunden.
            @if(Route::has('admin.settings.product-types'))
                <a href="{{ route('admin.settings.product-types') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">Typen verwalten</a>
            @endif
        </p>
    </div>

    {{-- Quick picks --}}
    <div x-show="frequent.length" class="flex flex-wrap items-center gap-1.5 mt-2">
        <span class="text-xs text-gray-400 dark:text-gray-500 mr-0.5">Häufig:</span>
        <template x-for="type in frequent" :key="'quick-' + type">
            <button type="button" @click="toggle(type)" x-text="type"
                    class="px-2.5 py-0.5 rounded-full text-xs font-medium border transition-colors"
                    :class="isSelected(type)
                        ? 'bg-blue-600 text-white border-blue-600'
                        : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500'"></button>
        </template>
    </div>

    <input type="hidden" name="product_type_submitted" x-bind:value="'1'">
    <template x-for="type in selected" :key="'input-' + type">
        <input type="hidden" name="product_type[]" :value="type">
    </template>
</div>

@once
@push('scripts')
<script>
function productTypeSelector(config) {
    return {
        groups: config.groups,
        selected: [...config.selected],
        frequent: config.frequent,
        search: '',
        open: false,
        active: 0,

        get filteredGroups() {
            const query = this.search.trim().toLowerCase();
            let index = 0;
            return this.groups
                .map(group => ({
                    name: group.name,
                    items: group.types
                        .filter(type => !query || type.toLowerCase().includes(query))
                        .map(type => ({ type, index: index++ })),
                }))
                .filter(group => group.items.length);
        },
        get flat() { return this.filteredGroups.flatMap(group => group.items); },

        isSelected(type) { return this.selected.includes(type); },
        toggle(type) {
            const index = this.selected.indexOf(type);
            index > -1 ? this.selected.splice(index, 1) : this.selected.push(type);
            this.$dispatch('product-types-changed', [...this.selected]);
        },
        clearSearch() {
            if (!this.search) return;
            this.search = '';
            this.active = 0;
            this.$refs.search.focus();
        },
        move(step) {
            if (!this.open) { this.open = true; return; }
            const count = this.flat.length;
            if (!count) return;
            this.active = (this.active + step + count) % count;
            this.$nextTick(() => this.$refs.list.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' }));
        },
        pickActive() {
            if (!this.open) { this.open = true; return; }
            const item = this.flat[this.active] ?? this.flat[0];
            if (!item) return;
            this.toggle(item.type);
            this.clearSearch();
        },
        removeLastIfEmpty() {
            if (this.search || !this.selected.length) return;
            this.toggle(this.selected[this.selected.length - 1]);
        },
    };
}
</script>
@endpush
@endonce
