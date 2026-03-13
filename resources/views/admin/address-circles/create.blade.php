@extends('admin.layouts.app')

@section('title', 'Neuer Adresskreis')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.address-circles.store') }}">
        @csrf

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="z.B. Newsletter Dezember 2026">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="info" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Beschreibung *</label>
                <textarea name="info" id="info" rows="3" required
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500"
                          placeholder="Zweck und Beschreibung des Adresskreises">{{ old('info') }}</textarea>
                @error('info') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Verknüpfte Organisationen --}}
            <div x-data="{ ids: {{ json_encode(old('organization_ids', [])) }} }">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Verknüpfte Organisationen</label>
                @include('admin.partials.organization-search', ['fieldName' => 'organization_ids', 'multiple' => true])
            </div>

            {{-- Verknüpfte Projekte --}}
            <div x-data="{ ids: {{ json_encode(old('project_ids', [])) }} }">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Verknüpfte Projekte</label>
                @include('admin.partials.project-search', ['fieldName' => 'project_ids', 'multiple' => true])
            </div>
        </div>

        <div class="mt-6 flex items-center gap-4">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Erstellen</button>
            <a href="{{ route('admin.address-circles.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
