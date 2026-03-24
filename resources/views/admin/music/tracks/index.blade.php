@extends('admin.layouts.app')

@section('title', 'Tracks')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.tracks.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Titel oder ISRC suchen..." class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        <select name="status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Status</option>
            @foreach(['draft' => 'Draft', 'released' => 'Released', 'archived' => 'Archived'] as $v => $l)
                <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <select name="genre" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Genres</option>
            @foreach($genres as $genre)
                <option value="{{ $genre->id }}" {{ request('genre') == $genre->id ? 'selected' : '' }}>{{ $genre->name }}</option>
            @endforeach
        </select>
        <select name="release" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Produkte</option>
            @foreach($releases as $release)
                <option value="{{ $release->id }}" {{ request('release') == $release->id ? 'selected' : '' }}>{{ $release->title }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-50 dark:hover:bg-gray-700/500">Filtern</button>
        @if(request('search') || request('status') || request('genre') || request('release'))
            <a href="{{ route('admin.tracks.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:hover:text-gray-200 dark:text-gray-100">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.tracks.create') }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 whitespace-nowrap">+ Neuer Track</a>
</div>

<div x-data="{ selected: [], selectAll: false, bulkAction: '', bulkValue: '' }">

{{-- Bulk-Aktionsleiste --}}
<div x-show="selected.length > 0" x-transition class="mb-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-3 flex flex-wrap items-center gap-3">
    <span class="text-sm font-medium text-blue-700 dark:text-blue-300" x-text="selected.length + ' Track(s) ausgewählt'"></span>
    <form method="POST" action="{{ route('admin.tracks.bulkUpdate') }}" class="flex flex-wrap items-center gap-2" onsubmit="return confirm('Aktion auf ' + document.querySelector('[x-text]').innerText + ' anwenden?')">
        @csrf
        <template x-for="id in selected" :key="id">
            <input type="hidden" name="ids[]" :value="id">
        </template>
        <select name="action" x-model="bulkAction" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
            <option value="">-- Aktion --</option>
            <option value="status">Status ändern</option>
            <option value="genre">Genre setzen</option>
            <option value="delete">Löschen</option>
        </select>
        <input x-show="bulkAction === 'genre'" type="text" name="value" placeholder="Genre..." class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm w-40">
        <select x-show="bulkAction === 'status'" name="value" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
            <option value="draft">Draft</option>
            <option value="released">Released</option>
            <option value="archived">Archived</option>
        </select>
        <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Anwenden</button>
    </form>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-2 py-3 w-8">
                        <input type="checkbox" x-model="selectAll" @change="selected = selectAll ? [...document.querySelectorAll('.track-cb')].map(el => el.value) : []" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </th>
                    <th class="px-2 py-3 w-8"></th>
                    <x-admin.sortable-header column="title">Titel</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Band</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Produkt(e)</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Genre</th>
                    <x-admin.sortable-header column="isrc">ISRC</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dauer</th>
                    <x-admin.sortable-header column="bpm">BPM</x-admin.sortable-header>
                    <x-admin.sortable-header column="status">Status</x-admin.sortable-header>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($tracks as $track)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">
                        <td class="px-2 py-3">
                            <input type="checkbox" class="track-cb rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="{{ $track->id }}" x-model="selected">
                        </td>
                        <td class="px-2 py-3">
                            @if($track->audio_file_path)
                                <button type="button"
                                        @click="$dispatch('play-track', { title: '{{ addslashes($track->display_title) }}', artist: '{{ addslashes($track->organizations->where("type", "band")->pluck("primary_name")->join(", ")) }}', url: '{{ Storage::disk('public')->url($track->audio_file_path) }}' })"
                                        class="w-7 h-7 flex items-center justify-center rounded-full bg-blue-600 hover:bg-blue-700 text-white transition">
                                    <svg class="w-3.5 h-3.5 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                            <a href="{{ route('admin.tracks.show', $track) }}" class="hover:text-blue-600">{{ $track->title }}</a>
                            @if($track->version)
                                <p class="text-xs text-gray-400 font-normal">{{ $track->version }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ $track->organizations->where('type', 'band')->map->primary_name->join(', ') ?: '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ $track->releases->pluck('title')->join(', ') ?: '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $track->genre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $track->isrc_formatted ?? $track->isrc ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $track->formatted_duration ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $track->bpm ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @switch($track->status)
                                @case('draft')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Draft</span>
                                    @break
                                @case('released')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300">Released</span>
                                    @break
                                @case('archived')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300">Archived</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.tracks.edit', $track) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Keine Tracks gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 flex items-center justify-between">
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $tracks->total() }} {{ Str::plural('Track', $tracks->total()) }} gesamt</p>
    {{ $tracks->links() }}
</div>
</div>{{-- close x-data --}}
@endsection
