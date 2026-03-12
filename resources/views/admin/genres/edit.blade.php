@extends('admin.layouts.app')

@section('title', 'Genre bearbeiten')

@section('content')
<div class="max-w-lg">
    <form method="POST" action="{{ route('admin.genres.update', $genre) }}">
        @csrf
        @method('PUT')
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $genre->name) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Genre aktualisieren</button>
            <a href="{{ route('admin.genres.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>

    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <form method="POST" action="{{ route('admin.genres.destroy', $genre) }}" onsubmit="return confirm('Genre wirklich löschen?')">
            @csrf
            @method('DELETE')
            <h3 class="text-sm font-semibold text-red-600 mb-2">Gefahrenzone</h3>
            <p class="text-sm text-gray-500 mb-3">Das Genre wird unwiderruflich gelöscht und von allen verknüpften Einträgen entfernt.</p>
            <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Genre löschen</button>
        </form>
    </div>
</div>
@endsection
