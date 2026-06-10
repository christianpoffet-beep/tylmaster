{{-- Rights / Vergütung Editor --}}
{{-- Requires Alpine.js context with rightsEditor() function --}}
<div class="border-t border-gray-200 dark:border-gray-700 pt-6" x-data="rightsEditor()">
    <div class="flex justify-between items-center mb-3">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Vergütung / Rechte <span class="text-gray-400 font-normal">(optional)</span></p>
        <button type="button" @click="addRight()" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Recht hinzufügen</button>
    </div>
    <p class="text-xs text-gray-400 mb-3">Definiere die Einnahmenaufteilung pro Rechtetyp. Diese werden im Vertragstext und PDF abgebildet.</p>

    {{-- Beteiligte Parteien (Labels) --}}
    <div class="mb-4">
        <div class="flex justify-between items-center mb-2">
            <label class="block text-xs text-gray-500 dark:text-gray-400">Beteiligte Parteien an der Aufteilung</label>
            <button type="button" @click="addLabel()" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Partei</button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <template x-for="(label, j) in labels" :key="j">
                <div>
                    <div class="flex items-center gap-1">
                        <input type="text" x-model="labels[j]" :name="'rights_labels['+j+']'" :placeholder="placeholders[j] || ('Partei ' + (j + 1))" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" x-show="labels.length > 2" @click="removeLabel(j)" class="text-red-400 hover:text-red-600 flex-shrink-0" title="Partei entfernen">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <button type="button" x-show="!labels[j] && placeholders[j]" @click="labels[j] = placeholders[j]" class="text-[10px] text-blue-500 hover:text-blue-700 mt-0.5" x-text="'↑ «' + placeholders[j] + '» übernehmen'"></button>
                </div>
            </template>
        </div>
        {{-- Legacy mirror so older readers keep working --}}
        <input type="hidden" name="rights_label_a" :value="labels[0] || ''">
        <input type="hidden" name="rights_label_b" :value="labels[1] || ''">
    </div>

    {{-- Preset buttons --}}
    <div x-show="rights.length === 0" class="mb-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Schnellauswahl:</p>
        <div class="flex flex-wrap gap-2">
            <button type="button" @click="loadPreset('publishing')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:shadow-sm transition-colors">Publishing-Standard</button>
            <button type="button" @click="loadPreset('label')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:shadow-sm transition-colors">Label-Standard</button>
            <button type="button" @click="loadPreset('distribution')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:shadow-sm transition-colors">Distribution</button>
            <button type="button" @click="loadPreset('management')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:shadow-sm transition-colors">Management</button>
            <button type="button" @click="loadPreset('admin')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:shadow-sm transition-colors">Admin</button>
            <button type="button" @click="loadPreset('booking')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:shadow-sm transition-colors">Booking</button>
            <button type="button" @click="loadPreset('promotion')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:shadow-sm transition-colors">Promotion</button>
        </div>
    </div>

    {{-- Rights list --}}
    <div class="space-y-3">
        <template x-for="(right, index) in rights" :key="index">
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                {{-- Hidden fields for form submission --}}
                <input type="hidden" :name="'rights['+index+'][label]'" :value="right.label">
                <input type="hidden" :name="'rights['+index+'][mode]'" :value="right.mode">
                <input type="hidden" :name="'rights['+index+'][custom_text]'" :value="right.custom_text">
                {{-- Legacy mirror of the first two splits --}}
                <input type="hidden" :name="'rights['+index+'][split_a]'" :value="right.splits[0] ?? ''">
                <input type="hidden" :name="'rights['+index+'][split_b]'" :value="right.splits[1] ?? ''">

                <div class="flex justify-between items-start mb-3">
                    <input type="text" x-model="right.label" placeholder="Bezeichnung (z.B. Mechanische Rechte)" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm font-medium focus:border-blue-500 focus:ring-blue-500">
                    <button type="button" @click="removeRight(index)" class="ml-2 text-red-400 hover:text-red-600 flex-shrink-0 mt-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex gap-4 mb-3">
                    <label class="inline-flex items-center">
                        <input type="radio" :checked="right.mode === 'split'" @click="right.mode = 'split'" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-xs text-gray-700 dark:text-gray-300">Prozentuale Aufteilung</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" :checked="right.mode === 'custom'" @click="right.mode = 'custom'" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-xs text-gray-700 dark:text-gray-300">Freitext</span>
                    </label>
                </div>

                <div x-show="right.mode === 'split'">
                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                        <template x-for="(label, j) in labels" :key="j">
                            <div>
                                <label class="block text-[10px] text-gray-400 mb-0.5" x-text="labelDisplay(j)"></label>
                                <div class="flex items-center gap-1">
                                    <input type="number" x-model.number="right.splits[j]" :name="'rights['+index+'][splits]['+j+']'" @input="onSplitInput(index, j)" min="0" max="100" step="0.5" class="w-20 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="text-xs text-gray-400">%</span>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p class="text-[11px] mt-1.5" :class="Math.abs(rightTotal(right) - 100) < 0.01 ? 'text-gray-400' : 'text-red-500'">
                        Summe: <span x-text="rightTotal(right).toFixed(1) + '%'"></span>
                        <span x-show="Math.abs(rightTotal(right) - 100) >= 0.01"> — sollte 100% ergeben</span>
                    </p>
                </div>

                <div x-show="right.mode === 'custom'">
                    <input type="text" x-model="right.custom_text" placeholder="z.B. gemäss Verteilung der Verwertungsgesellschaft (SUISA)" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
        </template>
    </div>

    {{-- Preview --}}
    <div x-show="rights.length > 0" class="mt-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Vorschau Vergütungsklausel:</p>
        <div class="text-xs text-gray-700 dark:text-gray-300 space-y-0.5">
            <template x-for="(right, index) in rights" :key="index">
                <p>
                    <span class="font-medium" x-text="(right.label || '—') + ':'"></span>
                    <span x-show="right.mode === 'split'" x-text="splitPreview(right)"></span>
                    <span x-show="right.mode === 'custom'" x-text="right.custom_text"></span>
                </p>
            </template>
        </div>
    </div>
</div>

<script>
function rightsEditor() {
    const PRESETS = {
        publishing: { labels: ['Urheber', 'Verlag'], rights: [
            { label: 'Mechanische Rechte', mode: 'split', split_a: 50, split_b: 50 },
            { label: 'Aufführungsrechte', mode: 'custom', custom_text: 'gemäss Verteilung der Verwertungsgesellschaft (SUISA)' },
            { label: 'Synchronisationsrechte', mode: 'split', split_a: 50, split_b: 50 },
            { label: 'Druckrechte (Print)', mode: 'split', split_a: 50, split_b: 50 },
            { label: 'Sonstige Einnahmen', mode: 'split', split_a: 50, split_b: 50 },
        ]},
        label: { labels: ['Künstler', 'Label'], rights: [
            { label: 'Streaming & Downloads', mode: 'split', split_a: 20, split_b: 80 },
            { label: 'Physische Verkäufe', mode: 'split', split_a: 15, split_b: 85 },
            { label: 'Synchronisation', mode: 'split', split_a: 50, split_b: 50 },
            { label: 'Aufführungsrechte', mode: 'custom', custom_text: 'gemäss Verwertungsgesellschaft' },
            { label: 'Merchandising', mode: 'split', split_a: 70, split_b: 30 },
            { label: 'Sonstige Einnahmen', mode: 'split', split_a: 50, split_b: 50 },
        ]},
        distribution: { labels: ['Künstler/Label', 'Distributor'], rights: [
            { label: 'Streaming & Downloads', mode: 'split', split_a: 80, split_b: 20 },
            { label: 'Physische Distribution', mode: 'split', split_a: 70, split_b: 30 },
            { label: 'Sonstige Einnahmen', mode: 'split', split_a: 80, split_b: 20 },
        ]},
        management: { labels: ['Künstler', 'Management'], rights: [
            { label: 'Brutto-Einnahmen (Live)', mode: 'split', split_a: 80, split_b: 20 },
            { label: 'Brutto-Einnahmen (Recordings)', mode: 'split', split_a: 80, split_b: 20 },
            { label: 'Brutto-Einnahmen (Publishing)', mode: 'split', split_a: 80, split_b: 20 },
            { label: 'Sponsoring & Endorsements', mode: 'split', split_a: 80, split_b: 20 },
            { label: 'Merchandising', mode: 'split', split_a: 80, split_b: 20 },
            { label: 'Sonstige Einnahmen', mode: 'split', split_a: 80, split_b: 20 },
        ]},
        admin: { labels: ['Urheber/Verlag', 'Sub-Verlag/Admin'], rights: [
            { label: 'Mechanische Rechte', mode: 'split', split_a: 75, split_b: 25 },
            { label: 'Aufführungsrechte', mode: 'custom', custom_text: 'gemäss Verteilung der Verwertungsgesellschaft (SUISA)' },
            { label: 'Synchronisationsrechte', mode: 'split', split_a: 75, split_b: 25 },
            { label: 'Digitale Rechte', mode: 'split', split_a: 75, split_b: 25 },
            { label: 'Druckrechte (Print)', mode: 'split', split_a: 75, split_b: 25 },
            { label: 'Sonstige Einnahmen', mode: 'split', split_a: 75, split_b: 25 },
        ]},
        booking: { labels: ['Künstler', 'Booking-Agentur'], rights: [
            { label: 'Live-Auftritte (Gagen)', mode: 'split', split_a: 85, split_b: 15 },
            { label: 'Festival-Auftritte', mode: 'split', split_a: 85, split_b: 15 },
            { label: 'Corporate Events', mode: 'split', split_a: 80, split_b: 20 },
            { label: 'Merchandising (an Konzerten)', mode: 'custom', custom_text: 'nicht inbegriffen' },
        ]},
        promotion: { labels: ['Künstler/Label', 'Promoter'], rights: [
            { label: 'Promotion-Fee', mode: 'custom', custom_text: 'Pauschale gemäss separater Vereinbarung' },
            { label: 'Erfolgsbonus (Chartplatzierung)', mode: 'custom', custom_text: 'gemäss Bonusvereinbarung' },
            { label: 'Sonstige Kosten', mode: 'custom', custom_text: 'Reise- und Unterkunftskosten werden separat abgerechnet' },
        ]},
    };

    return {
        labels: [],
        placeholders: [],
        rights: [],
        autoGrow: true,

        init() {
            // Normalise labels: saved list, else legacy a/b pair, padded to min 2.
            let saved = @json($rightsLabels ?? old('rights_labels', []));
            if (!Array.isArray(saved) || saved.length === 0) {
                saved = [@json($rightsLabelA ?? old('rights_label_a', '')), @json($rightsLabelB ?? old('rights_label_b', ''))];
            }
            this.labels = saved.map(l => l == null ? '' : String(l));
            while (this.labels.length < 2) this.labels.push('');
            // If anything was explicitly saved, the user has taken control.
            if (this.labels.some(l => l !== '')) this.autoGrow = false;

            const rawRights = @json($rightsData ?? old('rights', []));
            this.rights = (rawRights || []).map(r => this.normalizeRight(r));
            this.syncSplits();

            window.addEventListener('party-names-updated', (e) => {
                const names = (e.detail && e.detail.parties) || [];
                this.placeholders = names;
                if (this.autoGrow && names.length > this.labels.length) {
                    while (this.labels.length < names.length) this.labels.push('');
                    this.syncSplits();
                }
            });
        },

        normalizeRight(r) {
            let splits;
            if (Array.isArray(r.splits)) {
                splits = r.splits.map(v => parseFloat(v) || 0);
            } else {
                splits = [parseFloat(r.split_a) || 0, parseFloat(r.split_b) || 0];
            }
            return {
                label: r.label || '',
                mode: r.mode || 'split',
                splits: splits,
                custom_text: r.custom_text || '',
            };
        },

        syncSplits() {
            const n = this.labels.length;
            this.rights.forEach(r => {
                while (r.splits.length < n) r.splits.push(0);
                if (r.splits.length > n) r.splits = r.splits.slice(0, n);
            });
        },

        labelDisplay(j) {
            return this.labels[j] || this.placeholders[j] || ('Partei ' + (j + 1));
        },

        evenSplits(n) {
            if (n <= 0) return [];
            const base = Math.floor(100 / n);
            const arr = Array(n).fill(base);
            arr[0] += 100 - base * n;
            return arr;
        },

        rightTotal(right) {
            return (right.splits || []).reduce((s, v) => s + (parseFloat(v) || 0), 0);
        },

        splitPreview(right) {
            return this.labels.map((l, j) => (parseFloat(right.splits[j]) || 0) + '% ' + this.labelDisplay(j)).join(' / ');
        },

        onSplitInput(index, j) {
            // Convenience auto-balance only for the classic two-party case.
            if (this.labels.length === 2) {
                const other = j === 0 ? 1 : 0;
                this.rights[index].splits[other] = Math.max(0, 100 - (parseFloat(this.rights[index].splits[j]) || 0));
            }
        },

        addLabel() {
            this.autoGrow = false;
            this.labels.push('');
            this.syncSplits();
        },

        removeLabel(j) {
            if (this.labels.length <= 2) return;
            this.autoGrow = false;
            this.labels.splice(j, 1);
            this.rights.forEach(r => r.splits.splice(j, 1));
        },

        addRight() {
            this.rights.push({ label: '', mode: 'split', splits: this.evenSplits(this.labels.length), custom_text: '' });
        },

        removeRight(index) {
            this.rights.splice(index, 1);
        },

        loadPreset(type) {
            const preset = PRESETS[type];
            if (!preset) return;
            // A preset defines its own party set — collapse to its size (keeping
            // any names already typed in the first slots) so an auto-grown extra
            // party does not turn a 2-party preset into a phantom 3-party split.
            this.labels = this.labels.slice(0, preset.labels.length);
            while (this.labels.length < 2) this.labels.push('');
            preset.labels.forEach((l, j) => {
                if (!this.labels[j]) this.labels[j] = l;
            });
            this.autoGrow = false;
            this.rights = preset.rights.map(r => this.normalizeRight(r));
            this.syncSplits();
        },
    };
}
</script>
