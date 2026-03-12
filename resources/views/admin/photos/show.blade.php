@extends('admin.layouts.app')

@section('title', $photo->display_title)

@section('content')
<div class="max-w-4xl">
    <div class="mb-4">
        <a href="{{ route('admin.photos.folders.show', $photo->folder) }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-300">&larr; Zurück zum Ordner</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Photo Preview --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-100 dark:bg-gray-700 flex items-center justify-center p-4">
                <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $photo->display_title }}" class="max-w-full max-h-[500px] object-contain rounded">
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $photo->original_name }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ strtoupper(pathinfo($photo->original_name, PATHINFO_EXTENSION)) }}
                    · {{ number_format(($photo->file_size ?? 0) / 1024 / 1024, 1) }} MB
                </p>
            </div>
        </div>

        {{-- Metadata Form --}}
        <div>
            <form method="POST" action="{{ route('admin.photos.update', $photo) }}">
                @csrf
                @method('PUT')

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Credits & Metadaten</h3>

                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titel</label>
                        <input type="text" name="title" id="title" value="{{ old('title', $photo->title) }}" placeholder="{{ $photo->original_name }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="photographer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fotograf:in</label>
                        <input type="text" name="photographer" id="photographer" value="{{ old('photographer', $photo->photographer) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="graphic_artist" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Grafic Artist</label>
                        <input type="text" name="graphic_artist" id="graphic_artist" value="{{ old('graphic_artist', $photo->graphic_artist) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ort</label>
                            <input type="text" name="location" id="location" value="{{ old('location', $photo->location) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="photo_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Datum</label>
                            <input type="date" name="photo_date" id="photo_date" value="{{ old('photo_date', $photo->photo_date?->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label for="story" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Story</label>
                        <textarea name="story" id="story" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('story', $photo->story) }}</textarea>
                    </div>

                    <div>
                        <label for="info" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Info</label>
                        <textarea name="info" id="info" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('info', $photo->info) }}</textarea>
                    </div>

                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Speichern</button>
                </div>
            </form>

            {{-- Public URL --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mt-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Öffentlicher Link</h3>
                <div class="flex items-center gap-2">
                    <input type="text" value="{{ $photo->public_url }}" readonly class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm bg-gray-50 text-gray-600 dark:text-gray-300" id="public-url">
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('public-url').value).then(() => this.textContent = 'Kopiert!').catch(() => {})" class="px-3 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-50 dark:hover:bg-gray-700/500">Kopieren</button>
                </div>
            </div>

            {{-- Delete --}}
            <div class="mt-4">
                <form method="POST" action="{{ route('admin.photos.destroy', $photo) }}" onsubmit="return confirm('Foto wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white text-sm rounded-lg hover:bg-red-700 dark:hover:bg-red-600">Foto löschen</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
