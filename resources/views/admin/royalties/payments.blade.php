@extends('admin.layouts.app')

@section('title', 'Rebeat-Zahlungen')

@php
    $chf = fn ($value) => number_format((float) $value, 2, '.', "'");
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
    $input = 'rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
@endphp

@section('content')
@include('admin.royalties._tabs')

<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Rebeat-Zahlungen</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Was Rebeat ausbezahlt hat, in CHF wie es auf dem Konto ankam. Eine Abrechnung verteilt die Zahlungen ihres Zeitraums nach der Nutzung.</p>
    @if($newFromBank)
        <p class="text-sm text-green-700 dark:text-green-400 mt-2">{{ $newFromBank }} neue Zahlung(en) aus dem Bankimport übernommen.</p>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="{{ $card }} p-5">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">Aus Kontoauszügen</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Für Jahre ohne Buchhaltung im Tool: CSV aus dem E-Banking (wie beim Bankimport). Nur die Zeilen von Rebeat werden übernommen, es wird nichts gebucht. Zahlungen aus dem Bankimport der Buchhaltungen kommen von selbst.</p>
        <form method="POST" action="{{ route('admin.royalties.payments.upload') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="file" name="files[]" multiple accept=".csv,text/csv,text/plain" required class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300">
            @error('files') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Einlesen</button>
        </form>
    </div>

    <div class="{{ $card }} p-5" x-data="{ open: false }">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">Von Hand</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Falls eine Zahlung in keinem Auszug steht.</p>
        <button type="button" @click="open = !open" x-show="!open" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">+ Zahlung erfassen</button>
        <form x-show="open" x-cloak method="POST" action="{{ route('admin.royalties.payments.store') }}" class="space-y-2">
            @csrf
            <div class="grid grid-cols-2 gap-2">
                <input type="date" name="paid_at" required class="{{ $input }}">
                <input type="number" name="amount" step="0.01" min="0.01" required placeholder="CHF" class="{{ $input }}">
                <input type="number" name="amount_foreign" step="0.01" min="0.01" placeholder="EUR (optional)" class="{{ $input }}">
                <input type="text" name="text" maxlength="255" placeholder="Notiz" class="{{ $input }}">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Speichern</button>
        </form>
    </div>

    <div class="{{ $card }} p-5">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Pro Jahr</h3>
        @if($years->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">Noch keine Zahlungen.</p>
        @else
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($years as $year => $row)
                        <tr>
                            <td class="py-1.5 text-gray-900 dark:text-gray-100">{{ $year }}</td>
                            <td class="py-1.5 text-right text-gray-500 dark:text-gray-400">{{ $row['count'] }}×</td>
                            <td class="py-1.5 text-right tabular-nums text-gray-500 dark:text-gray-400">EUR {{ $chf($row['foreign']) }}</td>
                            <td class="py-1.5 text-right tabular-nums text-gray-900 dark:text-gray-100">CHF {{ $chf($row['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<div class="{{ $card }} overflow-hidden">
    @if($payments->isEmpty())
        <p class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">Noch keine Zahlungen.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-xs text-gray-500 dark:text-gray-400 text-left">
                        <th class="px-5 py-2 font-medium">Datum</th>
                        <th class="px-3 py-2 font-medium text-right">EUR</th>
                        <th class="px-3 py-2 font-medium text-right">Kurs</th>
                        <th class="px-3 py-2 font-medium text-right">CHF</th>
                        <th class="px-3 py-2 font-medium">Quelle</th>
                        <th class="px-3 py-2 font-medium">Verteilt in</th>
                        <th class="px-5 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($payments as $payment)
                        <tr>
                            <td class="px-5 py-2 text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ $payment->paid_at->format('d.m.Y') }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $payment->amount_foreign !== null ? $chf($payment->amount_foreign) : '—' }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-500 dark:text-gray-400">{{ $payment->rate ? rtrim(rtrim(number_format((float) $payment->rate, 4, '.', ''), '0'), '.') : '—' }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ $chf($payment->amount) }}</td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ \App\Models\RoyaltyPayment::ORIGINS[$payment->origin] ?? $payment->origin }}</td>
                            <td class="px-3 py-2">
                                @if($payment->statement)
                                    <a href="{{ route('admin.royalties.statements.show', $payment->statement) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $payment->statement->title }}</a>
                                @else
                                    <span class="text-gray-400">offen</span>
                                @endif
                            </td>
                            <td class="px-5 py-2 text-right">
                                @if($payment->origin !== 'bank' && !$payment->statement?->is_final)
                                    <form method="POST" action="{{ route('admin.royalties.payments.destroy', $payment) }}" onsubmit="return confirm('Diese Zahlung entfernen?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 dark:text-red-400 hover:underline">entfernen</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
