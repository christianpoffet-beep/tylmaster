{{--
    Vertragswerte und Platzhalter-Hilfe. Shared by the contract form and the
    template form; a new contract takes the template's values and can change
    them.

    Parameters:
      $field     field name of the rows (placeholder_values | default_placeholder_values)
      $rows      array of ['key' => …, 'value' => …]
      $current   optional: what each placeholder resolves to for a saved contract
--}}
@php
    $rows = array_values(array_map(fn ($r) => [
        'key' => (string) ($r['key'] ?? ''),
        'value' => (string) ($r['value'] ?? ''),
    ], is_array($rows ?? null) ? $rows : []));
    $current ??= null;
    $rowErrors = collect($errors->get($field . '.*'))->flatten()->unique();
@endphp
<div class="border-t border-gray-200 dark:border-gray-700 pt-6"
     x-data="contractPlaceholderValues(@js($rows))"
     @placeholder-values-set.window="setRows($event.detail)">
    <input type="hidden" name="{{ $field }}_submitted" value="" x-bind:value="'1'">

    <div class="flex justify-between items-center mb-1">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Vertragswerte für Platzhalter</p>
        <button type="button" @click="add()" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Wert</button>
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
        Steht in einem Text z.&nbsp;B. <code class="font-mono">{notice_period}</code>, setzen Ansicht und PDF hier den Wert ein, etwa «drei Wochen».
        Ein neuer Vertrag übernimmt die Werte der Vorlage und kann sie anpassen. Namen, Anteile und Daten füllt der Vertrag selbst, siehe unten.
    </p>

    <div class="space-y-2">
        <template x-for="(row, index) in rows" :key="index">
            <div class="flex items-center gap-2">
                <div class="flex items-center w-56 flex-shrink-0">
                    <span class="text-sm text-gray-400 font-mono mr-1">{</span>
                    <input type="text" :name="'{{ $field }}['+index+'][key]'" x-model="row.key" placeholder="notice_period" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm font-mono focus:border-blue-500 focus:ring-blue-500">
                    <span class="text-sm text-gray-400 font-mono ml-1">}</span>
                </div>
                <input type="text" :name="'{{ $field }}['+index+'][value]'" x-model="row.value" placeholder="drei Wochen" class="flex-1 min-w-0 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="button" @click="remove(index)" title="Wert entfernen" class="text-red-400 hover:text-red-600 flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>
    <p x-show="rows.length === 0" class="text-xs text-gray-400 italic">Noch keine Vertragswerte.</p>
    @foreach($rowErrors as $message)
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @endforeach

    {{-- Help: every automatic placeholder and the plural syntax --}}
    <div class="mt-4" x-data="{ open: false }">
        <button type="button" @click="open = !open" class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
            <span x-text="open ? '▾' : '▸'"></span> Verfügbare Platzhalter
        </button>
        <div x-show="open" x-cloak class="mt-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-3 text-xs text-gray-600 dark:text-gray-300 space-y-3">
            <p>
                Platzhalter gehen in allen Vertragstexten: Vertragsgegenstand, Abschnitte (Titel und Text), eigene Präambel,
                Schlusstext, Verknüpfungstext, Zessionsnotiz. Gespeichert wird der Text mit den Platzhaltern, ersetzt wird erst
                in der Ansicht und im PDF. Ein Klick auf einen Namen kopiert ihn.
            </p>

            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray-400">
                        <th class="font-normal pb-1 pr-3">Automatisch</th>
                        <th class="font-normal pb-1 pr-3">Inhalt</th>
                        @if($current !== null)<th class="font-normal pb-1">In diesem Vertrag</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Services\ContractPlaceholders::AUTOMATIC as $key => $description)
                        @php $token = '{' . $key . '}'; @endphp
                        <tr class="align-top">
                            <td class="pr-3 py-0.5 whitespace-nowrap">
                                <button type="button" @click="copy(@js($token))" class="font-mono text-blue-700 dark:text-blue-300 hover:underline">{{ $token }}</button>
                                <span x-show="copied === @js($token)" x-cloak class="text-green-600 dark:text-green-400 ml-1">kopiert</span>
                            </td>
                            <td class="pr-3 py-0.5">{{ $description }}</td>
                            @if($current !== null)
                                <td class="py-0.5 {{ ($current[$key] ?? null) === null ? 'text-amber-600 dark:text-amber-400' : 'text-gray-800 dark:text-gray-100' }}">{{ $current[$key] ?? 'kein Wert' }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="space-y-1">
                <p class="text-gray-400">Eine oder mehrere Personen auf der Künstlerseite</p>
                <p><code class="font-mono text-gray-800 dark:text-gray-100">[[MEHRERE: …]]</code> erscheint nur, wenn mehrere Personen unterschreiben, <code class="font-mono text-gray-800 dark:text-gray-100">[[EINE: …]]</code> nur bei einer Person.</p>
                <p><code class="font-mono text-gray-800 dark:text-gray-100">{haftet|haften}</code> wählt das linke Wort bei einer Person und das rechte bei mehreren, für jedes beliebige Wortpaar.</p>
            </div>
        </div>
    </div>
</div>

@once
<script>
function contractPlaceholderValues(initial) {
    const normalize = (r) => ({ key: r?.key ?? '', value: r?.value ?? '' });

    return {
        rows: (initial || []).map(normalize),
        copied: null,
        add() {
            this.rows.push({ key: '', value: '' });
        },
        remove(index) {
            this.rows.splice(index, 1);
        },
        /** Applying a contract template replaces the whole list. */
        setRows(rows) {
            this.rows = (rows || []).map(normalize);
        },
        copy(text) {
            navigator.clipboard?.writeText(text);
            this.copied = text;
            setTimeout(() => { if (this.copied === text) this.copied = null; }, 1500);
        },
    };
}
</script>
@endonce
