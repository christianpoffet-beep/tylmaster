@extends('admin.layouts.app')

@section('title', 'Neuer Ordner')

@section('content')
<div class="max-w-3xl">
    <div class="mb-4">
        <a href="{{ route('admin.photos.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-300">&larr; Zurück</a>
    </div>

    <form method="POST" action="{{ route('admin.photos.folders.store') }}">
        @csrf

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Beschreibung</label>
                <textarea name="description" id="description" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
            </div>

            <div>
                <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Übergeordneter Ordner</label>
                <select name="parent_id" id="parent_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— Kein (Root-Ordner) —</option>
                    @foreach($parentFolders as $pf)
                        <option value="{{ $pf->id }}" {{ old('parent_id', $parentId) == $pf->id ? 'selected' : '' }}>{{ $pf->name }}</option>
                    @endforeach
                </select>
            </div>

            <hr class="border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Verknüpfungen</h3>

            <div>
                @include('admin.partials.contact-search', ['selected' => collect()])
            </div>

            <div>
                @include('admin.partials.organization-search', ['selected' => collect(), 'inputName' => 'organization_ids[]', 'orgSearchLabel' => 'Organisationen', 'orgTypeFilter' => ''])
            </div>

            <div>
                @include('admin.partials.project-search', ['selected' => collect()])
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Ordner erstellen</button>
            <a href="{{ route('admin.photos.index') }}" class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
