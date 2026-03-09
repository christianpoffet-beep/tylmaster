@extends('admin.layouts.app')

@section('title', 'Neue Aufgabe')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.tasks.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Titel *</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Beschreibung</label>
                <textarea name="description" id="description" rows="3" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Fällig am</label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priorität</label>
                    <select name="priority" id="priority" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Keine</option>
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Tief</option>
                        <option value="medium" {{ old('priority') === 'medium' ? 'selected' : '' }}>Mittel</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>Hoch</option>
                    </select>
                </div>
                <div>
                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">Hauptprojekt</label>
                    <select name="project_id" id="project_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Keines</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Document Upload --}}
            <hr class="border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">Dokumente</h3>

            <div x-data="{ counter: 1 }">
                <div id="doc-rows">
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
                    document.getElementById('doc-rows').appendChild(row);
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
                                {{ in_array($contact->id, old('contact_ids', [])) ? 'checked' : '' }}
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
                                {{ in_array($contract->id, old('contract_ids', [])) ? 'checked' : '' }}
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
                                {{ in_array($track->id, old('track_ids', [])) ? 'checked' : '' }}
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
                                {{ in_array($project->id, old('linked_project_ids', [])) ? 'checked' : '' }}
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
                                {{ in_array($submission->id, old('submission_ids', [])) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700">{{ $submission->artist_name }} — {{ $submission->project_name ?? $submission->track_title }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Aufgabe erstellen</button>
            <a href="{{ route('admin.tasks.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
