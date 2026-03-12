@extends('admin.layouts.app')

@section('title', 'Track bearbeiten')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.tracks.update', $track) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titel *</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $track->title) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="isrc" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ISRC</label>
                    <input type="text" name="isrc" id="isrc" value="{{ old('isrc', $track->isrc) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('isrc') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="genre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Genre</label>
                    <input type="text" name="genre" id="genre" value="{{ old('genre', $track->genre) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('genre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="duration_seconds" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dauer (Sekunden)</label>
                    <input type="number" name="duration_seconds" id="duration_seconds" value="{{ old('duration_seconds', $track->duration_seconds) }}" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('duration_seconds') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select name="status" id="status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(['draft' => 'Draft', 'released' => 'Released', 'archived' => 'Archived'] as $value => $label)
                            <option value="{{ $value }}" {{ old('status', $track->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="release_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Release</label>
                    <select name="release_id" id="release_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Kein Release --</option>
                        @foreach($releases as $release)
                            <option value="{{ $release->id }}" {{ old('release_id', $track->release_id) == $release->id ? 'selected' : '' }}>{{ $release->title }}</option>
                        @endforeach
                    </select>
                    @error('release_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="audio_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Audio-Datei</label>
                @if($track->audio_file_path)
                    <p class="text-sm text-gray-500 mb-2">Aktuelle Datei: {{ basename($track->audio_file_path) }}</p>
                @endif
                <input type="file" name="audio_file" id="audio_file" accept="audio/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                @error('audio_file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Artists</label>
                <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2">
                    @php $selectedArtists = old('artists', $track->contacts->where('pivot.role', 'artist')->pluck('id')->toArray()); @endphp
                    @foreach($contacts as $contact)
                        <label class="flex items-center">
                            <input type="checkbox" name="artists[]" value="{{ $contact->id }}" {{ in_array($contact->id, $selectedArtists) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $contact->full_name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('artists') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Track aktualisieren</button>
            <a href="{{ route('admin.tracks.show', $track) }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>

    <!-- Delete -->
    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <form method="POST" action="{{ route('admin.tracks.destroy', $track) }}" onsubmit="return confirm('Track wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Track löschen</button>
        </form>
    </div>
</div>
@endsection
