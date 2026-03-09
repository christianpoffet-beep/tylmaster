@extends('admin.layouts.app')

@section('title', 'Organisationen')

@php
    $orgTypes = \App\Models\OrganizationType::orderBy('sort_order')->get();
    $typeLabels = $orgTypes->pluck('name', 'slug')->toArray();
    $typeColors = $orgTypes->pluck('color', 'slug')->toArray();
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.organizations.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name suchen..." class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
        <select name="type" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Typen</option>
            @foreach($typeLabels as $v => $l)
                <option value="{{ $v }}" {{ request('type') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Filtern</button>
        @if(request('search') || request('type'))
            <a href="{{ route('admin.organizations.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.organizations.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 whitespace-nowrap">+ Neue Organisation</a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-admin.sortable-header column="names">Name</x-admin.sortable-header>
                    <x-admin.sortable-header column="type">Typ</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kontakte</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Websites</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($organizations as $org)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium">
                            <a href="{{ route('admin.organizations.show', $org) }}" class="text-gray-900 hover:text-blue-600">{{ $org->primary_name }}</a>
                            @if(count($org->names) > 1)
                                <p class="text-xs text-gray-400 truncate">{{ implode(', ', array_slice($org->names, 1)) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$org->type] ?? 'bg-gray-100 text-gray-600' }}">{{ $typeLabels[$org->type] ?? $org->type }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $org->contacts_count }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ count($org->websites ?? []) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.organizations.edit', $org) }}" class="text-sm text-blue-600 hover:text-blue-800">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Keine Organisationen gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $organizations->links() }}</div>
@endsection
