@extends('admin.layouts.app')

@section('title', 'Releases')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.releases.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Titel oder UPC suchen..." class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Filtern</button>
    </form>
    <a href="{{ route('admin.releases.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 whitespace-nowrap">+ Neues Release</a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-admin.sortable-header column="title">Titel</x-admin.sortable-header>
                    <x-admin.sortable-header column="upc">UPC</x-admin.sortable-header>
                    <x-admin.sortable-header column="release_date">Release-Datum</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tracks</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($releases as $release)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            <a href="{{ route('admin.releases.show', $release) }}" class="hover:text-blue-600">{{ $release->title }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 font-mono">{{ $release->upc ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $release->release_date ? $release->release_date->format('d.m.Y') : '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $release->label ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $release->tracks_count ?? $release->tracks->count() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.releases.edit', $release) }}" class="text-sm text-blue-600 hover:text-blue-800">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Keine Releases gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $releases->links() }}</div>
@endsection
