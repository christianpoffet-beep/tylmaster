@extends('admin.layouts.app')

@section('title', $accounting->name)

@php
    $typeLabels = ['asset' => 'Aktiven', 'liability' => 'Passiven', 'income' => 'Ertrag', 'expense' => 'Aufwand'];
    $typeColors = ['asset' => 'bg-blue-100 text-blue-700', 'liability' => 'bg-purple-100 text-purple-700', 'income' => 'bg-green-100 text-green-700', 'expense' => 'bg-red-100 text-red-700'];
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900">{{ $accounting->name }}</h2>
        <p class="text-sm text-gray-500 mt-1">
            {{ $accounting->accountable_name }}
            · {{ $accounting->period_start->format('d.m.Y') }} – {{ $accounting->period_end->format('d.m.Y') }}
            · {{ $accounting->currency }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        @if($accounting->status === 'open')
            <form method="POST" action="{{ route('admin.accountings.close', $accounting) }}" onsubmit="return confirm('Buchhaltung abschliessen?')">
                @csrf @method('PATCH')
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Abschliessen</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.accountings.reopen', $accounting) }}">
                @csrf @method('PATCH')
                <button class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">Wieder öffnen</button>
            </form>
        @endif
        <a href="{{ route('admin.accountings.edit', $accounting) }}" class="px-4 py-2 bg-white text-gray-700 text-sm rounded-lg border border-gray-300 hover:bg-gray-50">Bearbeiten</a>
    </div>
</div>

{{-- Status --}}
<div class="mb-6">
    @if($accounting->status === 'open')
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">Offen</span>
    @else
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">Abgeschlossen</span>
    @endif
</div>

{{-- Quick Stats --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <p class="text-xs font-medium text-gray-500 uppercase">
            Buchungen
            <span x-data="{ show: false }" class="relative inline-block ml-1">
                <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-gray-500 text-xs hover:bg-gray-300 focus:outline-none">?</button>
                <div x-show="show" x-transition class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg normal-case">
                    Anzahl aller erfassten Buchungen in dieser Buchhaltung (Soll an Haben).
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                </div>
            </span>
        </p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $bookingsCount }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <p class="text-xs font-medium text-gray-500 uppercase">
            Ertrag
            <span x-data="{ show: false }" class="relative inline-block ml-1">
                <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-gray-500 text-xs hover:bg-gray-300 focus:outline-none">?</button>
                <div x-show="show" x-transition class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg normal-case">
                    Summe aller Einnahmen (Ertragskonten 3xxx-8xxx). Buchungen, die auf Ertragskonten gutgeschrieben wurden.
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                </div>
            </span>
        </p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($totalIncome, 2, '.', "'") }} {{ $accounting->currency }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <p class="text-xs font-medium text-gray-500 uppercase">
            Aufwand
            <span x-data="{ show: false }" class="relative inline-block ml-1">
                <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-gray-500 text-xs hover:bg-gray-300 focus:outline-none">?</button>
                <div x-show="show" x-transition class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg normal-case">
                    Summe aller Ausgaben (Aufwandkonten 4xxx-7xxx). Buchungen, die Aufwandkonten belastet haben.
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                </div>
            </span>
        </p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($totalExpenses, 2, '.', "'") }} {{ $accounting->currency }}</p>
    </div>
</div>

{{-- Grafiken --}}
@if($totalIncome > 0 || $totalExpenses > 0)
@php $profit = $totalIncome - $totalExpenses; @endphp
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
    {{-- Ertrag vs. Aufwand --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3">
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Ertrag / Aufwand</p>
        <div class="flex items-center gap-3">
            <div class="w-16 h-16 flex-shrink-0">
                <canvas id="revenueExpenseChart"></canvas>
            </div>
            <div class="min-w-0">
                <p class="text-xs {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                    {{ $profit >= 0 ? 'Gewinn' : 'Verlust' }}: {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit, 2, '.', "'") }}
                </p>
            </div>
        </div>
    </div>

    {{-- Top Ertragskonten --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3">
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Top Ertrag</p>
        @if($incomeByAccount->count())
            <div style="height: 60px;"><canvas id="incomeChart"></canvas></div>
        @else
            <p class="text-xs text-gray-400 py-4">Keine Daten</p>
        @endif
    </div>

    {{-- Top Aufwandkonten --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3">
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Top Aufwand</p>
        @if($expenseByAccount->count())
            <div style="height: 60px;"><canvas id="expenseChart"></canvas></div>
        @else
            <p class="text-xs text-gray-400 py-4">Keine Daten</p>
        @endif
    </div>
</div>
@endif

{{-- Navigation Links --}}
<div class="flex flex-wrap gap-3 mb-6">
    <a href="{{ route('admin.accountings.journal', $accounting) }}" class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Buchungsjournal</a>
    <a href="{{ route('admin.accountings.trialBalance', $accounting) }}" class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Probebilanz</a>
    <div class="relative inline-flex items-center gap-1">
        <a href="{{ route('admin.accountings.balanceSheet', $accounting) }}" class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Bilanz</a>
        <span x-data="{ show: false }" class="relative">
            <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-gray-500 text-xs hover:bg-gray-300 focus:outline-none">?</button>
            <div x-show="show" x-transition class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg">
                Zeigt Aktiven vs. Passiven mit Eröffnungs- und Schlusssaldo.
                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
            </div>
        </span>
    </div>
    <div class="relative inline-flex items-center gap-1">
        <a href="{{ route('admin.accountings.incomeStatement', $accounting) }}" class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Erfolgsrechnung</a>
        <span x-data="{ show: false }" class="relative">
            <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-gray-500 text-xs hover:bg-gray-300 focus:outline-none">?</button>
            <div x-show="show" x-transition class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg">
                Zeigt Ertrag vs. Aufwand und den Gewinn/Verlust der Periode.
                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
            </div>
        </span>
    </div>
    @if(!$accounting->is_closed)
        <a href="{{ route('admin.bookings.create', $accounting) }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">+ Neue Buchung</a>
    @endif
</div>

{{-- Kontenplan --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
        <h3 class="text-sm font-medium text-gray-900">Kontenplan</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Nr.</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bezeichnung</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-28">Typ</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-32">Saldo</th>
                    <th class="px-4 py-2 w-20"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($accounting->accounts as $account)
                    <tr class="hover:bg-gray-50 {{ $account->is_header ? 'bg-gray-50' : '' }}">
                        <td class="px-4 py-2 text-sm {{ $account->is_header ? 'font-bold' : 'pl-8' }}">{{ $account->number }}</td>
                        <td class="px-4 py-2 text-sm {{ $account->is_header ? 'font-bold' : '' }}">
                            @if(!$account->is_header)
                                <a href="{{ route('admin.accountings.ledger', [$accounting, $account]) }}" class="text-gray-900 hover:text-blue-600">{{ $account->name }}</a>
                            @else
                                {{ $account->name }}
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$account->type] ?? '' }}">{{ $typeLabels[$account->type] ?? $account->type }}</span>
                        </td>
                        <td class="px-4 py-2 text-sm text-right font-mono {{ $account->is_header ? '' : ($account->balance < 0 ? 'text-red-600' : 'text-gray-900') }}">
                            @unless($account->is_header)
                                {{ number_format($account->balance, 2, '.', "'") }}
                            @endunless
                        </td>
                        <td class="px-4 py-2 text-right">
                            @if(!$account->is_header && !$account->has_bookings && !$accounting->is_closed)
                                <form method="POST" action="{{ route('admin.accountings.accounts.destroy', $account) }}" class="inline" onsubmit="return confirm('Konto löschen?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-500 hover:text-red-700">Löschen</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Konto hinzufügen --}}
@if(!$accounting->is_closed)
<div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-sm font-medium text-gray-900 mb-4">Konto hinzufügen</h3>
    <form method="POST" action="{{ route('admin.accountings.accounts.store', $accounting) }}">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-6 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Nr. *</label>
                <input type="text" name="number" required placeholder="1000" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Bezeichnung *</label>
                <input type="text" name="name" required placeholder="Kasse" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Typ *</label>
                <select name="type" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($typeLabels as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Eröffnungssaldo</label>
                <input type="number" name="opening_balance" value="0" step="0.01" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Hinzufügen</button>
            </div>
        </div>
    </form>
</div>
@endif

@if($accounting->notes)
<div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-sm font-medium text-gray-900 mb-2">Notizen</h3>
    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $accounting->notes }}</p>
</div>
@endif
@endsection

@if($totalIncome > 0 || $totalExpenses > 0)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var currency = @json($accounting->currency);
    var fmt = function(v) { return v.toLocaleString('de-CH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ' + currency; };

    // Doughnut: Ertrag vs. Aufwand
    new Chart(document.getElementById('revenueExpenseChart'), {
        type: 'doughnut',
        data: {
            labels: ['Ertrag', 'Aufwand'],
            datasets: [{
                data: [{{ $totalIncome }}, {{ $totalExpenses }}],
                backgroundColor: ['#22c55e', '#ef4444'],
                borderWidth: 1,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '55%',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function(ctx) { return ctx.label + ': ' + fmt(ctx.parsed); } } }
            }
        }
    });

    @if($incomeByAccount->count())
    // Bar: Top Ertragskonten
    new Chart(document.getElementById('incomeChart'), {
        type: 'bar',
        data: {
            labels: @json($incomeByAccount->pluck('name')),
            datasets: [{
                data: @json($incomeByAccount->pluck('balance')),
                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                borderColor: '#22c55e',
                borderWidth: 1,
                borderRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            layout: { padding: 0 },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function(ctx) { return fmt(ctx.parsed.x); } } }
            },
            scales: {
                x: { display: false },
                y: { grid: { display: false }, ticks: { font: { size: 8 }, padding: 2 } }
            }
        }
    });
    @endif

    @if($expenseByAccount->count())
    // Bar: Top Aufwandkonten
    new Chart(document.getElementById('expenseChart'), {
        type: 'bar',
        data: {
            labels: @json($expenseByAccount->pluck('name')),
            datasets: [{
                data: @json($expenseByAccount->pluck('balance')),
                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                borderColor: '#ef4444',
                borderWidth: 1,
                borderRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            layout: { padding: 0 },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function(ctx) { return fmt(ctx.parsed.x); } } }
            },
            scales: {
                x: { display: false },
                y: { grid: { display: false }, ticks: { font: { size: 8 }, padding: 2 } }
            }
        }
    });
    @endif
});
</script>
@endpush
@endif
