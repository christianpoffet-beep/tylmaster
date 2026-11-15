{{--
    A filter that takes several values, for GET filter forms: a button with
    the choice so far, a dropdown with checkboxes (searchable when long).
    $name:     query parameter, sent as name[]
    $label:    e.g. "Band"
    $plural:   e.g. "Bands", for "2 Bands"
    $options:  [['value' => ..., 'label' => ...], ...]
    $selected: the chosen values

    The checkboxes are real form fields, so the form also works without
    Alpine. Changes are applied with "Anwenden" or the form's own button;
    the form hears about pending changes through "filters-changed".
--}}
@php
    $selected = array_map('strval', (array) ($selected ?? []));
    $chosenLabels = collect($options)->whereIn('value', $selected)->pluck('label');
    $summary = match ($chosenLabels->count()) {
        0 => 'Alle',
        1 => $chosenLabels->first(),
        default => $chosenLabels->count() . ' ' . $plural,
    };
@endphp
<div x-data="filterMultiselect(@js(['selected' => $selected, 'labels' => collect($options)->pluck('label', 'value'), 'plural' => $plural]))"
     @click.outside="open = false" @keydown.escape="open = false" class="relative">
    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $label }}</label>
    <button type="button" @click="open = !open; if (open) $nextTick(() => $refs.search?.focus())"
            class="w-full sm:w-44 flex items-center gap-2 rounded-lg border px-3 py-2 text-sm text-left bg-white dark:bg-gray-700"
            :class="selected.length ? 'border-blue-400 dark:border-blue-500 text-gray-900 dark:text-gray-100' : 'border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300'"
            @if(empty($options)) disabled title="Noch keine {{ $plural }} auf Produkten" @endif>
        <span class="flex-1 truncate" x-text="summary">{{ $summary }}</span>
        <svg class="w-4 h-4 flex-shrink-0 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div x-show="open" x-cloak class="absolute z-30 mt-1 w-64 max-w-[calc(100vw-2rem)] rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg">
        @if(count($options) > 8)
            <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                <input type="search" x-ref="search" x-model="search" placeholder="{{ $label }} suchen …" @keydown.enter.prevent
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm py-1.5 focus:border-blue-500 focus:ring-blue-500">
            </div>
        @endif
        <div class="max-h-64 overflow-y-auto py-1">
            @foreach($options as $option)
                <label x-show="matches(@js($option['label']))" class="flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                    <input type="checkbox" name="{{ $name }}[]" value="{{ $option['value'] }}" x-model="selected" @change="$dispatch('filters-changed')"
                           @checked(in_array((string) $option['value'], $selected, true))
                           class="rounded border-gray-300 dark:border-gray-500 text-blue-600 focus:ring-blue-500">
                    <span class="truncate">{{ $option['label'] }}</span>
                </label>
            @endforeach
        </div>
        <div class="flex items-center justify-between gap-2 px-3 py-2 border-t border-gray-100 dark:border-gray-700">
            <button type="button" @click="selected = []; $dispatch('filters-changed')" x-show="selected.length" class="text-xs text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">Auswahl leeren</button>
            <button type="submit" class="ml-auto px-3 py-1 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium">Anwenden</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function filterMultiselect(config) {
    return {
        open: false,
        search: '',
        selected: config.selected,
        get summary() {
            if (!this.selected.length) return 'Alle';
            if (this.selected.length === 1) return config.labels[this.selected[0]] ?? '1 ' + config.plural;
            return this.selected.length + ' ' + config.plural;
        },
        matches(label) {
            return !this.search || label.toLowerCase().includes(this.search.trim().toLowerCase());
        },
    };
}
</script>
@endpush
@endonce
