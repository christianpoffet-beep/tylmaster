@extends('admin.layouts.app')

@section('title', 'Ausgaben')

@php
    $input = 'rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
    $statusColors = [
        'open' => 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300',
        'bank' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300',
        'direct' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300',
        'unbooked' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
    ];
    $statusHints = [
        'open' => 'Wartet auf die Zahlung im Bankabgleich',
        'bank' => 'Über die Bank bezahlt und gebucht',
        'direct' => 'Bar oder privat bezahlt und gebucht',
        'unbooked' => 'Keine Buchhaltung oder kein Aufwandkonto gewählt',
    ];
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.expenses.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Beschreibung suchen..." class="{{ $input }}">
        <select name="status" class="{{ $input }}">
            <option value="">Alle Status</option>
            @foreach(\App\Models\Expense::STATUS_LABELS as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @if($accountings->count())
            <select name="accounting_id" class="{{ $input }}">
                <option value="">Alle Buchhaltungen</option>
                @foreach($accountings as $accounting)
                    <option value="{{ $accounting->id }}" @selected(request('accounting_id') == $accounting->id)>{{ $accounting->name }}</option>
                @endforeach
            </select>
        @endif
        <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-500">Filtern</button>
    </form>
    <a href="{{ route('admin.expenses.create') }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 whitespace-nowrap">+ Neue Ausgabe</a>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <x-admin.sortable-header column="expense_date">Datum</x-admin.sortable-header>
                    <x-admin.sortable-header column="description">Beschreibung</x-admin.sortable-header>
                    <x-admin.sortable-header column="amount">Betrag</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Konto</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kontakt</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($expenses as $expense)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $expense->expense_date ? $expense->expense_date->format('d.m.Y') : '-' }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $expense->description }}
                            @if($expense->documents_count)
                                <svg class="w-3.5 h-3.5 inline ml-1 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><title>{{ $expense->documents_count }} Beleg(e)</title><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            @endif
                            @if($expense->accounting)
                                <p class="text-xs font-normal text-gray-400 dark:text-gray-500">{{ $expense->accounting->name }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 font-medium whitespace-nowrap">
                            {{ number_format($expense->amount, 2, '.', "'") }} {{ $expense->currency ?? 'CHF' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $expense->account ? $expense->account->number . ' ' . $expense->account->name : '-' }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$expense->status] }}" title="{{ $statusHints[$expense->status] }}">{{ \App\Models\Expense::STATUS_LABELS[$expense->status] }}</span>
                            @if($expense->booking)
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">gebucht {{ $expense->booking->booking_date->format('d.m.Y') }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            @if($expense->contact)
                                <a href="{{ route('admin.contacts.show', $expense->contact) }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ $expense->contact->full_name }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.expenses.edit', $expense) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Bearbeiten</a>
                                <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}"
                                      onsubmit="return confirm('{{ $expense->booking && !$expense->booking->bank_transaction_id ? 'Ausgabe und ihre Buchung löschen?' : 'Ausgabe wirklich löschen?' }}')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">Löschen</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Keine Ausgaben gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $expenses->links() }}</div>
@endsection
