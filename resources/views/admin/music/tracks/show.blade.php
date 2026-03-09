@extends('admin.layouts.app')

@section('title', $track->title)

@section('content')
<div class="max-w-3xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $track->title }}</h2>
                <div class="mt-2">
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
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.tracks.edit', $track) }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Bearbeiten</a>
                <form method="POST" action="{{ route('admin.tracks.destroy', $track) }}" onsubmit="return confirm('Track wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">Löschen</button>
                </form>
            </div>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">ISRC</dt>
                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $track->isrc ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Genre</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $track->genre ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Dauer</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $track->formatted_duration ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Release</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    @if($track->release)
                        <a href="{{ route('admin.releases.show', $track->release) }}" class="text-blue-600 hover:text-blue-800">{{ $track->release->title }}</a>
                    @else
                        -
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    <!-- Artists -->
    @php $artists = $track->contacts->where('pivot.role', 'artist'); @endphp
    @if($artists->count())
        <x-admin.collapsible-card title="Artists" :count="$artists->count()" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($artists as $artist)
                    <a href="{{ route('admin.contacts.show', $artist) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700 hover:bg-gray-200">{{ $artist->full_name }}</a>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    <!-- Audio Player -->
    @if($track->audio_file_path)
        <x-admin.collapsible-card title="Audio" class="mt-6">
            <audio controls class="w-full">
                <source src="{{ Storage::url($track->audio_file_path) }}">
                Ihr Browser unterstützt das Audio-Element nicht.
            </audio>
        </x-admin.collapsible-card>
    @endif

    <div class="mt-4">
        <a href="{{ route('admin.tracks.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Zurück zur Übersicht</a>
    </div>
</div>
@endsection
