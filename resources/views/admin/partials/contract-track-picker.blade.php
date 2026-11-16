{{--
    Tracks of a contract or a contract template, picked like the tracklist of
    a product: search and filter by band, label and person, then add with the
    plus, one by one or all matches at once.

    Parameters:
      $selected  the tracks (or their ids) already linked
      $field     input name: track_ids | default_track_ids

    The inputs are built by Alpine, so the section reports in with a marker;
    without it the controller leaves the links alone.
--}}
@php
    $field ??= 'track_ids';
    $selectedIds = old($field . '_submitted')
        ? (array) old($field, [])
        : collect($selected ?? [])->map(fn ($track) => is_object($track) ? $track->id : $track)->all();
    $trackPicker = \App\Services\ContractTrackPicker::config($selectedIds);
@endphp
<div x-data="contractTrackPicker(@js($trackPicker))" @paste-tracks.window="addMany(($event.detail || []).map(t => t.id))">
    <input type="hidden" name="{{ $field }}_submitted" value="" x-bind:value="'1'">

    <div class="flex items-baseline justify-between mb-1">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tracks</label>
        <span x-show="rows.length" x-cloak class="text-xs text-gray-500 dark:text-gray-400" x-text="rows.length === 1 ? '1 Track' : rows.length + ' Tracks'"></span>
    </div>

    {{-- The tracks on the contract --}}
    <ul x-show="rows.length" x-cloak class="border border-gray-200 dark:border-gray-600 rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
        <template x-for="id in rows" :key="id">
            <li class="flex items-center gap-3 px-3 py-2">
                <input type="hidden" name="{{ $field }}[]" :value="id">
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-gray-900 dark:text-gray-100 truncate" x-text="track(id).title"></p>
                    <p x-show="meta(track(id))" class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="meta(track(id))"></p>
                </div>
                <button type="button" @click="remove(id)" class="p-1 rounded text-gray-400 hover:text-red-600 flex-shrink-0" title="Aus dem Vertrag entfernen">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </li>
        </template>
    </ul>
    <p x-show="!rows.length" class="text-sm text-gray-400">Noch keine Tracks.</p>

    {{-- Picker --}}
    <div class="mt-3">
        @if(empty($trackPicker['tracks']))
            <p class="text-sm text-gray-400">Keine Tracks vorhanden. <a href="{{ route('admin.tracks.create') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">Track erstellen</a></p>
        @else
            <button type="button" @click="pickerOpen = !pickerOpen; if (pickerOpen) $nextTick(() => $refs.search.focus())"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                <svg class="w-4 h-4 transition-transform" :class="pickerOpen ? 'rotate-45' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span x-text="pickerOpen ? 'Track-Auswahl schliessen' : 'Tracks hinzufügen'"></span>
            </button>

            <div x-show="pickerOpen" x-cloak class="mt-3 space-y-3">
                <input type="search" x-ref="search" x-model.debounce.150ms="search" @keydown.enter.prevent="matches.length === 1 && add(matches[0].id)"
                       placeholder="Suchen: Titel, Band, Label, Person, Produkt, ISRC …"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <select x-model="band" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Alle Bands</option>
                        <template x-for="option in bands" :key="option.id">
                            <option :value="option.id" x-text="option.name"></option>
                        </template>
                    </select>
                    <select x-model="label" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Alle Labels</option>
                        <template x-for="option in labels" :key="option.id">
                            <option :value="option.id" x-text="option.name"></option>
                        </template>
                    </select>
                    <select x-model="person" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Alle Personen</option>
                        <template x-for="option in people" :key="option.id">
                            <option :value="option.id" x-text="option.name"></option>
                        </template>
                    </select>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                    <p class="text-gray-500 dark:text-gray-400">
                        <span x-text="matches.length === 1 ? '1 Track' : matches.length + ' Tracks'"></span><span x-show="filtering"> gefunden</span>
                        <button type="button" x-show="filtering" @click="resetFilters()" class="ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-400">Filter zurücksetzen</button>
                    </p>
                    <button type="button" x-show="filtering && addable.length" @click="addAll()"
                            class="px-2.5 py-1 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700"
                            x-text="addable.length === 1 ? '1 Track hinzufügen' : 'Alle ' + addable.length + ' hinzufügen'"></button>
                </div>

                <ul class="max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-for="item in shown" :key="item.id">
                        <li>
                            <button type="button" @click="toggle(item.id)"
                                    class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40"
                                    :title="isAdded(item.id) ? 'Aus dem Vertrag entfernen' : 'Zum Vertrag hinzufügen'">
                                <span class="w-6 h-6 flex-shrink-0 rounded-full flex items-center justify-center border"
                                      :class="isAdded(item.id) ? 'bg-green-600 border-green-600 text-white' : 'border-gray-300 dark:border-gray-500 text-gray-400'">
                                    <svg x-show="isAdded(item.id)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <svg x-show="!isAdded(item.id)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14m7-7H5"/></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm text-gray-900 dark:text-gray-100 truncate" x-text="item.title"></span>
                                    <span x-show="meta(item)" class="block text-xs text-gray-500 dark:text-gray-400 truncate" x-text="meta(item)"></span>
                                </span>
                            </button>
                        </li>
                    </template>
                    <li x-show="!matches.length" class="px-3 py-3 text-sm text-gray-400">Keine Tracks gefunden.</li>
                    <li x-show="matches.length > shown.length" class="px-3 py-2 text-xs text-gray-400">
                        und <span x-text="matches.length - shown.length"></span> weitere – Suche oder Filter eingrenzen
                    </li>
                </ul>
            </div>
        @endif
    </div>
</div>

@once
@push('scripts')
<script>
function contractTrackPicker(config) {
    // "Zürich" and "zurich" find each other.
    const fold = text => String(text || '').normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
    const byId = {};
    config.tracks.forEach(track => { track.folded = fold(track.haystack); byId[track.id] = track; });

    return {
        tracks: config.tracks,
        bands: config.bands,
        labels: config.labels,
        people: config.people,
        rows: config.selected.filter(id => byId[id]),
        search: '',
        band: '',
        label: '',
        person: '',
        pickerOpen: false,
        limit: 60,

        track(id) { return byId[id] ?? { title: 'Track #' + id, bands: '', labels: '', isrc: null }; },
        meta(track) { return [track.bands, track.labels ? 'Label ' + track.labels : '', track.isrc].filter(Boolean).join(' · '); },

        get terms() { return fold(this.search).split(/\s+/).filter(Boolean); },
        get filtering() { return this.terms.length > 0 || this.band !== '' || this.label !== '' || this.person !== ''; },
        get matches() {
            const terms = this.terms, band = Number(this.band), label = Number(this.label), person = Number(this.person);
            return this.tracks.filter(track =>
                (!band || track.bandIds.includes(band)) &&
                (!label || track.labelIds.includes(label)) &&
                (!person || track.personIds.includes(person)) &&
                terms.every(term => track.folded.includes(term)));
        },
        get shown() { return this.matches.slice(0, this.limit); },
        get addable() { return this.matches.filter(track => !this.isAdded(track.id)); },
        resetFilters() { this.search = ''; this.band = ''; this.label = ''; this.person = ''; },

        isAdded(id) { return this.rows.includes(id); },
        add(id) { if (!this.isAdded(id)) this.rows.push(id); },
        addMany(ids) { ids.map(Number).filter(id => byId[id]).forEach(id => this.add(id)); },
        addAll() { this.addable.forEach(track => this.add(track.id)); },
        remove(id) { this.rows = this.rows.filter(row => row !== id); },
        toggle(id) { this.isAdded(id) ? this.remove(id) : this.add(id); },
    };
}
</script>
@endpush
@endonce
