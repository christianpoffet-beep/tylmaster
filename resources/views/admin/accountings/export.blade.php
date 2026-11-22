@extends('admin.layouts.app')

@section('title', 'Export – ' . $accounting->name)

@php
    $fmt = fn ($v) => number_format($v, 2, '.', "'");
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
    $ok = 'text-green-600 dark:text-green-400';
    $warn = 'text-amber-600 dark:text-amber-400';
    $bad = 'text-red-600 dark:text-red-400';
    $receiptFiles = $files;
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Export für die Steuererklärung</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $accounting->name }} · {{ $accounting->accountable_name }} · {{ $accounting->period_start->format('d.m.Y') }} – {{ $accounting->period_end->format('d.m.Y') }}</p>
    </div>
    <a href="{{ route('admin.accountings.show', $accounting) }}" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Zurück</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Optionen & Download --}}
    <form method="GET" action="{{ route('admin.accountings.export.download', $accounting) }}" class="lg:col-span-2 space-y-6"
          x-data="{ signatures: @js((bool) $options['signatures']) }">
        <div class="{{ $card }} p-6 space-y-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Briefkopf &amp; Unterschriften</h3>

            <div>
                <label for="logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Logo</label>
                @if($logos)
                    <select name="logo" id="logo" class="{{ $input }}">
                        <option value="">Kein Logo</option>
                        @foreach($logos as $key => $logo)
                            <option value="{{ $key }}" @selected($options['logo'] === $key)>{{ $logo['label'] }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="logo" value="">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kein Logo gefunden. Hinterlege eins als Profilbild von {{ $accounting->accountable_name }} oder in einer Rechnungsvorlage (PNG oder JPG).</p>
                @endif
            </div>

            <div>
                <input type="hidden" name="address" value="0">
                <label class="flex items-start gap-2">
                    <input type="checkbox" name="address" value="1" @checked($options['address'] && count($address) > 1) @disabled(count($address) <= 1) class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Adresse im Briefkopf
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            {{ count($address) > 1 ? implode(' · ', $address) : 'Bei ' . $accounting->accountable_name . ' ist keine Adresse hinterlegt.' }}
                        </span>
                    </span>
                </label>
            </div>

            <div>
                <input type="hidden" name="signatures" value="0">
                <label class="flex items-start gap-2">
                    <input type="checkbox" name="signatures" value="1" x-model="signatures" class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Unterschriftenzeilen
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Am Schluss der Jahresrechnung, zum Visieren durch Vorstand oder Revision.</span>
                    </span>
                </label>
                <div x-show="signatures" x-cloak class="mt-3 ml-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="signature_labels" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Wer unterschreibt (eine Zeile pro Person)</label>
                        <textarea name="signature_labels" id="signature_labels" rows="3" class="{{ $input }}">{{ $options['signature_labels'] }}</textarea>
                    </div>
                    <div>
                        <label for="signature_note" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Vermerk mit Linie (leer = keiner)</label>
                        <input type="text" name="signature_note" id="signature_note" value="{{ $options['signature_note'] }}" class="{{ $input }}">
                        <p class="text-xs text-gray-400 mt-1">z.B. «Von der Generalversammlung genehmigt am» oder «Geprüft durch die Revisoren am».</p>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-400">Die Einstellungen werden für {{ $accounting->accountable_name }} gespeichert und nächstes Jahr wieder vorgeschlagen.</p>
        </div>

        <div class="{{ $card }} p-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Herunterladen</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <button type="submit" name="format" value="pdf" class="text-left p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30">
                    <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">PDF</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">Jahresrechnung: Bilanz, Erfolgsrechnung, Angaben für die Steuererklärung</span>
                </button>
                <button type="submit" name="format" value="xlsx" class="text-left p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30">
                    <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">Excel</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">Bilanz, Erfolgsrechnung, Journal, Kontoblätter, Belegverzeichnis</span>
                </button>
                <button type="submit" name="format" value="zip" class="text-left p-4 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/30 hover:border-blue-400 dark:hover:border-blue-600">
                    <span class="block text-sm font-medium text-blue-800 dark:text-blue-200">ZIP – alles</span>
                    <span class="block text-xs text-blue-700 dark:text-blue-300 mt-1">PDF + Excel + {{ $receiptFiles }} {{ $receiptFiles === 1 ? 'Beleg' : 'Belege' }}@if(count($statements)) + {{ count($statements) }} {{ count($statements) === 1 ? 'Bankauszug' : 'Bankauszüge' }}@endif</span>
                </button>
            </div>
        </div>
    </form>

    {{-- Kontrolle & Zahlen --}}
    <div class="space-y-6">
        <div class="{{ $card }} p-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Vor dem Export prüfen</h3>
            <ul class="space-y-2 text-sm">
                <li class="{{ $openBankCount ? $warn : $ok }}">
                    @if($openBankCount)
                        {{ $openBankCount }} {{ $openBankCount === 1 ? 'Bankbewegung ist' : 'Bankbewegungen sind' }} noch nicht gebucht –
                        <a href="{{ route('admin.accountings.bank', $accounting) }}" class="underline">Bankabgleich</a>
                    @else
                        Alle eingelesenen Bankbewegungen sind gebucht
                    @endif
                </li>
                <li class="{{ abs($difference) >= 0.005 ? $bad : $ok }}">
                    {{ abs($difference) >= 0.005 ? 'Die Bilanz geht um ' . $fmt($difference) . ' nicht auf' : 'Die Bilanz geht auf' }}
                </li>
                @if($openingDifferences->isNotEmpty())
                    <li class="{{ $warn }}">Die Eröffnungssaldi passen nicht mehr zum Vorjahr – <a href="{{ route('admin.accountings.show', $accounting) }}" class="underline">ansehen</a></li>
                @endif
                <li class="{{ $missing ? $bad : ($without ? 'text-gray-600 dark:text-gray-300' : $ok) }}">
                    {{ $receiptFiles }} {{ $receiptFiles === 1 ? 'Belegdatei' : 'Belegdateien' }}
                    @if($without) · {{ $without }} {{ $without === 1 ? 'Buchung' : 'Buchungen' }} ohne Beleg @endif
                    @if($missing) · {{ $missing }} {{ $missing === 1 ? 'Datei fehlt' : 'Dateien fehlen' }} auf dem Server @endif
                </li>
                <li class="{{ $accounting->is_closed ? $ok : 'text-gray-600 dark:text-gray-300' }}">
                    {{ $accounting->is_closed ? 'Buchhaltung ist abgeschlossen' : 'Buchhaltung ist noch offen – abschliessen, wenn alles gebucht ist' }}
                </li>
            </ul>
        </div>

        <div class="{{ $card }} p-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Zahlen</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Ertrag</dt><dd class="font-mono text-gray-900 dark:text-gray-100">{{ $fmt($sections['income']['total']) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Aufwand</dt><dd class="font-mono text-gray-900 dark:text-gray-100">{{ $fmt($sections['expense']['total']) }}</dd></div>
                <div class="flex justify-between gap-3 pt-2 border-t border-gray-200 dark:border-gray-700 font-medium"><dt class="text-gray-700 dark:text-gray-300">Jahresergebnis ({{ \App\Services\Reports\AnnualReport::resultLabel($result) }})</dt><dd class="font-mono {{ $result < 0 ? $bad : 'text-gray-900 dark:text-gray-100' }}">{{ $fmt($result) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Eigenkapital Ende</dt><dd class="font-mono text-gray-900 dark:text-gray-100">{{ $fmt($equity) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Bilanzsumme</dt><dd class="font-mono text-gray-900 dark:text-gray-100">{{ $fmt($total_assets) }}</dd></div>
            </dl>
            <p class="text-xs text-gray-400 mt-3">Beträge in {{ $accounting->currency }}, {{ $journal->count() }} Buchungen.</p>
        </div>
    </div>
</div>
@endsection
