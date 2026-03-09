@extends('admin.layouts.app')

@section('title', 'Rechnungen')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.invoices.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechnungsnr. suchen..." class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
        <select name="status" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Status</option>
            @foreach(['open' => 'Offen', 'paid' => 'Bezahlt', 'overdue' => 'Überfällig'] as $value => $label)
                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="type" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Typen</option>
            @foreach(['incoming' => 'Eingehend', 'outgoing' => 'Ausgehend'] as $value => $label)
                <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Filtern</button>
    </form>
    <div class="flex gap-2">
        <a href="{{ route('admin.invoices.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 whitespace-nowrap">+ Neue Rechnung</a>
        @if($accountings->count())
            <div x-data="{ open: false, selected: '' }" class="relative">
                <button type="button" @click="open = !open" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 whitespace-nowrap">+ Neue Buchung</button>
                <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg z-20 p-3">
                    <p class="text-xs text-gray-500 mb-2">Buchhaltung wählen:</p>
                    @foreach($accountings as $acc)
                        <a href="{{ route('admin.bookings.create', $acc) }}" class="block px-3 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded">{{ $acc->name }}</a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-admin.sortable-header column="invoice_number">Rechnungsnr.</x-admin.sortable-header>
                    <x-admin.sortable-header column="type">Typ</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kontakt</th>
                    <x-admin.sortable-header column="amount">Betrag</x-admin.sortable-header>
                    <x-admin.sortable-header column="invoice_date">Datum</x-admin.sortable-header>
                    <x-admin.sortable-header column="due_date">Fällig</x-admin.sortable-header>
                    <x-admin.sortable-header column="status">Status</x-admin.sortable-header>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="hover:text-blue-600">{{ $invoice->invoice_number }}</a>
                            @if($invoice->title)
                                <div class="text-xs font-normal text-gray-500">{{ $invoice->title }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $invoice->type === 'incoming' ? 'Eingehend' : 'Ausgehend' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            @if($invoice->contact)
                                <a href="{{ route('admin.contacts.show', $invoice->contact) }}" class="hover:text-blue-600">{{ $invoice->contact->full_name }}</a>
                            @elseif($invoice->organization)
                                <a href="{{ route('admin.organizations.show', $invoice->organization) }}" class="hover:text-blue-600">{{ $invoice->organization->primary_name }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                            {{ number_format($invoice->amount, 2, '.', "'") }} {{ $invoice->currency ?? 'CHF' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $invoice->invoice_date ? $invoice->invoice_date->format('d.m.Y') : '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $invoice->due_date ? $invoice->due_date->format('d.m.Y') : '-' }}</td>
                        <td class="px-4 py-3">
                            @switch($invoice->status)
                                @case('open')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">Offen</span>
                                    @break
                                @case('paid')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Bezahlt</span>
                                    @break
                                @case('overdue')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Überfällig</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.invoices.edit', $invoice) }}" class="text-sm text-blue-600 hover:text-blue-800">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">Keine Rechnungen gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $invoices->links() }}</div>

@if($accountings->count())
<div class="mt-8">
    <h3 class="text-sm font-medium text-gray-700 mb-3">Buchungsjournale</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($accountings as $acc)
            <a href="{{ route('admin.accountings.journal', $acc) }}" class="bg-white rounded-lg border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-sm transition-all">
                <div class="text-sm font-medium text-gray-900">{{ $acc->name }}</div>
                <div class="text-xs text-gray-500 mt-0.5">{{ $acc->accountable_name }}</div>
                <div class="text-xs text-gray-400 mt-1">{{ $acc->period_start?->format('d.m.Y') }} – {{ $acc->period_end?->format('d.m.Y') }}</div>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-gray-500">{{ $acc->bookings_count }} {{ $acc->bookings_count === 1 ? 'Buchung' : 'Buchungen' }}</span>
                    <span class="text-sm font-medium text-gray-900">{{ number_format($acc->bookings_sum_amount ?? 0, 2, '.', "'") }} {{ $acc->currency }}</span>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endif
@endsection
