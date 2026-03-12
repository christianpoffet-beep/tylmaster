@extends('admin.layouts.app')

@section('title', $release->title)

@section('content')
<div class="max-w-3xl">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex justify-between items-start mb-6">
            <div class="flex gap-4">
                @if($release->cover_image_path)
                    <img src="{{ Storage::url($release->cover_image_path) }}" alt="Cover" class="w-28 h-28 object-cover rounded-lg shadow-sm">
                @endif
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $release->title }}</h2>
                    @if($release->label)
                        <p class="text-sm text-gray-500 mt-1">{{ $release->label }}</p>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.releases.edit', $release) }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">Bearbeiten</a>
                <form method="POST" action="{{ route('admin.releases.destroy', $release) }}" onsubmit="return confirm('Release wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white text-sm rounded-lg hover:bg-red-700 dark:hover:bg-red-600">Löschen</button>
                </form>
            </div>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">UPC</dt>
                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $release->upc ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Release-Datum</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $release->release_date ? $release->release_date->format('d.m.Y') : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Label</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $release->label ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    <!-- Artists -->
    @php $artists = $release->contacts->where('pivot.role', 'artist'); @endphp
    @if($artists->count())
        <x-admin.collapsible-card title="Artists" :count="$artists->count()" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($artists as $artist)
                    <a href="{{ route('admin.contacts.show', $artist) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200">{{ $artist->full_name }}</a>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    <!-- Tracks -->
    <x-admin.collapsible-card title="Tracks" :count="$release->tracks->count()" class="mt-6">
        @if($release->tracks->count())
            <div class="space-y-2">
                @foreach($release->tracks as $index => $track)
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-400 dark:text-gray-500 font-mono w-6 text-right">{{ $index + 1 }}</span>
                            <a href="{{ route('admin.tracks.show', $track) }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">{{ $track->title }}</a>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $track->formatted_duration ?? '-' }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Keine Tracks zugewiesen.</p>
        @endif
    </x-admin.collapsible-card>

    <div class="mt-4">
        <a href="{{ route('admin.releases.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-300">&larr; Zurück zur Übersicht</a>
    </div>
</div>
@endsection
