@extends('admin.layouts.app')

@section('title', 'Kampagnenvorlage erstellen')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.campaign-templates.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">&larr; Zurück zu Kampagnenvorlagen</a>
</div>

<form method="POST" action="{{ route('admin.campaign-templates.store') }}" class="max-w-4xl">
    @csrf

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Kampagnenvorlage erstellen</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Typ *</label>
                    <select name="type" id="type" required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="email" {{ old('type') === 'email' ? 'selected' : '' }}>E-Mail</option>
                        <option value="letter" {{ old('type') === 'letter' ? 'selected' : '' }}>Brief</option>
                    </select>
                </div>
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sprache *</label>
                    <select name="language" id="language" required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="de" {{ old('language', 'de') === 'de' ? 'selected' : '' }}>Deutsch</option>
                        <option value="en" {{ old('language') === 'en' ? 'selected' : '' }}>English</option>
                        <option value="fr" {{ old('language') === 'fr' ? 'selected' : '' }}>Français</option>
                        <option value="it" {{ old('language') === 'it' ? 'selected' : '' }}>Italiano</option>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Betreff</label>
            <input type="text" name="subject" id="subject" value="{{ old('subject') }}"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            @error('subject') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="body" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Inhalt (HTML)</label>
            <textarea name="body" id="body" rows="20"
                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm font-mono focus:border-blue-500 focus:ring-blue-500">{{ old('body') }}</textarea>
            <p class="text-xs text-gray-400 mt-1">Platzhalter: {name}, {email}, {organization} — werden beim Versand ersetzt.</p>
            @error('body') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notizen (intern)</label>
                <textarea name="notes" id="notes" rows="3"
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
            </div>
            <div>
                <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reihenfolge</label>
                <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-4">
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Erstellen</button>
        <a href="{{ route('admin.campaign-templates.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Abbrechen</a>
    </div>
</form>
@endsection
