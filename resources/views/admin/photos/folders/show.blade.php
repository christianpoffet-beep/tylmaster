@extends('admin.layouts.app')

@section('title', $folder->name)

@section('content')
<div class="max-w-6xl">
    {{-- Breadcrumbs --}}
    <div class="mb-4 flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('admin.photos.index') }}" class="hover:text-gray-700 dark:text-gray-300">Fotos</a>
        @foreach($folder->breadcrumbs as $crumb)
            <span>/</span>
            @if($crumb->id === $folder->id)
                <span class="text-gray-900 font-medium">{{ $crumb->name }}</span>
            @else
                <a href="{{ route('admin.photos.folders.show', $crumb) }}" class="hover:text-gray-700 dark:text-gray-300">{{ $crumb->name }}</a>
            @endif
        @endforeach
    </div>

    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $folder->name }}</h2>
                @if($folder->description)
                    <p class="text-sm text-gray-500 mt-1">{{ $folder->description }}</p>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.photos.folders.create', ['parent_id' => $folder->id]) }}" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded-lg hover:bg-gray-200">+ Unterordner</a>
                <a href="{{ route('admin.photos.folders.edit', $folder) }}" class="px-3 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-50 dark:hover:bg-gray-700/500">Bearbeiten</a>
                <form method="POST" action="{{ route('admin.photos.folders.destroy', $folder) }}" onsubmit="return confirm('Ordner und alle Fotos wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-2 bg-red-600 dark:bg-red-700 text-white text-sm rounded-lg hover:bg-red-700 dark:hover:bg-red-600">Löschen</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Share Link --}}
    <x-admin.collapsible-card title="Share-Link" class="mb-6">
        @if($folder->share_token)
            <div class="flex items-center gap-3">
                <input type="text" value="{{ $folder->share_url }}" readonly class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm bg-gray-50 text-gray-600 dark:text-gray-300" id="share-url">
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('share-url').value).then(() => this.textContent = 'Kopiert!').catch(() => {})" class="px-3 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">Kopieren</button>
                <form method="POST" action="{{ route('admin.photos.folders.revoke', $folder) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-2 bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 text-sm rounded-lg hover:bg-red-200">Widerrufen</button>
                </form>
            </div>
        @else
            <form method="POST" action="{{ route('admin.photos.folders.share', $folder) }}" class="flex items-center gap-3">
                @csrf
                <p class="text-sm text-gray-500 dark:text-gray-400">Kein Share-Link aktiv.</p>
                <button type="submit" class="px-3 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">Link erstellen</button>
            </form>
        @endif
    </x-admin.collapsible-card>

    {{-- Linked entities --}}
    @if($folder->contacts->count() || $folder->organizations->count() || $folder->projects->count())
    <x-admin.collapsible-card title="Verknüpfungen" class="mb-6">
        <div class="flex flex-wrap gap-2">
            @foreach($folder->contacts as $contact)
                <a href="{{ route('admin.contacts.show', $contact) }}" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 hover:bg-purple-200">{{ $contact->full_name }}</a>
            @endforeach
            @foreach($folder->organizations as $org)
                <a href="{{ route('admin.organizations.show', $org) }}" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-700 hover:bg-teal-200">{{ $org->primary_name }}</a>
            @endforeach
            @foreach($folder->projects as $project)
                <a href="{{ route('admin.projects.show', $project) }}" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-200">{{ $project->name }}</a>
            @endforeach
        </div>
    </x-admin.collapsible-card>
    @endif

    {{-- Subfolders --}}
    @if($folder->children->count())
    <x-admin.collapsible-card title="Unterordner" :count="$folder->children->count()" class="mb-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($folder->children as $child)
                <a href="{{ route('admin.photos.folders.show', $child) }}" class="flex flex-col items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 text-center">
                    <svg class="w-8 h-8 text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate w-full">{{ $child->name }}</span>
                    <span class="text-xs text-gray-400">{{ $child->photos_count }} Foto{{ $child->photos_count !== 1 ? 's' : '' }}</span>
                </a>
            @endforeach
        </div>
    </x-admin.collapsible-card>
    @endif

    {{-- Upload --}}
    <x-admin.collapsible-card title="Fotos hochladen" class="mb-6">
        <form method="POST" action="{{ route('admin.photos.upload', $folder) }}" enctype="multipart/form-data">
            @csrf
            <div class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
                <div class="flex-1">
                    <input type="file" name="photos[]" multiple accept="image/*"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                    <p class="text-xs text-gray-400 mt-1">Mehrere Bilder gleichzeitig auswählen möglich. Max. 50 MB pro Bild.</p>
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 whitespace-nowrap">Hochladen</button>
            </div>
            @error('photos') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
            @error('photos.*') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
        </form>
    </x-admin.collapsible-card>

    {{-- Photos Grid --}}
    <x-admin.collapsible-card title="Fotos" :count="$folder->photos->count()">
        @if($folder->photos->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            @foreach($folder->photos as $photo)
                <div class="group relative bg-gray-50 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.photos.show', $photo) }}">
                        <div class="aspect-square flex items-center justify-center">
                            <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $photo->display_title }}" class="w-full h-full object-cover">
                        </div>
                    </a>
                    <div class="p-2 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-700 truncate" title="{{ $photo->display_title }}">{{ $photo->display_title }}</p>
                        @if($photo->photographer)
                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $photo->photographer }}</p>
                        @endif
                    </div>
                    <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                        <a href="{{ asset('storage/' . $photo->file_path) }}" download class="inline-flex items-center justify-center w-7 h-7 bg-white rounded-full shadow text-gray-500 hover:text-blue-600" title="Download">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                        <form method="POST" action="{{ route('admin.photos.destroy', $photo) }}" onsubmit="return confirm('Foto löschen?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center w-7 h-7 bg-white rounded-full shadow text-gray-500 hover:text-red-600" title="Löschen">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        @else
            <p class="text-sm text-gray-400">Noch keine Fotos in diesem Ordner.</p>
        @endif
    </x-admin.collapsible-card>
</div>
@endsection
