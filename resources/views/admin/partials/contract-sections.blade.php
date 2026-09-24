{{--
    Vertragsabschnitte: nummerierte Klauseln statt einem einzigen Freitextfeld.

    Parameters (all optional, defaults target the contract form):
      $sections        array of ['title','body','page_break','numbered']
      $autoNumber      bool
      $termsValue      legacy free text
      $closingValue    text printed above the signatures
      $prefix          field name for the section rows          (sections)
      $termsField      field name for the legacy free text      (terms)
      $closingField    field name for the closing text          (closing_note)
      $autoNumberField field name for the numbering toggle      (auto_number_sections)
      $heading         section label
      $editorId        unique DOM id when several editors share a page
      $subjectField    id of the subject textarea — clause 1 when it is filled
--}}
@php
    $prefix ??= 'sections';
    $termsField ??= 'terms';
    $closingField ??= 'closing_note';
    $autoNumberField ??= 'auto_number_sections';
    $heading ??= 'Bedingungen / Abschnitte';
    $editorId ??= $termsField;
    $showAutoNumber ??= true;
    $subjectField ??= 'subject';
    $sections = array_values($sections ?? []);
    $autoNumber = $autoNumber ?? true;
    $termsValue = (string) ($termsValue ?? '');
    $closingValue = $closingValue ?? null;
@endphp
<div class="border-t border-gray-200 dark:border-gray-700 pt-6" x-data="contractSections(@js($sections), @js($editorId), @js($subjectField))">
    <div class="flex justify-between items-center mb-1">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $heading }}</p>
        <button type="button" @click="addSection()" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Abschnitt</button>
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
        Jeder Abschnitt wird im PDF als eigene Klausel mit Titel gesetzt. Bei automatischer Nummerierung ist der
        Vertragsgegenstand Ziffer&nbsp;1, die Abschnitte folgen danach.
    </p>

    @if($showAutoNumber)
    <label class="inline-flex items-center mb-4">
        <input type="hidden" name="{{ $autoNumberField }}" value="0">
        <input type="checkbox" name="{{ $autoNumberField }}" value="1" {{ $autoNumber ? 'checked' : '' }} class="rounded text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Abschnitte automatisch nummerieren (1., 2., 3. …)</span>
    </label>
    @endif

    <div class="space-y-3">
        <template x-for="(section, index) in sections" :key="index">
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-medium text-gray-400 w-7 flex-shrink-0" x-text="numberFor(index)"></span>
                    <input type="text" :name="'{{ $prefix }}['+index+'][title]'" x-model="section.title" placeholder="Titel, z.B. Vertragsdauer und Kündigung" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm font-medium focus:border-blue-500 focus:ring-blue-500">
                    <button type="button" @click="move(index, -1)" x-show="index > 0" title="Nach oben" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <button type="button" @click="move(index, 1)" x-show="index < sections.length - 1" title="Nach unten" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <button type="button" @click="remove(index)" title="Abschnitt entfernen" class="text-red-400 hover:text-red-600 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <textarea :name="'{{ $prefix }}['+index+'][body]'" x-model="section.body" rows="5" placeholder="Text des Abschnitts …" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>

                <div class="flex flex-wrap gap-4 mt-2">
                    <label class="inline-flex items-center">
                        <input type="hidden" :name="'{{ $prefix }}['+index+'][numbered]'" value="0">
                        <input type="checkbox" :name="'{{ $prefix }}['+index+'][numbered]'" value="1" x-model="section.numbered" class="rounded text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">Nummerieren</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="hidden" :name="'{{ $prefix }}['+index+'][page_break]'" value="0">
                        <input type="checkbox" :name="'{{ $prefix }}['+index+'][page_break]'" value="1" x-model="section.page_break" class="rounded text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">Seitenumbruch davor</span>
                    </label>
                </div>
            </div>
        </template>
    </div>

    <p x-show="sections.length === 0" class="text-xs text-gray-400 italic">Keine Abschnitte — es wird der Freitext unten verwendet.</p>

    {{-- Legacy free-text field: only used while no sections exist --}}
    <div class="mt-4" x-data="{ open: {{ trim($termsValue) !== '' && count($sections) === 0 ? 'true' : 'false' }} }">
        <button type="button" @click="open = !open" class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
            <span x-text="open ? '▾' : '▸'"></span> Freitext <span class="text-gray-400">(wird nur verwendet, solange keine Abschnitte erfasst sind)</span>
        </button>
        <div x-show="open" x-cloak class="mt-2">
            <div class="flex justify-end mb-1">
                <button type="button" @click="importFromTerms()" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">In Abschnitte umwandeln →</button>
            </div>
            <textarea name="{{ $termsField }}" id="{{ $termsField }}" rows="10" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono">{{ $termsValue }}</textarea>
        </div>
    </div>

    @if($closingField)
    <div class="mt-4">
        <label for="{{ $closingField }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Schlusstext vor den Unterschriften</label>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Optional, z.B. «Dieser Vertrag wird in zweifacher Ausfertigung erstellt.»</p>
        <textarea name="{{ $closingField }}" id="{{ $closingField }}" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ $closingValue }}</textarea>
    </div>
    @endif
</div>

@once
<script>
function contractSections(initial, editorId, subjectField) {
    const normalize = (s) => ({
        title: s.title ?? '',
        body: s.body ?? '',
        page_break: !!(s.page_break ?? false),
        numbered: s.numbered === undefined ? true : !!s.numbered,
    });

    return {
        // The subject is clause 1, so this editor starts counting at 2.
        sections: (initial || []).map(normalize),
        init() {
            // Applying a contract template replaces the whole list.
            window.addEventListener('contract-sections-set', (e) => {
                if (e.detail?.editorId !== editorId) return;
                this.sections = (e.detail.sections || []).map(normalize);
            });
        },
        numberFor(index) {
            // The subject is clause 1 whenever it carries text.
            const subject = document.getElementById(subjectField);
            let n = (subject && subject.value.trim() !== '') ? 2 : 1;
            for (let i = 0; i < index; i++) {
                if (this.sections[i].numbered) n++;
            }
            return this.sections[index].numbered ? n + '.' : '—';
        },
        addSection() {
            this.sections.push({ title: '', body: '', page_break: false, numbered: true });
        },
        remove(index) {
            this.sections.splice(index, 1);
        },
        move(index, delta) {
            const target = index + delta;
            if (target < 0 || target >= this.sections.length) return;
            const [item] = this.sections.splice(index, 1);
            this.sections.splice(target, 0, item);
        },
        /**
         * Split the legacy free text on its own headings ("2. VERTRAGSDAUER …")
         * so an existing contract becomes editable section by section.
         */
        importFromTerms() {
            const field = document.getElementById(editorId);
            const text = (field?.value || '').replace(/\r\n/g, '\n');
            if (!text.trim()) return;

            const headingOf = (line) => {
                const m = line.match(/^\s*\d+\s*[.)]\s+(.{1,80})$/);
                if (!m) return null;
                const title = m[1].trim();
                const letters = title.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ]/g, '');
                const allCaps = letters.length > 0 && title.toUpperCase() === title;
                if (!allCaps && /[.!?]$/.test(title)) return null;
                // "VERTRAGSDAUER UND KÜNDIGUNG" reads better as "Vertragsdauer und Kündigung".
                return allCaps ? titleCase(title) : title;
            };

            const titleCase = (t) => t.toLowerCase().replace(/(^|[\s(/-])(\p{L})/gu, (m, sep, ch) => sep + ch.toUpperCase());

            const found = [];
            let current = null;
            for (const line of text.split('\n')) {
                const title = headingOf(line);
                if (title) {
                    if (current) found.push(current);
                    current = { title, body: '', page_break: false, numbered: true };
                } else if (current) {
                    current.body += line + '\n';
                }
            }
            if (current) found.push(current);

            if (found.length === 0) {
                found.push({ title: '', body: text.trim(), page_break: false, numbered: true });
            }

            found.forEach(s => { s.body = s.body.trim(); });
            this.sections = this.sections.concat(found);
            if (field) field.value = '';
        },
    };
}
</script>
@endonce
