@extends('admin.layouts.app')

@section('title', 'Aufgabe bearbeiten')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.tasks.update', $task) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Titel *</label>
                <input type="text" name="title" id="title" value="{{ old('title', $task->title) }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Beschreibung</label>
                <textarea name="description" id="description" rows="3" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $task->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Fällig am</label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priorität</label>
                    <select name="priority" id="priority" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Keine</option>
                        <option value="low" {{ old('priority', $task->priority) === 'low' ? 'selected' : '' }}>Tief</option>
                        <option value="medium" {{ old('priority', $task->priority) === 'medium' ? 'selected' : '' }}>Mittel</option>
                        <option value="high" {{ old('priority', $task->priority) === 'high' ? 'selected' : '' }}>Hoch</option>
                    </select>
                </div>
                <div>
                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">Hauptprojekt</label>
                    <select name="project_id" id="project_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Keines</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id', $task->project_id) == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Existing Documents --}}
            @if($task->uploadedDocuments->count())
            <hr class="border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">Vorhandene Dokumente</h3>
            <div class="space-y-2">
                @foreach($task->uploadedDocuments as $doc)
                    @php
                        $docPreviewable = $doc->mime_type && (
                            $doc->mime_type === 'application/pdf' ||
                            str_starts_with($doc->mime_type, 'image/') ||
                            str_starts_with($doc->mime_type, 'audio/') ||
                            str_starts_with($doc->mime_type, 'video/')
                        );
                    @endphp
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded bg-gray-200 text-[9px] font-bold text-gray-500 flex-shrink-0">{{ $doc->file_extension }}</span>
                            <div class="min-w-0">
                                @if($docPreviewable)
                                    <button type="button" class="text-sm font-medium text-blue-600 hover:text-blue-800 truncate block text-left"
                                        @click="$dispatch('open-doc-viewer', {
                                            url: '{{ route('admin.documents.preview', $doc) }}',
                                            title: '{{ e($doc->title) }}',
                                            mime: '{{ $doc->mime_type }}',
                                            downloadUrl: '{{ route('admin.documents.download', $doc) }}'
                                        })">{{ $doc->title }}</button>
                                @else
                                    <a href="{{ route('admin.documents.download', $doc) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 truncate block">{{ $doc->title }}</a>
                                @endif
                                <div class="flex items-center gap-3 text-xs text-gray-500 mt-0.5">
                                    <span>{{ $doc->created_at->format('d.m.Y H:i') }}</span>
                                    <span>@if($doc->file_size >= 1048576){{ number_format($doc->file_size / 1048576, 1) }} MB @else {{ number_format($doc->file_size / 1024, 0) }} KB @endif</span>
                                    @if($doc->notes)
                                        <span class="text-gray-600">{{ $doc->notes }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 ml-2">
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
                            <button type="button" class="text-red-400 hover:text-red-600" title="Löschen"
                                onclick="if(confirm('Dokument wirklich löschen?')){let f=document.createElement('form');f.method='POST';f.action='{{ route('admin.tasks.documents.destroy', [$task, $doc]) }}';f.innerHTML='<input type=hidden name=_token value={{ csrf_token() }}><input type=hidden name=_method value=DELETE>';document.body.appendChild(f);f.submit();}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif

            {{-- New Document Upload --}}
            <hr class="border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">Neue Dokumente hochladen</h3>

            <div x-data="{ counter: 1 }">
                <div id="doc-rows-edit">
                    <div class="flex gap-2 items-start mb-3">
                        <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <input type="file" name="doc_files[]" class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                            <input type="text" name="doc_notes[]" placeholder="Notiz zum Dokument..." class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
                <button type="button" @click="counter++; $nextTick(() => {
                    let row = document.createElement('div');
                    row.className = 'flex gap-2 items-start mb-3';
                    row.innerHTML = '<div class=\'flex-1 grid grid-cols-1 sm:grid-cols-2 gap-2\'><input type=\'file\' name=\'doc_files[]\' class=\'w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200\'><input type=\'text\' name=\'doc_notes[]\' placeholder=\'Notiz zum Dokument...\' class=\'w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500\'></div><button type=\'button\' onclick=\'this.parentElement.remove()\' class=\'mt-1 text-red-400 hover:text-red-600\'><svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M6 18L18 6M6 6l12 12\'/></svg></button>';
                    document.getElementById('doc-rows-edit').appendChild(row);
                })" class="text-sm text-blue-600 hover:text-blue-800">+ Dokument hinzufügen</button>
            </div>

            <hr class="border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">Verknüpfungen</h3>

            @if($contacts->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kontakte</label>
                <div class="flex flex-wrap gap-2 max-h-32 overflow-y-auto">
                    @foreach($contacts as $contact)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="contact_ids[]" value="{{ $contact->id }}"
                                {{ $task->contacts->contains($contact->id) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700">{{ $contact->full_name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            @if($contracts->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Verträge</label>
                <div class="flex flex-wrap gap-2 max-h-32 overflow-y-auto">
                    @foreach($contracts as $contract)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="contract_ids[]" value="{{ $contract->id }}"
                                {{ $task->contracts->contains($contract->id) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700">{{ $contract->title }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            @if($tracks->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tracks</label>
                <div class="flex flex-wrap gap-2 max-h-32 overflow-y-auto">
                    @foreach($tracks as $track)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="track_ids[]" value="{{ $track->id }}"
                                {{ $task->tracks->contains($track->id) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700">{{ $track->title }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            @if($projects->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Verknüpfte Projekte</label>
                <div class="flex flex-wrap gap-2 max-h-32 overflow-y-auto">
                    @foreach($projects as $project)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="linked_project_ids[]" value="{{ $project->id }}"
                                {{ $task->projects->contains($project->id) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700">{{ $project->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            @if($submissions->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Submissions</label>
                <div class="flex flex-wrap gap-2 max-h-32 overflow-y-auto">
                    @foreach($submissions as $submission)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="submission_ids[]" value="{{ $submission->id }}"
                                {{ $task->submissions->contains($submission->id) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700">{{ $submission->artist_name }} — {{ $submission->project_name ?? $submission->track_title }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Aufgabe aktualisieren</button>
            <a href="{{ route('admin.tasks.show', $task) }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Abbrechen</a>
        </div>
    </form>

    <div class="mt-8 pt-6 border-t border-gray-200">
        <form method="POST" action="{{ route('admin.tasks.destroy', $task) }}" onsubmit="return confirm('Aufgabe wirklich löschen?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Aufgabe löschen</button>
        </form>
    </div>
</div>
@endsection
