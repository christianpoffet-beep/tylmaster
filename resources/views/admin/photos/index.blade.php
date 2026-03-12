@extends('admin.layouts.app')

@section('title', 'Fotos / Bilder')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.photos.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ordner suchen..." class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-50 dark:hover:bg-gray-700/500">Suchen</button>
        @if(request('search'))
            <a href="{{ route('admin.photos.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:hover:text-gray-200 dark:text-gray-100">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.photos.folders.create') }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 whitespace-nowrap">+ Neuer Ordner</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @forelse($folders as $folder)
        <a href="{{ route('admin.photos.folders.show', $folder) }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="aspect-[4/3] bg-gray-50 flex items-center justify-center">
                @php $firstPhoto = $folder->photos->first(); @endphp
                @if($firstPhoto)
                    <img src="{{ asset('storage/' . $firstPhoto->file_path) }}" alt="{{ $folder->name }}" class="w-full h-full object-cover">
                @else
                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                @endif
            </div>
            <div class="p-3">
                <h3 class="text-sm font-medium text-gray-900 truncate">{{ $folder->name }}</h3>
                @if($folder->description)
                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $folder->description }}</p>
                @endif
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $folder->photos_count }} Foto{{ $folder->photos_count !== 1 ? 's' : '' }}
                    @if($folder->children_count)
                        · {{ $folder->children_count }} Unterordner
                    @endif
                </p>
            </div>
        </a>
    @empty
        <div class="col-span-full bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Keine Ordner vorhanden.</p>
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $folders->links() }}</div>
@endsection
