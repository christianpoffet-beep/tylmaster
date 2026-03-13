{{-- Postal code input with auto-fill city --}}
{{-- Usage: @include('admin.partials.postal-code-input', [
    'zipName' => 'zip', 'cityName' => 'city',
    'zipValue' => old('zip'), 'cityValue' => old('city'),
    'zipLabel' => 'PLZ', 'cityLabel' => 'Ort',
    'zipId' => null, 'cityId' => null,
]) --}}

@php
    $zipName = $zipName ?? 'zip';
    $cityName = $cityName ?? 'city';
    $zipValue = $zipValue ?? '';
    $cityValue = $cityValue ?? '';
    $zipLabel = $zipLabel ?? 'PLZ';
    $cityLabel = $cityLabel ?? 'Ort';
    $zipId = $zipId ?? $zipName;
    $cityId = $cityId ?? $cityName;
    $componentId = 'plz_' . str_replace(['[', ']', '.'], '_', $zipName);
@endphp

<div x-data="postalCodeLookup_{{ $componentId }}()" class="contents">
    <div>
        <label for="{{ $zipId }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $zipLabel }}</label>
        <div class="relative">
            <input type="text" name="{{ $zipName }}" id="{{ $zipId }}" x-model="zip"
                @input.debounce.300ms="lookup()" @focus="if(suggestions.length) showSuggestions = true"
                value="{{ $zipValue }}"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <div x-show="showSuggestions && suggestions.length > 1" @click.away="showSuggestions = false" x-cloak
                class="absolute z-20 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-40 overflow-y-auto mt-1">
                <template x-for="s in suggestions" :key="s.zip">
                    <button type="button" @click="selectSuggestion(s)"
                        class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 text-sm border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <span class="font-medium text-gray-700 dark:text-gray-300" x-text="s.zip"></span>
                        <span class="text-gray-500 dark:text-gray-400" x-text="s.city"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
    <div>
        <label for="{{ $cityId }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $cityLabel }}</label>
        <div class="relative">
            <input type="text" name="{{ $cityName }}" id="{{ $cityId }}" x-model="city"
                @input.debounce.300ms="lookupByCity()" @focus="if(citySuggestions.length) showCitySuggestions = true"
                value="{{ $cityValue }}"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <div x-show="showCitySuggestions && citySuggestions.length > 0" @click.away="showCitySuggestions = false" x-cloak
                class="absolute z-20 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-40 overflow-y-auto mt-1">
                <template x-for="s in citySuggestions" :key="s.zip">
                    <button type="button" @click="selectSuggestion(s); showCitySuggestions = false"
                        class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 text-sm border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <span class="font-medium text-gray-700 dark:text-gray-300" x-text="s.zip"></span>
                        <span class="text-gray-500 dark:text-gray-400" x-text="s.city"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function postalCodeLookup_{{ $componentId }}() {
    return {
        zip: '{{ addslashes($zipValue) }}',
        city: '{{ addslashes($cityValue) }}',
        suggestions: [],
        showSuggestions: false,
        citySuggestions: [],
        showCitySuggestions: false,

        async lookup() {
            if (this.zip.length < 4) {
                this.suggestions = [];
                this.showSuggestions = false;
                return;
            }
            try {
                const response = await fetch(`{{ route('admin.postal-codes.lookup') }}?zip=${encodeURIComponent(this.zip)}`);
                this.suggestions = await response.json();
                if (this.suggestions.length === 1 && this.suggestions[0].zip === this.zip) {
                    this.city = this.suggestions[0].city;
                    this.showSuggestions = false;
                } else if (this.suggestions.length > 1) {
                    const exact = this.suggestions.find(s => s.zip === this.zip);
                    if (exact) {
                        this.city = exact.city;
                    }
                    this.showSuggestions = true;
                } else {
                    this.showSuggestions = false;
                }
            } catch (e) {
                this.suggestions = [];
            }
        },

        async lookupByCity() {
            if (this.city.length < 2) {
                this.citySuggestions = [];
                this.showCitySuggestions = false;
                return;
            }
            try {
                const response = await fetch(`{{ route('admin.postal-codes.lookup') }}?city=${encodeURIComponent(this.city)}`);
                this.citySuggestions = await response.json();
                if (this.citySuggestions.length === 1) {
                    this.zip = this.citySuggestions[0].zip;
                    this.city = this.citySuggestions[0].city;
                    this.showCitySuggestions = false;
                } else if (this.citySuggestions.length > 1) {
                    this.showCitySuggestions = true;
                } else {
                    this.showCitySuggestions = false;
                }
            } catch (e) {
                this.citySuggestions = [];
            }
        },

        selectSuggestion(s) {
            this.zip = s.zip;
            this.city = s.city;
            this.showSuggestions = false;
        }
    };
}
</script>
