@extends('admin.layouts.app')

@section('title', 'Organisationen')

@php
    $typeLabels = $orgTypes->pluck('name', 'slug')->toArray();
    $typeColors = $orgTypes->pluck('color', 'slug')->toArray();
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Organisationen</h1>
    <div class="flex gap-2">
        <a href="{{ route('admin.organizations.export', request()->query()) }}" class="px-4 py-2 bg-green-600 dark:bg-green-700 text-white text-sm rounded-lg hover:bg-green-700 dark:hover:bg-green-600 whitespace-nowrap">
            <svg class="inline w-4 h-4 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Excel Export
        </a>
        <a href="{{ route('admin.organizations.create') }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 whitespace-nowrap">+ Neue Organisation</a>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
    <form method="GET" action="{{ route('admin.organizations.index') }}" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Suche</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Typ</label>
            <select name="type" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Alle Typen</option>
                @foreach($typeLabels as $v => $l)
                    <option value="{{ $v }}" {{ request('type') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Genre</label>
            <select name="genre" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Alle Genres</option>
                @foreach($genres as $g)
                    <option value="{{ $g->id }}" {{ request('genre') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Ort</label>
            <input type="text" name="city" value="{{ request('city') }}" placeholder="Ort..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Land</label>
            <select name="country" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Alle Länder</option>
                @foreach($countries as $c)
                    <option value="{{ $c }}" {{ request('country') === $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-500">Filtern</button>
            @if(request()->hasAny(['search', 'type', 'genre', 'city', 'country']))
                <a href="{{ route('admin.organizations.index') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200" title="Zurücksetzen">&times;</a>
            @endif
        </div>
    </form>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ref</th>
                    <x-admin.sortable-header column="names">Name</x-admin.sortable-header>
                    <x-admin.sortable-header column="type">Typ</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Genre</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ort</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kontakte</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($organizations as $org)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">
                        <td class="px-4 py-3 text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $org->ref_nr }}</td>
                        <td class="px-4 py-3 text-sm font-medium">
                            <a href="{{ route('admin.organizations.show', $org) }}" class="text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">{{ $org->primary_name }}</a>
                            @if(count($org->names) > 1)
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ implode(', ', array_slice($org->names, 1)) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$org->type] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">{{ $typeLabels[$org->type] ?? $org->type }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $org->genres->pluck('name')->implode(', ') ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $org->city ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $org->contacts_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.organizations.edit', $org) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Keine Organisationen gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 flex items-center justify-between">
    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $organizations->total() }} Organisationen gefunden</span>
    {{ $organizations->links() }}
</div>
@endsection
