{{-- Product type selector with search and quick-select buttons --}}
{{-- Usage: @include('admin.partials.product-type-selector', ['allProductTypes' => $allProductTypes, 'selectedTypes' => old('product_type', [])]) --}}

@php
    $selectedTypes = $selectedTypes ?? [];
    $quickDefaults = [
        'Digital Album', 'Digital Single', 'Digital EP',
        'CD', 'Vinyl 12"', 'Vinyl 7"', 'Kassette',
        'Album (LP)', 'EP (Extended Play)', 'Single', 'T-Shirt',
    ];
    $allFlat = collect($allProductTypes)->flatten()->toArray();
    $quickDefaults = array_values(array_filter($quickDefaults, fn($t) => in_array($t, $allFlat)));
@endphp

<script>
function productTypeSelector(initial) {
    return {
        search: '',
        selectedTypes: initial,
        toggleType(type) {
            const idx = this.selectedTypes.indexOf(type);
            if (idx > -1) { this.selectedTypes.splice(idx, 1); } else { this.selectedTypes.push(type); }
            this.$nextTick(() => {
                this.$el.querySelectorAll('input[name="product_type[]"]').forEach(cb => {
                    cb.checked = this.selectedTypes.includes(cb.value);
                });
            });
            this.$dispatch('product-types-changed', [...this.selectedTypes]);
        },
        hasType(type) { return this.selectedTypes.includes(type); },
        matchesSearch(type) {
            if (!this.search) return true;
            return type.toLowerCase().includes(this.search.toLowerCase());
        },
        groupVisible(types) {
            return types.some(t => this.matchesSearch(t));
        }
    };
}
</script>

<div x-data="productTypeSelector(@json(array_values($selectedTypes)))">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Typ</label>

    {{-- Quick-Select Buttons --}}
    <div class="flex flex-wrap gap-1.5 mb-3">
        @foreach($quickDefaults as $qd)
            @php $jsQd = Js::from($qd); @endphp
            <button type="button"
                @click="toggleType({{ $jsQd }})"
                class="px-2.5 py-1 rounded-full text-xs font-medium border transition-colors"
                :class="hasType({{ $jsQd }})
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500'">
                {{ $qd }}
            </button>
        @endforeach
    </div>

    {{-- Search --}}
    <input type="text" x-model="search" placeholder="Produkttyp suchen..."
        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 mb-3">

    {{-- Grouped checkboxes (filtered) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($allProductTypes as $group => $types)
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"
                 x-show="groupVisible(@json($types))">
                <div class="bg-gray-50 dark:bg-gray-700/50 px-3 py-2">
                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">{{ $group }}</span>
                </div>
                <div class="max-h-40 overflow-y-auto p-2 space-y-1">
                    @foreach($types as $type)
                        @php $jsType = Js::from($type); @endphp
                        <label class="items-center gap-2 px-2 py-1 rounded hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer"
                               x-show="matchesSearch({{ $jsType }})"
                               :class="matchesSearch({{ $jsType }}) ? 'flex' : 'hidden'">
                            <input type="checkbox" name="product_type[]" value="{{ $type }}"
                                {{ in_array($type, $selectedTypes) ? 'checked' : '' }}
                                x-on:change="toggleType({{ $jsType }})"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 flex-shrink-0">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $type }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Selected summary --}}
    <div x-show="selectedTypes.length > 0" class="flex flex-wrap gap-1.5 mt-3">
        <template x-for="type in selectedTypes" :key="type">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                <span x-text="type"></span>
                <button type="button" @click="toggleType(type)" class="hover:text-red-600">&times;</button>
            </span>
        </template>
    </div>
</div>
