@extends('admin.layouts.app')

@section('title', 'Kontoplan-Vorlage erstellen')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.chart-templates.store') }}">
        @csrf
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="z.B. Label-Kontoplan">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Beschreibung</label>
                <textarea name="description" id="description" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
            </div>

            <div>
                <label for="organization_type_slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Organisationstyp</label>
                <select name="organization_type_slug" id="organization_type_slug" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— Kein spezifischer Typ —</option>
                    @foreach($orgTypes as $orgType)
                        <option value="{{ $orgType->slug }}" {{ old('organization_type_slug') === $orgType->slug ? 'selected' : '' }}>{{ $orgType->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Optional: Vorlage einem Organisationstyp zuordnen</p>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Vorlage erstellen</button>
            <a href="{{ route('admin.chart-templates.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
