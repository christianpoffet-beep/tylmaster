@extends('admin.layouts.app')

@section('title', 'Erfolgsrechnung – ' . $accounting->name)

@php
    $totalIncome = $incomeAccounts->sum(fn($a) => $a->balance);
    $totalExpenses = $expenseAccounts->sum(fn($a) => $a->balance);
    $profitLoss = $totalIncome - $totalExpenses;
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Erfolgsrechnung</h2>
        <p class="text-sm text-gray-500 mt-1">{{ $accounting->name }} · {{ $accounting->accountable_name }} · {{ $accounting->period_start->format('d.m.Y') }} – {{ $accounting->period_end->format('d.m.Y') }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.accountings.balanceSheet', $accounting) }}" class="px-4 py-2 bg-white text-gray-700 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Bilanz</a>
        <a href="{{ route('admin.accountings.show', $accounting) }}" class="px-4 py-2 bg-white text-gray-700 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Zurück</a>
    </div>
</div>

{{-- Ergebnis --}}
<div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ertrag</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($totalIncome, 2, '.', "'") }} {{ $accounting->currency }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Aufwand</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($totalExpenses, 2, '.', "'") }} {{ $accounting->currency }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $profitLoss >= 0 ? 'Gewinn' : 'Verlust' }}</p>
            <p class="text-2xl font-bold mt-1 {{ $profitLoss >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format(abs($profitLoss), 2, '.', "'") }} {{ $accounting->currency }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Ertrag --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 bg-green-50 border-b border-green-100">
            <h3 class="text-sm font-semibold text-green-800">Ertrag</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-20">Nr.</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bezeichnung</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-32">Betrag</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($incomeAccounts->sortBy('sort_order') as $account)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $account->number }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                <a href="{{ route('admin.accountings.ledger', [$accounting, $account]) }}" class="hover:text-blue-600">{{ $account->name }}</a>
                            </td>
                            <td class="px-4 py-2 text-sm text-right font-mono {{ $account->balance < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($account->balance, 2, '.', "'") }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-4 text-sm text-gray-400 text-center">Keine Ertragskonten vorhanden</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="font-bold">
                        <td colspan="2" class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">Total Ertrag</td>
                        <td class="px-4 py-3 text-sm text-right font-mono text-green-700">{{ number_format($totalIncome, 2, '.', "'") }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Aufwand --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 bg-red-50 border-b border-red-100">
            <h3 class="text-sm font-semibold text-red-800">Aufwand</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-20">Nr.</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bezeichnung</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-32">Betrag</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($expenseAccounts->sortBy('sort_order') as $account)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $account->number }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                <a href="{{ route('admin.accountings.ledger', [$accounting, $account]) }}" class="hover:text-blue-600">{{ $account->name }}</a>
                            </td>
                            <td class="px-4 py-2 text-sm text-right font-mono {{ $account->balance < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($account->balance, 2, '.', "'") }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-4 text-sm text-gray-400 text-center">Keine Aufwandskonten vorhanden</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="font-bold">
                        <td colspan="2" class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">Total Aufwand</td>
                        <td class="px-4 py-3 text-sm text-right font-mono text-red-700">{{ number_format($totalExpenses, 2, '.', "'") }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Ergebnis --}}
<div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <table class="min-w-full">
        <tbody>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300">Total Ertrag</td>
                <td class="px-6 py-3 text-sm text-right font-mono text-green-700 font-medium">{{ number_format($totalIncome, 2, '.', "'") }}</td>
            </tr>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300">Total Aufwand</td>
                <td class="px-6 py-3 text-sm text-right font-mono text-red-700 font-medium">- {{ number_format($totalExpenses, 2, '.', "'") }}</td>
            </tr>
            <tr class="bg-gray-50 dark:bg-gray-700/50">
                <td class="px-6 py-4 text-sm font-bold {{ $profitLoss >= 0 ? 'text-green-700' : 'text-red-700' }}">
                    {{ $profitLoss >= 0 ? 'Gewinn' : 'Verlust' }}
                </td>
                <td class="px-6 py-4 text-right font-mono text-lg font-bold {{ $profitLoss >= 0 ? 'text-green-700' : 'text-red-700' }}">
                    {{ number_format(abs($profitLoss), 2, '.', "'") }} {{ $accounting->currency }}
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
