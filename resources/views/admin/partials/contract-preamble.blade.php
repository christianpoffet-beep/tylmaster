{{-- Parteien-Präambel ("... vertreten durch ..., nachfolgend «Label»") --}}
{{-- Requires the surrounding Alpine contractForm() scope for the live preview. --}}
@php
    $preambleMode = old('preamble_mode', $contract->preamble_mode ?? 'auto');
    $showPartiesTable = (bool) old('show_parties_table', $contract->show_parties_table ?? true);
@endphp
<div class="border-t border-gray-200 dark:border-gray-700 pt-6">
    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Präambel der Vertragsparteien</p>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
        Der Einleitungstext mit Namen, Adressen und Rollen der Parteien. Im Modus «Automatisch» wird er aus
        den oben erfassten Parteien generiert — er gehört dann <strong>nicht</strong> mehr in den Vertragsgegenstand.
    </p>

    <div class="flex flex-wrap gap-4 mb-3">
        @foreach(['auto' => 'Automatisch', 'custom' => 'Eigener Text', 'none' => 'Keine Präambel'] as $value => $label)
            <label class="inline-flex items-center">
                <input type="radio" name="preamble_mode" value="{{ $value }}" x-model="preambleMode" {{ $preambleMode === $value ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
            </label>
        @endforeach
    </div>

    <label class="inline-flex items-center mb-3">
        <input type="hidden" name="show_parties_table" value="0">
        <input type="checkbox" name="show_parties_table" value="1" {{ $showPartiesTable ? 'checked' : '' }} class="rounded text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Parteien-Tabelle mit Anteilen im PDF anzeigen</span>
    </label>

    <div x-show="preambleMode === 'auto'" x-cloak>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Vorschau (aus den Parteien generiert):</p>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-3 text-xs text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed" x-text="preamblePreview || 'Noch keine Parteien mit Rolle erfasst.'"></div>
        <p class="text-[11px] text-gray-400 mt-1">Die Rolle («Label», «Künstlerin oder Künstler» …) wird pro Partei oben erfasst. Parteien mit gleicher Rolle werden zusammengefasst.</p>
    </div>

    <div x-show="preambleMode === 'custom'" x-cloak>
        <textarea name="preamble_text" rows="8" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Eigener Präambel-Text …">{{ old('preamble_text', $contract->preamble_text ?? '') }}</textarea>
    </div>
</div>
