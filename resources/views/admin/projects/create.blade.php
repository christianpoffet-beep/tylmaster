@extends('admin.layouts.app')

@section('title', 'Neues Projekt')

@section('content')
<div class="max-w-3xl" x-data="{ type: '{{ old('type', '') }}' }">
    <form method="POST" action="{{ route('admin.projects.store') }}">
        @csrf

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Typ *</label>
                <select name="type" id="type" x-model="type" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— Typ wählen —</option>
                    @foreach($projectTypes as $pt)
                        <option value="{{ $pt->slug }}" {{ old('type') === $pt->slug ? 'selected' : '' }}>{{ $pt->name }}</option>
                    @endforeach
                </select>
                @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Beschreibung</label>
                <textarea name="description" id="description" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select name="status" id="status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(['planned' => 'Geplant', 'in_progress' => 'In Arbeit', 'completed' => 'Abgeschlossen', 'paused' => 'Pausiert'] as $value => $label)
                            <option value="{{ $value }}" {{ old('status', 'planned') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="deadline" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deadline</label>
                    <input type="date" name="deadline" id="deadline" value="{{ old('deadline') }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('deadline') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            @include('admin.partials.contact-search', ['selected' => collect(), 'inputName' => 'contacts[]', 'contactSearchLabel' => 'Kontakte'])

            {{-- Genres --}}
            @if($genres->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Genres</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($genres as $genre)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="genre_ids[]" value="{{ $genre->id }}" {{ in_array($genre->id, old('genre_ids', [])) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">{{ $genre->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Organisationen --}}
            @include('admin.partials.organization-search', ['selected' => collect()])

            {{-- Verträge --}}
            @if($contracts->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Verträge</label>
                <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2">
                    @foreach($contracts as $contract)
                        <label class="flex items-center">
                            <input type="checkbox" name="contract_ids[]" value="{{ $contract->id }}" {{ in_array($contract->id, old('contract_ids', [])) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $contract->title }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Tracks (nur Release + Administration) --}}
            <div x-show="type === 'release' || type === 'administration'" x-cloak>
                @if($tracks->count())
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tracks (Musik)</label>
                    <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2">
                        @foreach($tracks as $track)
                            <label class="flex items-center">
                                <input type="checkbox" name="track_ids[]" value="{{ $track->id }}" {{ in_array($track->id, old('track_ids', [])) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $track->title }}{{ $track->isrc ? ' (' . $track->isrc . ')' : '' }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Artworks (nur Release + Administration) --}}
            <div x-show="type === 'release' || type === 'administration'" x-cloak>
                @if($artworks->count())
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Logo & Artwork</label>
                    <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2">
                        @foreach($artworks as $artwork)
                            <label class="flex items-center">
                                <input type="checkbox" name="artwork_ids[]" value="{{ $artwork->id }}" {{ in_array($artwork->id, old('artwork_ids', [])) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $artwork->title }}{{ $artwork->yoc ? ' (' . $artwork->yoc . ')' : '' }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Projekt erstellen</button>
            <a href="{{ route('admin.projects.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
