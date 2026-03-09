@extends('admin.layouts.app')

@section('title', 'Verträge')

@php
    $typeLabels = $contractTypes->pluck('name', 'slug')->toArray();
    $typeColors = $contractTypes->pluck('color', 'slug')->toArray();
    $statusColors = ['draft' => 'bg-gray-100 text-gray-600', 'active' => 'bg-green-100 text-green-700', 'expired' => 'bg-red-100 text-red-700', 'terminated' => 'bg-orange-100 text-orange-700'];
    $statusLabels = ['draft' => 'Entwurf', 'active' => 'Aktiv', 'expired' => 'Ausgelaufen', 'terminated' => 'Gekündigt'];
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.contracts.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Suche..." class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
        <select name="status" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Status</option>
            @foreach($statusLabels as $v => $l)
                <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <select name="type" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Typen</option>
            @foreach($contractTypes as $ct)
                <option value="{{ $ct->slug }}" {{ request('type') === $ct->slug ? 'selected' : '' }}>{{ $ct->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Filtern</button>
        @if(request('search') || request('status') || request('type'))
            <a href="{{ route('admin.contracts.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.contracts.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 whitespace-nowrap">+ Neuer Vertrag</a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-admin.sortable-header column="title">Titel</x-admin.sortable-header>
                    <x-admin.sortable-header column="type">Typ</x-admin.sortable-header>
                    <x-admin.sortable-header column="status">Status</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parteien</th>
                    <x-admin.sortable-header column="start_date">Laufzeit</x-admin.sortable-header>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($contracts as $contract)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium">
                            <a href="{{ route('admin.contracts.show', $contract) }}" class="text-gray-900 hover:text-blue-600">{{ $contract->title }}</a>
                            @if($contract->contract_number)
                                <p class="text-xs text-gray-400 font-normal">{{ $contract->contract_number }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$contract->type] ?? 'bg-gray-100 text-gray-600' }}">{{ $typeLabels[$contract->type] ?? ucfirst($contract->type) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$contract->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $statusLabels[$contract->status] ?? $contract->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            @forelse($contract->parties as $party)
                                <span class="inline-flex items-center gap-1">
                                    <span>{{ $party->name }}</span>
                                    <span class="text-xs text-gray-400">({{ number_format($party->share, 0) }}%)</span>
                                </span>@if(!$loop->last), @endif
                            @empty
                                -
                            @endforelse
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $contract->start_date?->format('d.m.Y') ?? '-' }} — {{ $contract->end_date?->format('d.m.Y') ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.contracts.edit', $contract) }}" class="text-sm text-blue-600 hover:text-blue-800">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Keine Verträge gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $contracts->links() }}</div>
@endsection
