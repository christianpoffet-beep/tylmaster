@extends('admin.layouts.app')

@section('title', 'Produkt bearbeiten')

@php
    $allProductTypes = \App\Models\Setting::productTypes();
    $musicGroups = [];
    $merchGroups = [];
    foreach ($allProductTypes as $group => $types) {
        if (str_contains(strtolower($group), 'merchan')) {
            $merchGroups[$group] = $types;
        } else {
            $musicGroups[$group] = $types;
        }
    }
    $allMusicTypes = collect($musicGroups)->flatten()->toArray();
    $allMerchTypes = collect($merchGroups)->flatten()->toArray();
    $currentTypes = old('product_type', is_array($release->product_type) ? $release->product_type : ($release->product_type ? [$release->product_type] : []));
@endphp

@section('content')
<div class="max-w-4xl" x-data="{
    selectedTypes: @json($currentTypes),
    musicTypes: @json($allMusicTypes),
    merchTypes: @json($allMerchTypes),
    get isMusic() { return this.selectedTypes.some(t => this.musicTypes.includes(t)); },
    get isMerch() { return this.selectedTypes.some(t => this.merchTypes.includes(t)); }
}" @product-types-changed.window="selectedTypes = $event.detail">
    <form method="POST" action="{{ route('admin.releases.update', $release) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Grunddaten --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Grunddaten</h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titel *</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $release->title) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="title_language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sprache des Titels</label>
                    <select name="title_language" id="title_language" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">--</option>
                        @foreach(['de' => 'DE', 'en' => 'EN', 'fr' => 'FR', 'it' => 'IT', 'es' => 'ES'] as $code => $label)
                            <option value="{{ $code }}" {{ old('title_language', $release->title_language) === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Produkt-Typ --}}
            @include('admin.partials.product-type-selector', ['allProductTypes' => $allProductTypes, 'selectedTypes' => $currentTypes])

            {{-- Gemeinsame Felder --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="release_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Datum der Erstveröffentlichung</label>
                    <input type="date" name="release_date" id="release_date" value="{{ old('release_date', $release->release_date?->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="ean_upc" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">EAN / UPC</label>
                    <input type="text" name="ean_upc" id="ean_upc" value="{{ old('ean_upc', $release->ean_upc) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            {{-- Katalognummer (immer sichtbar) --}}
            <div>
                <label for="catalog_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Katalognummer</label>
                <div x-data="catalogGenerator()" class="flex gap-2 items-start">
                    <select x-model="selectedCatalog" @change="generate()" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Katalog...</option>
                        <template x-for="cat in catalogs" :key="cat.prefix">
                            <option :value="cat.prefix" x-text="cat.prefix + ' (' + cat.label + ')'"></option>
                        </template>
                    </select>
                    <input type="text" name="catalog_number" id="catalog_number" x-model="catalogNumber" placeholder="z.B. TYL001" class="flex-1 sm:max-w-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                    <button type="button" @click="generate()" x-show="selectedCatalog" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 flex-shrink-0" title="Nächste freie Nummer generieren">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                </div>
            </div>

            {{-- Musik-spezifische Felder --}}
            <div x-show="isMusic" x-transition x-cloak class="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide">Musik-Felder</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="main_artist" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hauptkünstler</label>
                        <input type="text" name="main_artist" id="main_artist" value="{{ old('main_artist', $release->main_artist) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="label_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Label Name</label>
                        <input type="text" name="label_name" id="label_name" value="{{ old('label_name', $release->label_name) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            {{-- Release Info --}}
            <div>
                <label for="release_info" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Release Info</label>
                <textarea name="release_info" id="release_info" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('release_info', $release->release_info) }}</textarea>
            </div>
            <div>
                <label for="release_info_internal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Release Info (intern)</label>
                <textarea name="release_info_internal" id="release_info_internal" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('release_info_internal', $release->release_info_internal) }}</textarea>
            </div>

            {{-- Grösse, Gewicht, Preis --}}
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Grösse & Preis</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <label for="width" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Breite (cm)</label>
                        <input type="number" name="width" id="width" value="{{ old('width', $release->width) }}" step="0.01" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="height" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Höhe (cm)</label>
                        <input type="number" name="height" id="height" value="{{ old('height', $release->height) }}" step="0.01" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="depth" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tiefe (cm)</label>
                        <input type="number" name="depth" id="depth" value="{{ old('depth', $release->depth) }}" step="0.01" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gewicht (g)</label>
                        <input type="number" name="weight" id="weight" value="{{ old('weight', $release->weight) }}" step="0.01" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Preis</label>
                        <input type="number" name="price" id="price" value="{{ old('price', $release->price) }}" step="0.01" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Währung</label>
                        <select name="currency" id="currency" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach(['CHF' => 'CHF', 'EUR' => 'EUR', 'USD' => 'USD', 'GBP' => 'GBP'] as $code => $label)
                                <option value="{{ $code }}" {{ old('currency', $release->currency ?? 'CHF') === $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Anzahl an Lager</label>
                        <input type="number" name="stock" id="stock" value="{{ old('stock', $release->stock) }}" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </div>

        {{-- Musik: Tracklist --}}
        @php
            $selectedTrackIds = old('track_ids', $release->tracks->pluck('id')->toArray());
            $trackNumbers = [];
            foreach ($release->tracks as $t) {
                $trackNumbers[$t->id] = $t->pivot->track_number;
            }
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6" x-data="{ trackFilter: '' }">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Tracklist</h3>
            @if($allTracks->count())
                <input type="text" x-model="trackFilter" placeholder="Titel, Alternativtitel, ISRC, Band, Label, Verlag oder Credit suchen..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 mb-3">
                <div class="max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($allTracks as $track)
                        <label class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer"
                            x-show="!trackFilter || @js($track->search_haystack).includes(trackFilter.toLowerCase())" x-cloak>
                            <input type="checkbox" name="track_ids[]" value="{{ $track->id }}"
                                {{ in_array($track->id, $selectedTrackIds) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 flex-shrink-0">
                            <input type="number" name="track_numbers[{{ $track->id }}]"
                                value="{{ old('track_numbers.' . $track->id, $trackNumbers[$track->id] ?? '') }}"
                                min="1" placeholder="#"
                                class="w-16 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm text-center focus:border-blue-500 focus:ring-blue-500 flex-shrink-0">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $track->display_title }}</span>
                            @if($track->band_names)
                                <span class="text-xs text-gray-400 truncate">{{ $track->band_names }}</span>
                            @endif
                            @if($track->isrc)
                                <span class="text-xs text-gray-400 font-mono ml-auto">{{ $track->isrc_formatted }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400">Keine Tracks vorhanden.</p>
            @endif
        </div>

        {{-- Musik: Territory --}}
        <div x-show="isMusic" x-transition x-cloak>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6"
            x-data="{
                worldwide: {{ json_encode(in_array('ALL', old('territory', $release->territory ?? []))) }},
                selected: {{ json_encode(old('territory', $release->territory ?? [])) }},
                presets: {{ json_encode($territoryPresets) }},
                countries: [
                    {code:'DE',name:'Deutschland'},{code:'AT',name:'Österreich'},{code:'CH',name:'Schweiz'},
                    {code:'US',name:'USA'},{code:'GB',name:'Grossbritannien'},{code:'FR',name:'Frankreich'},
                    {code:'IT',name:'Italien'},{code:'ES',name:'Spanien'},{code:'NL',name:'Niederlande'},
                    {code:'BE',name:'Belgien'},{code:'SE',name:'Schweden'},{code:'NO',name:'Norwegen'},
                    {code:'DK',name:'Dänemark'},{code:'FI',name:'Finnland'},{code:'JP',name:'Japan'},
                    {code:'AU',name:'Australien'},{code:'CA',name:'Kanada'},{code:'BR',name:'Brasilien'},
                    {code:'PL',name:'Polen'},{code:'PT',name:'Portugal'},{code:'NZ',name:'Neuseeland'}
                ],
                toggleWorldwide() { this.worldwide = !this.worldwide; this.selected = this.worldwide ? ['ALL'] : []; },
                applyPreset(key) { this.worldwide = false; this.selected = [...this.presets[key].countries]; if (this.selected.includes('ALL')) { this.worldwide = true; } },
                toggleCountry(code) { if (this.worldwide) return; const idx = this.selected.indexOf(code); if (idx > -1) { this.selected.splice(idx, 1); } else { this.selected.push(code); } },
                isChecked(code) { return this.selected.includes(code); }
            }">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Territory</h3>
            <label class="flex items-center gap-2 mb-4">
                <input type="checkbox" :checked="worldwide" @change="toggleWorldwide()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Weltweit</span>
            </label>
            <div class="flex flex-wrap gap-2 mb-4">
                <template x-for="(preset, key) in presets" :key="key">
                    <button type="button" @click="applyPreset(key)" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" x-text="preset.label"></button>
                </template>
            </div>
            <div x-show="!worldwide" x-cloak class="max-h-56 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                    <template x-for="country in countries" :key="country.code">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" :checked="isChecked(country.code)" @change="toggleCountry(country.code)" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300"><span class="font-mono text-xs text-gray-400" x-text="country.code"></span> <span x-text="country.name"></span></span>
                        </label>
                    </template>
                </div>
            </div>
            <template x-for="code in selected" :key="'t_' + code"><input type="hidden" name="territory[]" :value="code"></template>
        </div>
        </div>

        {{-- Musik: Band --}}
        <div x-show="isMusic" x-transition x-cloak class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Band</h3>
            @include('admin.partials.organization-search', ['selected' => $release->organizations->where('type', 'band'), 'inputName' => 'band_ids[]', 'orgSearchLabel' => 'Band', 'orgTypeFilter' => 'band'])
        </div>

        {{-- Musik: Credits --}}
        <div x-show="isMusic" x-transition x-cloak class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Credits</h3>
            @include('admin.partials.music-credits', ['selectedCredits' => $release->contacts])
        </div>

        {{-- Musik: Label --}}
        <div x-show="isMusic" x-transition x-cloak class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Label</h3>
            @include('admin.partials.organization-search', ['selected' => $release->organizations->where('type', 'label'), 'inputName' => 'label_ids[]', 'orgSearchLabel' => 'Label', 'orgTypeFilter' => 'label'])
        </div>

        {{-- Musik: Publisher --}}
        <div x-show="isMusic" x-transition x-cloak class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Publisher</h3>
            @include('admin.partials.organization-search', ['selected' => $release->organizations->where('type', 'publishing'), 'inputName' => 'publisher_ids[]', 'orgSearchLabel' => 'Publisher', 'orgTypeFilter' => 'publishing'])
        </div>

        {{-- Projekte --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Projekte</h3>
            <div>
                @include('admin.partials.project-search', ['selected' => $release->projects])
            </div>
        </div>

        {{-- Verträge --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Verträge</h3>
            <div>
                @include('admin.partials.contract-search', ['selected' => $release->contracts])
            </div>
        </div>

        {{-- Artworks --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Artworks</h3>
            <div>
                @include('admin.partials.artwork-search', ['selected' => $release->artworks])
            </div>
        </div>

        {{-- Notizen --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Notizen</h3>
            <textarea name="description" id="description" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $release->description) }}</textarea>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Produkt aktualisieren</button>
            <a href="{{ route('admin.releases.show', $release) }}" class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Abbrechen</a>
        </div>
    </form>

    {{-- Delete --}}
    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <form method="POST" action="{{ route('admin.releases.destroy', $release) }}" onsubmit="return confirm('Produkt wirklich löschen?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Produkt löschen</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function catalogGenerator() {
    return {
        catalogs: @json($catalogs),
        selectedCatalog: '',
        catalogNumber: '{{ old('catalog_number', $release->catalog_number ?? '') }}',
        async generate() {
            if (!this.selectedCatalog) return;
            try {
                const resp = await fetch(`/admin/releases-next-catalog-number?prefix=${encodeURIComponent(this.selectedCatalog)}`, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
                if (resp.ok) {
                    const data = await resp.json();
                    this.catalogNumber = data.catalog_number;
                }
            } catch (e) {}
        }
    };
}
</script>
@endpush
