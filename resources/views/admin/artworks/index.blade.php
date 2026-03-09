@extends('admin.layouts.app')

@section('title', 'Logo & Artwork')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.artworks.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Titel suchen..." class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Suchen</button>
        @if(request('search'))
            <a href="{{ route('admin.artworks.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.artworks.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 whitespace-nowrap">+ Neues Artwork</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($artworks as $artwork)
        <a href="{{ route('admin.artworks.show', $artwork) }}" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            <div class="aspect-square bg-gray-100 flex items-center justify-center">
                @if($artwork->artwork_path && in_array($artwork->artwork_mime_type, ['image/jpeg', 'image/jpg']))
                    <img src="{{ $artwork->artwork_url }}" alt="{{ $artwork->title }}" class="w-full h-full object-cover">
                @else
                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                @endif
            </div>
            <div class="p-3">
                <h3 class="text-sm font-medium text-gray-900 truncate">{{ $artwork->title }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $artwork->yoc ?? '' }}
                    {{ $artwork->logos_count ? ($artwork->yoc ? '· ' : '') . $artwork->logos_count . ' Logo' . ($artwork->logos_count > 1 ? 's' : '') : '' }}
                </p>
            </div>
        </a>
    @empty
        <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
            <p class="text-sm text-gray-500">Keine Artworks vorhanden.</p>
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $artworks->links() }}</div>
@endsection
