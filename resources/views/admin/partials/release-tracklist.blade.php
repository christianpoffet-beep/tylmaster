{{--
    Tracklist of a product, for the create and the edit form.
    $tracklist: ReleaseController::tracklistConfig() - the chosen rows and
    every track with what the picker filters by.

    Above: the tracks on the product in order, with their number - sorted by
    drag and drop (grip, or the title with the mouse) or the arrows; the
    order then gives the numbers 1 to n. Below: the picker, filtered by
    search, band and product. The inputs are built by
    Alpine, so the section reports in with a marker; without it the controller
    leaves the tracklist alone.
--}}
<div x-data="releaseTracklist(@js($tracklist))" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
    <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Tracklist</h3>
        <p x-show="rows.length" x-cloak class="text-sm text-gray-500 dark:text-gray-400">
            <span x-text="rows.length === 1 ? '1 Track' : rows.length + ' Tracks'"></span><span x-show="totalDuration"> · <span x-text="totalDuration"></span></span>
        </p>
    </div>

    <input type="hidden" name="track_ids_submitted" x-bind:value="'1'">

    @php $tracklistError = $errors->first('track_numbers.*') ?: $errors->first('track_ids.*') ?: $errors->first('track_ids'); @endphp
    @if($tracklistError)
        <p class="mb-3 text-sm text-red-600 dark:text-red-400">{{ $tracklistError }}</p>
    @endif

    {{-- The tracks on this product --}}
    <ol x-show="rows.length" x-cloak x-ref="rows" class="border border-gray-200 dark:border-gray-600 rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
        <template x-for="(row, index) in rows" :key="row.id">
            <li :data-row-id="row.id" @pointerdown="startDrag($event, index)"
                class="flex items-center gap-2 sm:gap-3 pl-1 pr-2 sm:pr-3 py-2 transition-colors"
                :class="dragging === row.id ? 'bg-blue-50 dark:bg-blue-900/30 shadow-md relative z-10 rounded-lg ring-1 ring-blue-300 dark:ring-blue-600' : ''">
                <span data-drag-handle="grip" class="flex-shrink-0 p-1 text-gray-300 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-300 touch-none"
                      :class="dragging === row.id ? 'cursor-grabbing' : 'cursor-grab'" title="Ziehen zum Verschieben">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><circle cx="7" cy="4" r="1.5"/><circle cx="13" cy="4" r="1.5"/><circle cx="7" cy="10" r="1.5"/><circle cx="13" cy="10" r="1.5"/><circle cx="7" cy="16" r="1.5"/><circle cx="13" cy="16" r="1.5"/></svg>
                </span>
                <input type="hidden" name="track_ids[]" :value="row.id">
                <input type="number" :name="'track_numbers[' + row.id + ']'" x-model="row.number" @change="sortByNumber()"
                       min="1" placeholder="#" :aria-label="'Tracknummer von ' + track(row.id).title"
                       class="w-14 flex-shrink-0 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm text-center focus:border-blue-500 focus:ring-blue-500">
                <div class="min-w-0 flex-1 select-none" data-drag-handle="row" :class="dragging === row.id ? 'cursor-grabbing' : 'sm:cursor-grab'">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="track(row.id).title"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-show="track(row.id).bands || track(row.id).isrc">
                        <span x-text="track(row.id).bands"></span><span x-show="track(row.id).bands && track(row.id).isrc"> · </span><span class="font-mono" x-text="track(row.id).isrc"></span>
                    </p>
                </div>
                <span class="hidden sm:inline text-xs text-gray-400 tabular-nums" x-text="track(row.id).duration"></span>
                <div class="flex items-center flex-shrink-0">
                    <button type="button" @click="move(index, -1)" :disabled="index === 0" class="p-1 rounded text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 disabled:opacity-30 disabled:cursor-default" title="Nach oben">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <button type="button" @click="move(index, 1)" :disabled="index === rows.length - 1" class="p-1 rounded text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 disabled:opacity-30 disabled:cursor-default" title="Nach unten">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <button type="button" @click="remove(index)" class="p-1 rounded text-gray-400 hover:text-red-600" title="Aus der Tracklist entfernen">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </li>
        </template>
    </ol>
    <div x-show="rows.length > 1" x-cloak class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
        <span class="text-gray-400 dark:text-gray-500">Zum Umsortieren ziehen – die Nummern folgen der Reihenfolge.</span>
        <button type="button" x-show="!numberedInOrder" @click="renumber()" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Nummern 1 bis <span x-text="rows.length"></span> in dieser Reihenfolge vergeben</button>
    </div>
    <p x-show="!rows.length" class="text-sm text-gray-400">Noch keine Tracks auf diesem Produkt.</p>

    {{-- Picker --}}
    <div class="mt-5 pt-4 border-t border-gray-200 dark:border-gray-700">
        @if(empty($tracklist['tracks']))
            <p class="text-sm text-gray-400">Keine Tracks vorhanden. <a href="{{ route('admin.tracks.create') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">Track erstellen</a></p>
        @else
            <button type="button" @click="pickerOpen = !pickerOpen; if (pickerOpen) $nextTick(() => $refs.search.focus())"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                <svg class="w-4 h-4 transition-transform" :class="pickerOpen ? 'rotate-45' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span x-text="pickerOpen ? 'Track-Auswahl schliessen' : 'Tracks hinzufügen'"></span>
            </button>

            <div x-show="pickerOpen" x-cloak class="mt-3 space-y-3">
                <div class="flex flex-col sm:flex-row gap-2">
                    <input type="search" x-ref="search" x-model.debounce.150ms="search" @keydown.enter.prevent="matches.length === 1 && add(matches[0].id)"
                           placeholder="Suchen: Titel, Band, Person, Produkt, ISRC …"
                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <select x-model="band" class="sm:w-44 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Alle Bands</option>
                        <template x-for="option in bands" :key="option.id">
                            <option :value="option.id" x-text="option.name"></option>
                        </template>
                    </select>
                    <select x-model="release" class="sm:w-52 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Alle Produkte</option>
                        <template x-for="option in releases" :key="option.id">
                            <option :value="option.id" x-text="option.title"></option>
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
                                    :title="isAdded(item.id) ? 'Aus der Tracklist entfernen' : 'Zur Tracklist hinzufügen'">
                                <span class="w-6 h-6 flex-shrink-0 rounded-full flex items-center justify-center border"
                                      :class="isAdded(item.id) ? 'bg-green-600 border-green-600 text-white' : 'border-gray-300 dark:border-gray-500 text-gray-400'">
                                    <svg x-show="isAdded(item.id)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <svg x-show="!isAdded(item.id)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14m7-7H5"/></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm text-gray-900 dark:text-gray-100 truncate">
                                        <span x-show="release && numberOn(item, release) !== null" class="text-gray-400 tabular-nums" x-text="numberOn(item, release) + '.'"></span>
                                        <span x-text="item.title"></span>
                                    </span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 truncate" x-show="item.bands || item.isrc">
                                        <span x-text="item.bands"></span><span x-show="item.bands && item.isrc"> · </span><span class="font-mono" x-text="item.isrc"></span>
                                    </span>
                                </span>
                                <span class="hidden sm:inline text-xs text-gray-400 tabular-nums" x-text="item.duration"></span>
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
function releaseTracklist(config) {
    const byId = Object.fromEntries(config.tracks.map(track => [track.id, track]));
    const asNumber = value => { const n = parseInt(value, 10); return n > 0 ? n : null; };

    return {
        tracks: config.tracks,
        bands: config.bands,
        releases: config.releases,
        rows: config.rows.filter(row => byId[row.id]).map(row => ({ id: row.id, number: row.number ?? '' })),
        search: '',
        band: '',
        release: '',
        pickerOpen: config.rows.length === 0,
        limit: 60,
        dragging: null,

        track(id) { return byId[id] ?? { title: 'Track #' + id, bands: '', isrc: null, duration: null, seconds: 0 }; },

        get totalDuration() {
            const seconds = this.rows.reduce((sum, row) => sum + (this.track(row.id).seconds || 0), 0);
            if (!seconds) return '';
            const h = Math.floor(seconds / 3600), m = Math.floor(seconds % 3600 / 60), s = seconds % 60;
            const pad = n => String(n).padStart(2, '0');
            return h ? `${h}:${pad(m)}:${pad(s)}` : `${m}:${pad(s)}`;
        },
        get numberedInOrder() { return this.rows.every((row, index) => asNumber(row.number) === index + 1); },

        // Picker
        get terms() { return this.search.toLowerCase().split(/\s+/).filter(Boolean); },
        get filtering() { return this.terms.length > 0 || this.band !== '' || this.release !== ''; },
        get matches() {
            const terms = this.terms, band = Number(this.band), release = Number(this.release);
            const list = this.tracks.filter(track =>
                (!band || track.bandIds.includes(band)) &&
                (!release || track.releases.some(entry => entry.id === release)) &&
                terms.every(term => track.haystack.includes(term)));
            // Filtered by a product, the tracks come in that product's order
            if (release) {
                const position = track => this.numberOn(track, release) ?? Infinity;
                return list.map((track, index) => [track, index])
                    .sort((a, b) => (position(a[0]) - position(b[0])) || (a[1] - b[1]))
                    .map(pair => pair[0]);
            }
            return list;
        },
        get shown() { return this.matches.slice(0, this.limit); },
        get addable() { return this.matches.filter(track => !this.isAdded(track.id)); },
        numberOn(track, releaseId) {
            const entry = track.releases.find(entry => entry.id === Number(releaseId));
            return entry ? asNumber(entry.number) : null;
        },
        resetFilters() { this.search = ''; this.band = ''; this.release = ''; },

        // Tracklist
        isAdded(id) { return this.rows.some(row => row.id === id); },
        nextNumber() {
            const numbers = this.rows.map(row => asNumber(row.number)).filter(Boolean);
            return numbers.length ? Math.max(...numbers) + 1 : this.rows.length + 1;
        },
        add(id) { if (!this.isAdded(id)) this.rows.push({ id, number: this.nextNumber() }); },
        addAll() { this.addable.forEach(track => this.add(track.id)); },
        toggle(id) { this.isAdded(id) ? this.remove(this.rows.findIndex(row => row.id === id)) : this.add(id); },
        remove(index) { if (index > -1) this.rows.splice(index, 1); },
        // Moving - arrows or drag and drop - makes the order the numbering
        move(index, step) {
            const other = index + step;
            if (other < 0 || other >= this.rows.length) return;
            this.moveTo(index, other);
            this.renumber();
        },
        moveTo(from, to) {
            if (from === to) return;
            const [row] = this.rows.splice(from, 1);
            this.rows.splice(to, 0, row);
        },

        /*
         * Drag and drop with pointer events, so it works with mouse, pen and
         * touch. The row moves along live as the pointer passes the middle
         * of its neighbours. Touch only drags by the grip, the title would
         * otherwise block scrolling the page.
         */
        startDrag(event, index) {
            const handle = event.target.closest('[data-drag-handle]');
            if (event.button !== 0 || !handle || (handle.dataset.dragHandle === 'row' && event.pointerType !== 'mouse')) return;
            event.preventDefault();

            const list = this.$refs.rows;
            const before = this.rows.map(row => row.id).join();
            this.dragging = this.rows[index].id;

            const onMove = (e) => {
                const items = [...list.children].filter(el => el.dataset && el.dataset.rowId);
                const from = this.rows.findIndex(row => row.id === this.dragging);
                let target = items.findIndex(el => { const box = el.getBoundingClientRect(); return e.clientY < box.top + box.height / 2; });
                if (target === -1) target = items.length;
                if (target > from) target--;
                this.moveTo(from, target);

                // Long lists: scroll along at the edges of the window
                if (e.clientY < 60) window.scrollBy(0, -12);
                else if (e.clientY > window.innerHeight - 60) window.scrollBy(0, 12);
            };
            const onEnd = () => {
                window.removeEventListener('pointermove', onMove);
                window.removeEventListener('pointerup', onEnd);
                window.removeEventListener('pointercancel', onEnd);
                this.dragging = null;
                if (this.rows.map(row => row.id).join() !== before) this.renumber();
            };
            window.addEventListener('pointermove', onMove);
            window.addEventListener('pointerup', onEnd);
            window.addEventListener('pointercancel', onEnd);
        },
        renumber() { this.rows.forEach((row, index) => { row.number = index + 1; }); },
        // A typed number moves the row to its place; rows without a number go last
        sortByNumber() {
            const position = row => asNumber(row.number) ?? Infinity;
            this.rows = this.rows.map((row, index) => [row, index])
                .sort((a, b) => (position(a[0]) - position(b[0])) || (a[1] - b[1]))
                .map(pair => pair[0]);
        },
    };
}
</script>
@endpush
@endonce
