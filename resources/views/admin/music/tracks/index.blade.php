@extends('admin.layouts.app')

@section('title', 'Tracks')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.tracks.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Titel oder ISRC suchen..." class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Filtern</button>
    </form>
    <a href="{{ route('admin.tracks.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 whitespace-nowrap">+ Neuer Track</a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-admin.sortable-header column="title">Titel</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Artist(s)</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Genre</th>
                    <x-admin.sortable-header column="isrc">ISRC</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dauer</th>
                    <x-admin.sortable-header column="status">Status</x-admin.sortable-header>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($tracks as $track)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            <a href="{{ route('admin.tracks.show', $track) }}" class="hover:text-blue-600">{{ $track->title }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $track->contacts->where('pivot.role', 'artist')->pluck('full_name')->join(', ') ?: '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $track->genre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 font-mono">{{ $track->isrc ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $track->formatted_duration ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @switch($track->status)
                                @case('draft')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Draft</span>
                                    @break
                                @case('released')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Released</span>
                                    @break
                                @case('archived')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">Archived</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.tracks.edit', $track) }}" class="text-sm text-blue-600 hover:text-blue-800">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">Keine Tracks gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $tracks->links() }}</div>
@endsection
