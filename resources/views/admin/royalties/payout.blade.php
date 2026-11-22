@extends('admin.layouts.app')

@section('title', 'Auszahlung ' . $payout->execution_date->format('d.m.Y'))

@php
    $chf = fn ($value) => number_format((float) $value, 2, '.', "'");
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
    $inFile = $payout->items->filter->is_transferable;
    $outside = $payout->items->reject->is_transferable;
    $reconciled = $payout->items->contains(fn ($i) => $i->booking?->bank_transaction_id);
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Auszahlung vom {{ $payout->execution_date->format('d.m.Y') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ $payout->accounting?->name }} · {{ $payout->bankAccount?->number }} {{ $payout->bankAccount?->name }}{{ $payout->debtor_iban ? ' · ' . \App\Support\Iban::format($payout->debtor_iban) : '' }}
            · Mitteilung «{{ $payout->message }}»
        </p>
    </div>
    <div class="flex items-center gap-2">
        @unless($reconciled)
            <form method="POST" action="{{ route('admin.royalties.payouts.destroy', $payout) }}" onsubmit="return confirm('Auszahlung stornieren? Buchungen werden gelöscht, die Saldi wieder erhöht. Eine schon ans E-Banking übermittelte Datei bitte dort ebenfalls löschen.')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-red-50 dark:hover:bg-red-900/20">Stornieren</button>
            </form>
        @endunless
        @if($inFile->isNotEmpty())
            <a href="{{ route('admin.royalties.payouts.pain001', $payout) }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Zahlungsdatei (pain.001)</a>
        @endif
    </div>
</div>

@if(!$payout->debtor_iban && $inFile->isNotEmpty())
    <div class="mb-6 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4 text-sm text-red-800 dark:text-red-200">
        Das Bankkonto hat keine IBAN – das E-Banking nimmt die Datei so nicht an. Die IBAN wird beim Bankimport übernommen oder im Kontenplan erfasst.
    </div>
@endif

<div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-4 mb-6 text-sm text-blue-800 dark:text-blue-200">
    <p class="font-medium">So geht es weiter</p>
    <ol class="list-decimal list-inside mt-1 space-y-0.5 text-blue-700 dark:text-blue-300">
        <li>Die Zahlungsdatei herunterladen und im Raiffeisen E-Banking unter «Zahlungen → Datei hochladen» übermitteln.</li>
        <li>Die Belastungen erscheinen einzeln auf dem Kontoauszug. Beim nächsten Bankimport werden sie mit diesen Buchungen verknüpft, nicht nochmals gebucht.</li>
        @if($outside->isNotEmpty())
            <li>{{ $outside->count() }} Empfänger ohne gültige IBAN sind nicht in der Datei – diese bitte von Hand bezahlen.</li>
        @endif
    </ol>
</div>

<div class="{{ $card }} overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr class="text-xs text-gray-500 dark:text-gray-400 text-left">
                <th class="px-5 py-2 font-medium">Empfänger</th>
                <th class="px-3 py-2 font-medium">IBAN</th>
                <th class="px-3 py-2 font-medium">Buchung</th>
                <th class="px-5 py-2 font-medium text-right">CHF</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($payout->items as $item)
                <tr>
                    <td class="px-5 py-2 text-gray-900 dark:text-gray-100">{{ $item->name }}</td>
                    <td class="px-3 py-2 text-xs">
                        @if($item->is_transferable)
                            <span class="font-mono text-gray-600 dark:text-gray-300">{{ \App\Support\Iban::format($item->iban) }}</span>
                        @else
                            <span class="text-amber-600 dark:text-amber-400">nicht in der Datei – von Hand zahlen</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ $item->booking ? $payout->payableAccount?->number . ' / ' . $payout->bankAccount?->number : 'gelöscht' }}
                        {{ $item->booking?->bank_transaction_id ? '· mit Bankauszug abgeglichen' : '' }}
                    </td>
                    <td class="px-5 py-2 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ $chf($item->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="border-t border-gray-200 dark:border-gray-700">
                <td colspan="3" class="px-5 py-2 text-right text-gray-500 dark:text-gray-400">Total</td>
                <td class="px-5 py-2 text-right tabular-nums font-medium text-gray-900 dark:text-gray-100">{{ $chf($payout->total) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
