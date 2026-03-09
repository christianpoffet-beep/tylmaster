@extends('admin.layouts.app')

@section('title', 'Kontoblatt ' . $account->number . ' – ' . $account->name)

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900">{{ $account->number }} {{ $account->name }}</h2>
        <p class="text-sm text-gray-500 mt-1">{{ $accounting->name }} · {{ $accounting->accountable_name }}</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.accountings.show', $accounting) }}" class="px-4 py-2 bg-white text-gray-700 text-sm rounded-lg border border-gray-300 hover:bg-gray-50">Kontenplan</a>
        <a href="{{ route('admin.accountings.trialBalance', $accounting) }}" class="px-4 py-2 bg-white text-gray-700 text-sm rounded-lg border border-gray-300 hover:bg-gray-50">Probebilanz</a>
    </div>
</div>

{{-- Opening Balance --}}
<div class="mb-4 text-sm text-gray-600">
    Eröffnungssaldo: <span class="font-mono font-medium">{{ number_format($account->opening_balance, 2, '.', "'") }} {{ $accounting->currency }}</span>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-28">Datum</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Beleg</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Beschreibung</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gegenkonto</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Soll</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Haben</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Saldo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @php
                    $runningBalance = (float) $account->opening_balance;
                    $isDebitAccount = in_array($account->type, ['asset', 'expense']);
                @endphp
                @forelse($bookings as $booking)
                    @php
                        $fullBooking = $allBookings[$booking->id] ?? null;
                        $isDebit = $booking->debit_account_id === $account->id;
                        if ($isDebitAccount) {
                            $runningBalance += $isDebit ? $booking->amount : -$booking->amount;
                        } else {
                            $runningBalance += $isDebit ? -$booking->amount : $booking->amount;
                        }
                        $counterAccount = $isDebit ? $fullBooking?->creditAccount : $fullBooking?->debitAccount;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $booking->booking_date->format('d.m.Y') }}</td>
                        <td class="px-4 py-2 text-sm text-gray-400">{{ $booking->reference ?? '—' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $booking->description }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $counterAccount ? $counterAccount->number . ' ' . $counterAccount->name : '' }}</td>
                        <td class="px-4 py-2 text-sm text-right font-mono text-gray-700">{{ $isDebit ? number_format($booking->amount, 2, '.', "'") : '' }}</td>
                        <td class="px-4 py-2 text-sm text-right font-mono text-gray-700">{{ !$isDebit ? number_format($booking->amount, 2, '.', "'") : '' }}</td>
                        <td class="px-4 py-2 text-sm text-right font-mono font-medium {{ $runningBalance < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($runningBalance, 2, '.', "'") }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">Keine Buchungen für dieses Konto.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($bookings->count())
            <tfoot class="bg-gray-50">
                <tr class="font-bold">
                    <td colspan="4" class="px-4 py-3 text-sm text-gray-900 text-right">Schlusssaldo:</td>
                    <td class="px-4 py-3 text-sm text-right font-mono text-gray-700">{{ number_format($account->debit_total, 2, '.', "'") }}</td>
                    <td class="px-4 py-3 text-sm text-right font-mono text-gray-700">{{ number_format($account->credit_total, 2, '.', "'") }}</td>
                    <td class="px-4 py-3 text-sm text-right font-mono {{ $runningBalance < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($runningBalance, 2, '.', "'") }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
