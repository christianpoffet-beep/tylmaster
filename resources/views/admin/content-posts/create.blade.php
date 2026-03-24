@extends('admin.layouts.app')

@section('title', 'Neuer Beitrag')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Neuer Beitrag</h1>
    <a href="{{ route('admin.content-posts.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Zurück</a>
</div>

<form method="POST" action="{{ route('admin.content-posts.store') }}" enctype="multipart/form-data"
      class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-5">
    @csrf

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Plattform *</label>
            <select name="platform" id="platform" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach(\App\Models\ContentPost::PLATFORMS as $val => $label)
                    <option value="{{ $val }}" {{ old('platform', 'instagram') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
            <select name="status" id="status" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach(\App\Models\ContentPost::STATUSES as $val => $label)
                    <option value="{{ $val }}" {{ old('status', 'draft') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="scheduled_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Geplant für</label>
            <input type="datetime-local" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at') }}"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
    </div>

    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titel</label>
        <input type="text" name="title" id="title" value="{{ old('title') }}" placeholder="Optionaler interner Titel"
               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="caption" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Caption *</label>
        <textarea name="caption" id="caption" rows="6" required
                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="Post-Text...">{{ old('caption') }}</textarea>
        @error('caption') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="hashtags" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hashtags</label>
        <textarea name="hashtags" id="hashtags" rows="2"
                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="#metal #underground #livemusic">{{ old('hashtags') }}</textarea>
    </div>

    @include('admin.content-posts._image-picker')

    <div>
        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notizen</label>
        <textarea name="notes" id="notes" rows="2"
                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="Interne Notizen...">{{ old('notes') }}</textarea>
    </div>

    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('admin.content-posts.index') }}" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">Abbrechen</a>
        <button type="submit" class="px-6 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">Speichern</button>
    </div>
</form>
@endsection
