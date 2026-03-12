@extends('admin.layouts.app')

@section('title', 'Vorlage bearbeiten')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.chart-templates.update', $chartTemplate) }}">
        @csrf @method('PUT')
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $chartTemplate->name) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Beschreibung</label>
                <textarea name="description" id="description" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $chartTemplate->description) }}</textarea>
            </div>

            <div>
                <label for="organization_type_slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Organisationstyp</label>
                <select name="organization_type_slug" id="organization_type_slug" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— Kein spezifischer Typ —</option>
                    @foreach($orgTypes as $orgType)
                        <option value="{{ $orgType->slug }}" {{ old('organization_type_slug', $chartTemplate->organization_type_slug) === $orgType->slug ? 'selected' : '' }}>{{ $orgType->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Speichern</button>
            <a href="{{ route('admin.chart-templates.show', $chartTemplate) }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>

    @if($chartTemplate->usage_count === 0)
    <div class="mt-8 bg-red-50 rounded-xl border border-red-200 p-6">
        <h3 class="text-sm font-medium text-red-800 mb-2">Vorlage löschen</h3>
        <p class="text-sm text-red-600 mb-3">Diese Aktion kann nicht rückgängig gemacht werden.</p>
        <form method="POST" action="{{ route('admin.chart-templates.destroy', $chartTemplate) }}" onsubmit="return confirm('Vorlage wirklich löschen?')">
            @csrf @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white text-sm rounded-lg hover:bg-red-700 dark:hover:bg-red-600">Vorlage löschen</button>
        </form>
    </div>
    @endif
</div>
@endsection
