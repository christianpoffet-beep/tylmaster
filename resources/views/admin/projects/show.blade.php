@extends('admin.layouts.app')

@section('title', $project->name)

@section('content')
<div class="max-w-4xl">
    <!-- Project Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $project->name }}</h2>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @php
                        $typeLabels = $projectTypes->pluck('name', 'slug')->toArray();
                        $typeColors = $projectTypes->pluck('color', 'slug')->toArray();
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$project->type] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">{{ $typeLabels[$project->type] ?? $project->type }}</span>
                    @switch($project->status)
                        @case('planned')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">Geplant</span>
                            @break
                        @case('in_progress')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300">In Arbeit</span>
                            @break
                        @case('completed')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300">Abgeschlossen</span>
                            @break
                        @case('paused')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Pausiert</span>
                            @break
                    @endswitch
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.projects.edit', $project) }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">Bearbeiten</a>
                <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" onsubmit="return confirm('Projekt wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white text-sm rounded-lg hover:bg-red-700 dark:hover:bg-red-600">Löschen</button>
                </form>
            </div>
        </div>

        @if($project->description)
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-500 mb-1">Beschreibung</h3>
                <p class="text-sm text-gray-900 whitespace-pre-line">{{ $project->description }}</p>
            </div>
        @endif

        @if($project->genres->count())
        <div class="mb-4 flex flex-wrap gap-1.5">
            @foreach($project->genres as $genre)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">{{ $genre->name }}</span>
            @endforeach
        </div>
        @endif

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Deadline</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $project->deadline ? $project->deadline->format('d.m.Y') : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Erstellt am</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $project->created_at->format('d.m.Y') }}</dd>
            </div>
        </dl>
    </div>

    <!-- Contacts -->
    @if($project->contacts->count())
        <x-admin.collapsible-card title="Kontakte" :count="$project->contacts->count()" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($project->contacts as $contact)
                    <a href="{{ route('admin.contacts.show', $contact) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200">{{ $contact->full_name }}</a>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    <!-- Organizations -->
    @if($project->organizations->count())
        @php
            $orgTypeLabels = ['band' => 'Band', 'label' => 'Label', 'publishing' => 'Publishing', 'venue' => 'Location/Venue', 'event_festival' => 'Veranstalter/Event/Festival', 'media' => 'Media', 'oma' => 'OMA-Kontakt'];
            $orgTypeColors = ['band' => 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300', 'label' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300', 'publishing' => 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300', 'venue' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300', 'event_festival' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300', 'media' => 'bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300', 'oma' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'];
        @endphp
        <x-admin.collapsible-card title="Organisationen" :count="$project->organizations->count()" class="mt-6">
            <div class="space-y-1">
                @foreach($project->organizations as $org)
                    <div class="flex items-center gap-2 py-1">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $orgTypeColors[$org->type] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">{{ $orgTypeLabels[$org->type] ?? $org->type }}</span>
                        <a href="{{ route('admin.organizations.show', $org) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $org->primary_name }}</a>
                    </div>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    <!-- Contracts -->
    @if($project->contracts && $project->contracts->count())
        <x-admin.collapsible-card title="Verträge" :count="$project->contracts->count()" class="mt-6">
            <div class="space-y-2">
                @foreach($project->contracts as $contract)
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                        <a href="{{ route('admin.contracts.show', $contract) }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">{{ $contract->title }}</a>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $contract->status ?? '' }}</span>
                    </div>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    <!-- Tracks (nur Release + Administration) -->
    @if($project->type !== 'event' && $project->tracks && $project->tracks->count())
        <x-admin.collapsible-card title="Tracks" :count="$project->tracks->count()" class="mt-6">
            <div class="space-y-2">
                @foreach($project->tracks as $track)
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                        <a href="{{ route('admin.tracks.show', $track) }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">{{ $track->title }}</a>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $track->formatted_duration ?? '' }}</span>
                    </div>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    <!-- Logo & Artwork (nur Release + Administration) -->
    @if($project->type !== 'event')
    <x-admin.collapsible-card title="Logo & Artwork" :count="$project->artworks->count()" class="mt-6">
        <x-slot:actions>
            <a href="{{ route('admin.artworks.create') }}" class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">+ Neues Artwork</a>
        </x-slot:actions>

        @if($project->artworks->count())
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($project->artworks as $artwork)
                    <a href="{{ route('admin.artworks.show', $artwork) }}" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        @if($artwork->artwork_path && in_array($artwork->artwork_mime_type, ['image/jpeg', 'image/jpg']))
                            <div class="w-12 h-12 rounded overflow-hidden bg-gray-200 flex-shrink-0">
                                <img src="{{ $artwork->artwork_url }}" alt="{{ $artwork->title }}" class="w-full h-full object-cover">
                            </div>
                        @else
                            <div class="w-12 h-12 rounded bg-gray-200 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $artwork->title }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $artwork->yoc ? $artwork->yoc : '' }}
                                {{ $artwork->logos->count() ? ($artwork->yoc ? '· ' : '') . $artwork->logos->count() . ' Logo' . ($artwork->logos->count() > 1 ? 's' : '') : '' }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Keine Artworks vorhanden.</p>
        @endif
    </x-admin.collapsible-card>
    @endif

    <!-- Tasks -->
    <x-admin.collapsible-card title="Aufgaben" :count="$project->tasks->count()" class="mt-6">
        @if($project->tasks->count())
            <div class="space-y-2 mb-6">
                @foreach($project->tasks as $task)
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <form method="POST" action="{{ route('admin.projects.tasks.toggle', [$project, $task]) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="flex items-center justify-center w-5 h-5 rounded border {{ $task->is_completed ? 'bg-blue-600 border-blue-600' : 'border-gray-300 hover:border-blue-500' }}">
                                    @if($task->is_completed)
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </button>
                            </form>
                            <a href="{{ route('admin.tasks.show', $task) }}" class="text-sm {{ $task->is_completed ? 'line-through text-gray-400' : 'text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400' }}">{{ $task->title }}</a>
                        </div>
                        @if($task->due_date)
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $task->due_date->format('d.m.Y') }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 mb-4">Keine Aufgaben vorhanden.</p>
        @endif

        <!-- Add Task Form -->
        <form method="POST" action="{{ route('admin.projects.tasks.store', $project) }}" class="flex gap-2">
            @csrf
            <input type="text" name="title" placeholder="Neue Aufgabe hinzufügen..." required class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <input type="date" name="due_date" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-50 dark:hover:bg-gray-700/500 whitespace-nowrap">Hinzufügen</button>
        </form>
        @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </x-admin.collapsible-card>

    <div class="mt-4">
        <a href="{{ route('admin.projects.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-300">&larr; Zurück zur Übersicht</a>
    </div>
</div>
@endsection
