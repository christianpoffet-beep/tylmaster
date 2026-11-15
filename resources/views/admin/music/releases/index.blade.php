@extends('admin.layouts.app')

@section('title', 'Produkte')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Produkte</h1>
    <a href="{{ route('admin.releases.create') }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 whitespace-nowrap">+ Neues Produkt</a>
</div>

{{-- Filters: band, label and type each take several values (any of them), combined with each other (all of them) --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
    <form method="GET" action="{{ route('admin.releases.index') }}" x-data="{ dirty: false }" @filters-changed="dirty = true" class="flex flex-wrap gap-3 items-end">
        @foreach(['sort', 'dir'] as $keep)
            @if(request()->filled($keep))
                <input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">
            @endif
        @endforeach
        <div class="w-full sm:w-auto">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Suche</label>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Titel, UPC, Katalognr., Band, Label …" class="w-full sm:w-64 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        @include('admin.partials.filter-multiselect', ['name' => 'band', 'label' => 'Band', 'plural' => 'Bands', 'options' => $filterOptions['band'], 'selected' => $filters['band']])
        @include('admin.partials.filter-multiselect', ['name' => 'label', 'label' => 'Label', 'plural' => 'Labels', 'options' => $filterOptions['label'], 'selected' => $filters['label']])
        @include('admin.partials.filter-multiselect', ['name' => 'type', 'label' => 'Typ', 'plural' => 'Typen', 'options' => $filterOptions['type'], 'selected' => $filters['type']])
        <button type="submit" class="px-4 py-2 text-white text-sm rounded-lg transition"
                :class="dirty ? 'bg-blue-600 hover:bg-blue-700 ring-2 ring-blue-300 dark:ring-blue-500' : 'bg-gray-800 dark:bg-gray-600 hover:bg-gray-700 dark:hover:bg-gray-500'">
            <span x-text="dirty ? 'Filter anwenden' : 'Filtern'">Filtern</span>
        </button>
    </form>

    @if(count($activeFilters) || request()->filled('search'))
        <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            @foreach($activeFilters as $chip)
                <a href="{{ $chip['url'] }}" class="inline-flex items-center gap-1 pl-2.5 pr-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900" title="Filter entfernen">
                    {{ $chip['label'] }} <span class="text-sm leading-none">&times;</span>
                </a>
            @endforeach
            <a href="{{ route('admin.releases.index', request()->only(['sort', 'dir'])) }}" class="text-xs text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">Alle Filter zurücksetzen</a>
        </div>
    @endif
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <x-admin.sortable-header column="title">Titel</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Band</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Typ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Label</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">EAN / UPC</th>
                    <x-admin.sortable-header column="release_date">Datum</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tracks</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($releases as $release)
                    @php
                        $album = $release->playerQueue();
                        $artwork = $release->main_artwork;
                        $artworkHasImage = $artwork && ($artwork->images->isNotEmpty() || $artwork->logos->isNotEmpty() || $artwork->getRawOriginal('artwork_path'));
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                        @if(count($album['items'])) x-data="{ album: @js($album) }" :class="$store.player.isCurrent(album.id) && 'bg-blue-50/70 dark:bg-blue-900/20'" @endif>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                            <div class="flex items-center gap-3">
                                {{-- Main artwork; play button over it when there is audio --}}
                                <div class="relative w-12 h-12 flex-shrink-0 rounded-md overflow-hidden bg-gray-100 dark:bg-gray-700 shadow-sm">
                                    @if($artworkHasImage)
                                        <img src="{{ route('admin.artworks.thumbnail', $artwork) }}" alt="{{ $artwork->title }}" loading="lazy" class="w-full h-full object-cover">
                                    @elseif($release->cover_image_path)
                                        <img src="{{ Storage::disk('public')->url($release->cover_image_path) }}" alt="" loading="lazy" class="w-full h-full object-cover">
                                    @else
                                        <svg class="w-6 h-6 m-3 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                                    @endif
                                    @if(count($album['items']))
                                        <button type="button"
                                                @click="$store.player.isCurrent(album.id) ? $store.player.toggle() : $store.player.playQueue(album, 0)"
                                                :title="$store.player.isCurrent(album.id) && $store.player.playing ? 'Pause' : 'Ganzes Produkt abspielen ({{ count($album['items']) }} {{ count($album['items']) === 1 ? 'Track' : 'Tracks' }})'"
                                                class="absolute inset-0 flex items-center justify-center bg-black/10 hover:bg-black/40 text-white transition">
                                            <span class="w-7 h-7 rounded-full bg-blue-600/95 flex items-center justify-center shadow">
                                                <svg x-show="!($store.player.isCurrent(album.id) && $store.player.playing)" class="w-3.5 h-3.5 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                <svg x-show="$store.player.isCurrent(album.id) && $store.player.playing" x-cloak class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                                            </span>
                                        </button>
                                    @endif
                                </div>
                                <a href="{{ route('admin.releases.show', $release) }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ $release->title }}</a>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($release->main_artist_display)
                                <span class="text-gray-700 dark:text-gray-200">{{ $release->main_artist_display }}</span>
                            @elseif($release->track_band_names)
                                <span class="text-gray-400 dark:text-gray-500" title="Aus den Tracks">{{ $release->track_band_names }}</span>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $types = is_array($release->product_type) ? $release->product_type : ($release->product_type ? [$release->product_type] : []);
                            @endphp
                            @if(count($types))
                                <div class="flex flex-wrap gap-1">
                                    @foreach($types as $pt)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">{{ $pt }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $release->label_display ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $release->ean_upc ?: ($release->upc ?: '-') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $release->release_date ? $release->release_date->format('d.m.Y') : '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $release->tracks_count ?? $release->tracks->count() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.releases.edit', $release) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Keine Produkte gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 flex items-center justify-between">
    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $releases->total() }} Produkte gefunden</span>
    {{ $releases->links() }}
</div>
@endsection
