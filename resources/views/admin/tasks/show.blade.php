@extends('admin.layouts.app')

@section('title', 'Aufgabe: ' . $task->title)

@section('content')
<div class="max-w-4xl">

    {{-- Header --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('admin.tasks.toggle', $task) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-6 h-6 rounded border-2 flex items-center justify-center {{ $task->is_completed ? 'bg-blue-500 border-blue-500 text-white' : 'border-gray-300 hover:border-blue-400' }}">
                            @if($task->is_completed)
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            @endif
                        </button>
                    </form>
                    <h2 class="text-xl font-bold {{ $task->is_completed ? 'text-gray-400 line-through' : 'text-gray-900' }}">{{ $task->title }}</h2>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-2">
                    @if($task->is_completed)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Erledigt</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">Offen</span>
                    @endif

                    @if($task->priority === 'high')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Hoch</span>
                    @elseif($task->priority === 'medium')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">Mittel</span>
                    @elseif($task->priority === 'low')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Tief</span>
                    @endif

                    @if($task->isOverdue())
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Überfällig</span>
                    @endif
                </div>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.tasks.edit', $task) }}" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Bearbeiten</a>
                <form method="POST" action="{{ route('admin.tasks.destroy', $task) }}" onsubmit="return confirm('Aufgabe wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">Löschen</button>
                </form>
            </div>
        </div>

        {{-- Details --}}
        <dl class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <dt class="text-gray-500">Fällig am</dt>
                <dd class="mt-1 {{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-900' }}">{{ $task->due_date?->format('d.m.Y') ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Hauptprojekt</dt>
                <dd class="mt-1 text-gray-900">
                    @if($task->project)
                        <a href="{{ route('admin.projects.show', $task->project) }}" class="text-blue-600 hover:text-blue-800">{{ $task->project->name }}</a>
                    @else - @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Erstellt am</dt>
                <dd class="mt-1 text-gray-900">{{ $task->created_at->format('d.m.Y H:i') }}</dd>
            </div>
        </dl>

        @if($task->description)
            <div class="mt-4 pt-4 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">Beschreibung</h3>
                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $task->description }}</p>
            </div>
        @endif
    </div>

    {{-- Uploaded Documents --}}
    @if($task->uploadedDocuments->count())
    <x-admin.collapsible-card title="Dokumente" :count="$task->uploadedDocuments->count()" class="mb-6">
        <div class="space-y-3">
            @foreach($task->uploadedDocuments as $doc)
                @php
                    $docPreviewable = $doc->mime_type && (
                        $doc->mime_type === 'application/pdf' ||
                        str_starts_with($doc->mime_type, 'image/') ||
                        str_starts_with($doc->mime_type, 'audio/') ||
                        str_starts_with($doc->mime_type, 'video/')
                    );
                @endphp
                <div class="flex items-start justify-between py-2 px-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-200 text-[10px] font-bold text-gray-500 flex-shrink-0">{{ $doc->file_extension }}</span>
                        <div class="min-w-0">
                            @if($docPreviewable)
                                <button type="button" class="text-sm font-medium text-blue-600 hover:text-blue-800 text-left truncate block"
                                    @click="$dispatch('open-doc-viewer', {
                                        url: '{{ route('admin.documents.preview', $doc) }}',
                                        title: '{{ e($doc->title) }}',
                                        mime: '{{ $doc->mime_type }}',
                                        downloadUrl: '{{ route('admin.documents.download', $doc) }}'
                                    })">{{ $doc->title }}</button>
                            @else
                                <a href="{{ route('admin.documents.download', $doc) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 truncate block">{{ $doc->title }}</a>
                            @endif
                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 mt-0.5">
                                <span>{{ $doc->created_at->format('d.m.Y H:i') }}</span>
                                <span>@if($doc->file_size >= 1048576){{ number_format($doc->file_size / 1048576, 1) }} MB @else {{ number_format($doc->file_size / 1024, 0) }} KB @endif</span>
                            </div>
                            @if($doc->notes)
                                <p class="text-sm text-gray-600 mt-1">{{ $doc->notes }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 ml-3">
                        @if($docPreviewable)
                            <button type="button" title="Vorschau" class="text-gray-400 hover:text-blue-600"
                                @click="$dispatch('open-doc-viewer', {
                                    url: '{{ route('admin.documents.preview', $doc) }}',
                                    title: '{{ e($doc->title) }}',
                                    mime: '{{ $doc->mime_type }}',
                                    downloadUrl: '{{ route('admin.documents.download', $doc) }}'
                                })">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        @endif
                        <a href="{{ route('admin.documents.download', $doc) }}" title="Download" class="text-gray-400 hover:text-blue-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                        <form method="POST" action="{{ route('admin.tasks.documents.destroy', [$task, $doc]) }}" onsubmit="return confirm('Dokument wirklich löschen?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-600" title="Löschen">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </x-admin.collapsible-card>
    @endif

    {{-- Linked Records --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @if($task->contacts->count())
        <x-admin.collapsible-card title="Kontakte" :count="$task->contacts->count()">
            @foreach($task->contacts as $contact)
                <a href="{{ route('admin.contacts.show', $contact) }}" class="block text-sm text-blue-600 hover:text-blue-800 py-1">{{ $contact->full_name }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        @if($task->contracts->count())
        <x-admin.collapsible-card title="Verträge" :count="$task->contracts->count()">
            @foreach($task->contracts as $contract)
                <a href="{{ route('admin.contracts.show', $contract) }}" class="block text-sm text-blue-600 hover:text-blue-800 py-1">{{ $contract->title }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        @if($task->tracks->count())
        <x-admin.collapsible-card title="Tracks" :count="$task->tracks->count()">
            @foreach($task->tracks as $track)
                <a href="{{ route('admin.tracks.show', $track) }}" class="block text-sm text-blue-600 hover:text-blue-800 py-1">{{ $track->title }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        @if($task->releases->count())
        <x-admin.collapsible-card title="Releases" :count="$task->releases->count()">
            @foreach($task->releases as $release)
                <a href="{{ route('admin.releases.show', $release) }}" class="block text-sm text-blue-600 hover:text-blue-800 py-1">{{ $release->title }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        @if($task->projects->count())
        <x-admin.collapsible-card title="Projekte" :count="$task->projects->count()">
            @foreach($task->projects as $project)
                <a href="{{ route('admin.projects.show', $project) }}" class="block text-sm text-blue-600 hover:text-blue-800 py-1">{{ $project->name }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        @if($task->submissions->count())
        <x-admin.collapsible-card title="Submissions" :count="$task->submissions->count()">
            @foreach($task->submissions as $submission)
                <a href="{{ route('admin.submissions.show', $submission) }}" class="block text-sm text-blue-600 hover:text-blue-800 py-1">{{ $submission->artist_name }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif
    </div>

    <div class="mt-4">
        <a href="{{ route('admin.tasks.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Zurück zur Übersicht</a>
    </div>
</div>
@endsection
