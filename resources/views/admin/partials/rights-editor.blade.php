{{-- Rights / Vergütung Editor --}}
{{-- Requires Alpine.js context with rightsEditor() function --}}
<div class="border-t border-gray-200 dark:border-gray-700 pt-6" x-data="rightsEditor()">
    <div class="flex justify-between items-center mb-3">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Vergütung / Rechte <span class="text-gray-400 font-normal">(optional)</span></p>
        <button type="button" @click="addRight()" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Recht hinzufügen</button>
    </div>
    <p class="text-xs text-gray-400 mb-3">Definiere die Einnahmenaufteilung pro Rechtetyp. Diese werden im Vertragstext und PDF abgebildet.</p>

    {{-- Label pair --}}
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Bezeichnung Partei 1</label>
            <input type="text" name="rights_label_a" x-model="labelA" :placeholder="placeholderA" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <button type="button" x-show="labelA !== placeholderA && placeholderA !== 'Partei 1'" @click="labelA = placeholderA" class="text-[10px] text-blue-500 hover:text-blue-700 mt-0.5" x-text="'↑ «' + placeholderA + '» übernehmen'"></button>
        </div>
        <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Bezeichnung Partei 2</label>
            <input type="text" name="rights_label_b" x-model="labelB" :placeholder="placeholderB" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <button type="button" x-show="labelB !== placeholderB && placeholderB !== 'Partei 2'" @click="labelB = placeholderB" class="text-[10px] text-blue-500 hover:text-blue-700 mt-0.5" x-text="'↑ «' + placeholderB + '» übernehmen'"></button>
        </div>
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
                <input type="hidden" :name="'rights['+index+'][split_a]'" :value="right.split_a">
                <input type="hidden" :name="'rights['+index+'][split_b]'" :value="right.split_b">
                <input type="hidden" :name="'rights['+index+'][custom_text]'" :value="right.custom_text">

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

                <div x-show="right.mode === 'split'" class="flex items-center gap-2">
                    <div class="flex-1">
                        <label class="block text-[10px] text-gray-400 mb-0.5" x-text="labelA || 'Partei 1'"></label>
                        <div class="flex items-center gap-1">
                            <input type="number" x-model="right.split_a" @input="right.split_b = Math.max(0, 100 - (parseFloat(right.split_a) || 0))" min="0" max="100" step="0.5" class="w-20 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="text-xs text-gray-400">%</span>
                        </div>
                    </div>
                    <span class="text-gray-300 dark:text-gray-600 mt-4">/</span>
                    <div class="flex-1">
                        <label class="block text-[10px] text-gray-400 mb-0.5" x-text="labelB || 'Partei 2'"></label>
                        <div class="flex items-center gap-1">
                            <input type="number" x-model="right.split_b" @input="right.split_a = Math.max(0, 100 - (parseFloat(right.split_b) || 0))" min="0" max="100" step="0.5" class="w-20 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="text-xs text-gray-400">%</span>
                        </div>
                    </div>
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
            <template x-for="right in rights" :key="right.label">
                <p>
                    <span class="font-medium" x-text="right.label + ':'"></span>
                    <span x-show="right.mode === 'split'" x-text="(right.split_a || 0) + '% ' + (labelA || 'Partei 1') + ' / ' + (right.split_b || 0) + '% ' + (labelB || 'Partei 2')"></span>
                    <span x-show="right.mode === 'custom'" x-text="right.custom_text"></span>
                </p>
            </template>
        </div>
    </div>
</div>

<script>
function rightsEditor() {
    return {
        labelA: @json($rightsLabelA ?? old('rights_label_a', '')),
        labelB: @json($rightsLabelB ?? old('rights_label_b', '')),
        placeholderA: 'Partei 1',
        placeholderB: 'Partei 2',
        rights: @json($rightsData ?? old('rights', [])),
        init() {
            window.addEventListener('party-names-updated', (e) => {
                if (e.detail.party1) this.placeholderA = e.detail.party1;
                if (e.detail.party2) this.placeholderB = e.detail.party2;
            });
        },
        addRight() {
            this.rights.push({ label: '', mode: 'split', split_a: 50, split_b: 50, custom_text: '' });
        },
        removeRight(index) {
            this.rights.splice(index, 1);
        },
        loadPreset(type) {
            if (type === 'publishing') {
                this.labelA = this.labelA || 'Urheber';
                this.labelB = this.labelB || 'Verlag';
                this.rights = [
                    { label: 'Mechanische Rechte', mode: 'split', split_a: 50, split_b: 50, custom_text: '' },
                    { label: 'Aufführungsrechte', mode: 'custom', split_a: null, split_b: null, custom_text: 'gemäss Verteilung der Verwertungsgesellschaft (SUISA)' },
                    { label: 'Synchronisationsrechte', mode: 'split', split_a: 50, split_b: 50, custom_text: '' },
                    { label: 'Druckrechte (Print)', mode: 'split', split_a: 50, split_b: 50, custom_text: '' },
                    { label: 'Sonstige Einnahmen', mode: 'split', split_a: 50, split_b: 50, custom_text: '' },
                ];
            } else if (type === 'label') {
                this.labelA = this.labelA || 'Künstler';
                this.labelB = this.labelB || 'Label';
                this.rights = [
                    { label: 'Streaming & Downloads', mode: 'split', split_a: 20, split_b: 80, custom_text: '' },
                    { label: 'Physische Verkäufe', mode: 'split', split_a: 15, split_b: 85, custom_text: '' },
                    { label: 'Synchronisation', mode: 'split', split_a: 50, split_b: 50, custom_text: '' },
                    { label: 'Aufführungsrechte', mode: 'custom', split_a: null, split_b: null, custom_text: 'gemäss Verwertungsgesellschaft' },
                    { label: 'Merchandising', mode: 'split', split_a: 70, split_b: 30, custom_text: '' },
                    { label: 'Sonstige Einnahmen', mode: 'split', split_a: 50, split_b: 50, custom_text: '' },
                ];
            } else if (type === 'distribution') {
                this.labelA = this.labelA || 'Künstler/Label';
                this.labelB = this.labelB || 'Distributor';
                this.rights = [
                    { label: 'Streaming & Downloads', mode: 'split', split_a: 80, split_b: 20, custom_text: '' },
                    { label: 'Physische Distribution', mode: 'split', split_a: 70, split_b: 30, custom_text: '' },
                    { label: 'Sonstige Einnahmen', mode: 'split', split_a: 80, split_b: 20, custom_text: '' },
                ];
            } else if (type === 'management') {
                this.labelA = this.labelA || 'Künstler';
                this.labelB = this.labelB || 'Management';
                this.rights = [
                    { label: 'Brutto-Einnahmen (Live)', mode: 'split', split_a: 80, split_b: 20, custom_text: '' },
                    { label: 'Brutto-Einnahmen (Recordings)', mode: 'split', split_a: 80, split_b: 20, custom_text: '' },
                    { label: 'Brutto-Einnahmen (Publishing)', mode: 'split', split_a: 80, split_b: 20, custom_text: '' },
                    { label: 'Sponsoring & Endorsements', mode: 'split', split_a: 80, split_b: 20, custom_text: '' },
                    { label: 'Merchandising', mode: 'split', split_a: 80, split_b: 20, custom_text: '' },
                    { label: 'Sonstige Einnahmen', mode: 'split', split_a: 80, split_b: 20, custom_text: '' },
                ];
            } else if (type === 'admin') {
                this.labelA = this.labelA || 'Urheber/Verlag';
                this.labelB = this.labelB || 'Sub-Verlag/Admin';
                this.rights = [
                    { label: 'Mechanische Rechte', mode: 'split', split_a: 75, split_b: 25, custom_text: '' },
                    { label: 'Aufführungsrechte', mode: 'custom', split_a: null, split_b: null, custom_text: 'gemäss Verteilung der Verwertungsgesellschaft (SUISA)' },
                    { label: 'Synchronisationsrechte', mode: 'split', split_a: 75, split_b: 25, custom_text: '' },
                    { label: 'Digitale Rechte', mode: 'split', split_a: 75, split_b: 25, custom_text: '' },
                    { label: 'Druckrechte (Print)', mode: 'split', split_a: 75, split_b: 25, custom_text: '' },
                    { label: 'Sonstige Einnahmen', mode: 'split', split_a: 75, split_b: 25, custom_text: '' },
                ];
            } else if (type === 'booking') {
                this.labelA = this.labelA || 'Künstler';
                this.labelB = this.labelB || 'Booking-Agentur';
                this.rights = [
                    { label: 'Live-Auftritte (Gagen)', mode: 'split', split_a: 85, split_b: 15, custom_text: '' },
                    { label: 'Festival-Auftritte', mode: 'split', split_a: 85, split_b: 15, custom_text: '' },
                    { label: 'Corporate Events', mode: 'split', split_a: 80, split_b: 20, custom_text: '' },
                    { label: 'Merchandising (an Konzerten)', mode: 'custom', split_a: null, split_b: null, custom_text: 'nicht inbegriffen' },
                ];
            } else if (type === 'promotion') {
                this.labelA = this.labelA || 'Künstler/Label';
                this.labelB = this.labelB || 'Promoter';
                this.rights = [
                    { label: 'Promotion-Fee', mode: 'custom', split_a: null, split_b: null, custom_text: 'Pauschale gemäss separater Vereinbarung' },
                    { label: 'Erfolgsbonus (Chartplatzierung)', mode: 'custom', split_a: null, split_b: null, custom_text: 'gemäss Bonusvereinbarung' },
                    { label: 'Sonstige Kosten', mode: 'custom', split_a: null, split_b: null, custom_text: 'Reise- und Unterkunftskosten werden separat abgerechnet' },
                ];
            }
        }
    };
}
</script>
