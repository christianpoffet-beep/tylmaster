@extends('admin.layouts.app')

@section('title', 'Bilanz – ' . $accounting->name)

@php
    $totalAssetsOpening = $assets->sum('opening_balance');
    $totalAssetsClosing = $assets->sum(fn($a) => $a->balance);

    $totalLiabilitiesOpening = $liabilities->sum('opening_balance');
    $totalLiabilitiesClosing = $liabilities->sum(fn($a) => $a->balance);

    // Gewinn/Verlust = Differenz Aktiven - Passiven (Schlussbilanz)
    $profitLoss = $totalAssetsClosing - $totalLiabilitiesClosing;
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900">Bilanz</h2>
        <p class="text-sm text-gray-500 mt-1">{{ $accounting->name }} · {{ $accounting->accountable_name }} · {{ $accounting->period_start->format('d.m.Y') }} – {{ $accounting->period_end->format('d.m.Y') }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.accountings.incomeStatement', $accounting) }}" class="px-4 py-2 bg-white text-gray-700 text-sm rounded-lg border border-gray-300 hover:bg-gray-50">Erfolgsrechnung</a>
        <a href="{{ route('admin.accountings.show', $accounting) }}" class="px-4 py-2 bg-white text-gray-700 text-sm rounded-lg border border-gray-300 hover:bg-gray-50">Zurück</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Aktiven --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 bg-blue-50 border-b border-blue-100">
            <h3 class="text-sm font-semibold text-blue-800">Aktiven</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-20">Nr.</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bezeichnung</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-28">Eröffnung</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-28">Abschluss</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($assets->sortBy('sort_order') as $account)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $account->number }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                <a href="{{ route('admin.accountings.ledger', [$accounting, $account]) }}" class="hover:text-blue-600">{{ $account->name }}</a>
                            </td>
                            <td class="px-4 py-2 text-sm text-right font-mono text-gray-500">{{ number_format($account->opening_balance, 2, '.', "'") }}</td>
                            <td class="px-4 py-2 text-sm text-right font-mono {{ $account->balance < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($account->balance, 2, '.', "'") }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr class="font-bold">
                        <td colspan="2" class="px-4 py-3 text-sm text-gray-900">Total Aktiven</td>
                        <td class="px-4 py-3 text-sm text-right font-mono text-gray-700">{{ number_format($totalAssetsOpening, 2, '.', "'") }}</td>
                        <td class="px-4 py-3 text-sm text-right font-mono text-gray-900">{{ number_format($totalAssetsClosing, 2, '.', "'") }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Passiven --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 bg-purple-50 border-b border-purple-100">
            <h3 class="text-sm font-semibold text-purple-800">Passiven</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-20">Nr.</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bezeichnung</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-28">Eröffnung</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-28">Abschluss</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($liabilities->sortBy('sort_order') as $account)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $account->number }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                <a href="{{ route('admin.accountings.ledger', [$accounting, $account]) }}" class="hover:text-blue-600">{{ $account->name }}</a>
                            </td>
                            <td class="px-4 py-2 text-sm text-right font-mono text-gray-500">{{ number_format($account->opening_balance, 2, '.', "'") }}</td>
                            <td class="px-4 py-2 text-sm text-right font-mono {{ $account->balance < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($account->balance, 2, '.', "'") }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr class="font-bold">
                        <td colspan="2" class="px-4 py-3 text-sm text-gray-900">Total Passiven</td>
                        <td class="px-4 py-3 text-sm text-right font-mono text-gray-700">{{ number_format($totalLiabilitiesOpening, 2, '.', "'") }}</td>
                        <td class="px-4 py-3 text-sm text-right font-mono text-gray-900">{{ number_format($totalLiabilitiesClosing, 2, '.', "'") }}</td>
                    </tr>
                    @if(abs($profitLoss) > 0.01)
                    <tr class="font-bold border-t border-gray-300">
                        <td colspan="2" class="px-4 py-3 text-sm {{ $profitLoss >= 0 ? 'text-green-700' : 'text-red-700' }}">
                            {{ $profitLoss >= 0 ? 'Gewinn' : 'Verlust' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-mono"></td>
                        <td class="px-4 py-3 text-sm text-right font-mono {{ $profitLoss >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format(abs($profitLoss), 2, '.', "'") }}</td>
                    </tr>
                    <tr class="font-bold border-t border-gray-300">
                        <td colspan="2" class="px-4 py-3 text-sm text-gray-900">Total Passiven + {{ $profitLoss >= 0 ? 'Gewinn' : 'Verlust' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-mono"></td>
                        <td class="px-4 py-3 text-sm text-right font-mono text-gray-900">{{ number_format($totalLiabilitiesClosing + abs($profitLoss) * ($profitLoss >= 0 ? 1 : -1), 2, '.', "'") }}</td>
                    </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Zusammenfassung --}}
<div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-sm font-semibold text-gray-900 mb-4">Zusammenfassung</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">Eröffnungsbilanz</h4>
            <div class="space-y-1 text-sm">
                <div class="flex justify-between"><span class="text-gray-600">Aktiven</span><span class="font-mono">{{ number_format($totalAssetsOpening, 2, '.', "'") }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Passiven</span><span class="font-mono">{{ number_format($totalLiabilitiesOpening, 2, '.', "'") }}</span></div>
                <div class="flex justify-between border-t pt-1 font-medium {{ abs($totalAssetsOpening - $totalLiabilitiesOpening) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                    <span>Differenz</span>
                    <span class="font-mono">{{ number_format($totalAssetsOpening - $totalLiabilitiesOpening, 2, '.', "'") }}</span>
                </div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">Schlussbilanz</h4>
            <div class="space-y-1 text-sm">
                <div class="flex justify-between"><span class="text-gray-600">Aktiven</span><span class="font-mono">{{ number_format($totalAssetsClosing, 2, '.', "'") }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Passiven</span><span class="font-mono">{{ number_format($totalLiabilitiesClosing, 2, '.', "'") }}</span></div>
                <div class="flex justify-between border-t pt-1 font-medium {{ $profitLoss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    <span>{{ $profitLoss >= 0 ? 'Gewinn' : 'Verlust' }}</span>
                    <span class="font-mono">{{ number_format(abs($profitLoss), 2, '.', "'") }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
