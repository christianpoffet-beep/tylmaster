@extends('admin.layouts.app')

@section('title', 'Beitrag bearbeiten')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Beitrag bearbeiten</h1>
    <a href="{{ route('admin.content-posts.show', $contentPost) }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Zurück</a>
</div>

<form method="POST" action="{{ route('admin.content-posts.update', $contentPost) }}" enctype="multipart/form-data"
      class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-5">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Plattform *</label>
            <select name="platform" id="platform" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach(\App\Models\ContentPost::PLATFORMS as $val => $label)
                    <option value="{{ $val }}" {{ old('platform', $contentPost->platform) === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
            <select name="status" id="status" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach(\App\Models\ContentPost::STATUSES as $val => $label)
                    <option value="{{ $val }}" {{ old('status', $contentPost->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="scheduled_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Geplant für</label>
            <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                   value="{{ old('scheduled_at', $contentPost->scheduled_at?->format('Y-m-d\TH:i')) }}"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
    </div>

    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titel</label>
        <input type="text" name="title" id="title" value="{{ old('title', $contentPost->title) }}" placeholder="Optionaler interner Titel"
               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="caption" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Caption *</label>
        <textarea name="caption" id="caption" rows="6" required
                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('caption', $contentPost->caption) }}</textarea>
        @error('caption') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="hashtags" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hashtags</label>
        <textarea name="hashtags" id="hashtags" rows="2"
                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('hashtags', $contentPost->hashtags) }}</textarea>
    </div>

    @include('admin.content-posts._image-picker', ['contentPost' => $contentPost])

    <div>
        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notizen</label>
        <textarea name="notes" id="notes" rows="2"
                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $contentPost->notes) }}</textarea>
    </div>

    <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
        <button type="button" onclick="if(confirm('Beitrag wirklich löschen?')) document.getElementById('delete-form').submit()"
                class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">Löschen</button>
        <div class="flex gap-3">
            <a href="{{ route('admin.content-posts.show', $contentPost) }}" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">Abbrechen</a>
            <button type="submit" class="px-6 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">Speichern</button>
        </div>
    </div>
</form>

<form id="delete-form" method="POST" action="{{ route('admin.content-posts.destroy', $contentPost) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endsection
