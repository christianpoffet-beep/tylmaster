@extends('admin.layouts.app')

@section('title', 'Ordner bearbeiten')

@section('content')
<div class="max-w-3xl">
    <div class="mb-4">
        <a href="{{ route('admin.photos.folders.show', $folder) }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-300">&larr; Zurück zum Ordner</a>
    </div>

    <form method="POST" action="{{ route('admin.photos.folders.update', $folder) }}">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $folder->name) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Beschreibung</label>
                <textarea name="description" id="description" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $folder->description) }}</textarea>
            </div>

            <div>
                <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Übergeordneter Ordner</label>
                <select name="parent_id" id="parent_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— Kein (Root-Ordner) —</option>
                    @foreach($parentFolders as $pf)
                        <option value="{{ $pf->id }}" {{ old('parent_id', $folder->parent_id) == $pf->id ? 'selected' : '' }}>{{ $pf->name }}</option>
                    @endforeach
                </select>
            </div>

            <hr class="border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Verknüpfungen</h3>

            @php $selectedContactIds = old('contact_ids', $folder->contacts->pluck('id')->toArray()); @endphp
            @if($contacts->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kontakte</label>
                <div class="max-h-36 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2">
                    @foreach($contacts as $contact)
                        <label class="flex items-center">
                            <input type="checkbox" name="contact_ids[]" value="{{ $contact->id }}" {{ in_array($contact->id, $selectedContactIds) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $contact->full_name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            @php $selectedOrgIds = old('organization_ids', $folder->organizations->pluck('id')->toArray()); @endphp
            @if($organizations->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Organisationen</label>
                <div class="max-h-36 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2">
                    @foreach($organizations as $org)
                        <label class="flex items-center">
                            <input type="checkbox" name="organization_ids[]" value="{{ $org->id }}" {{ in_array($org->id, $selectedOrgIds) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $org->primary_name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            @php $selectedProjectIds = old('project_ids', $folder->projects->pluck('id')->toArray()); @endphp
            @if($projects->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Projekte</label>
                <div class="max-h-36 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2">
                    @foreach($projects as $project)
                        <label class="flex items-center">
                            <input type="checkbox" name="project_ids[]" value="{{ $project->id }}" {{ in_array($project->id, $selectedProjectIds) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $project->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Speichern</button>
            <a href="{{ route('admin.photos.folders.show', $folder) }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
