@extends('admin.layouts.app')

@section('title', 'Produktvorlage bearbeiten')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.product-templates.update', $productTemplate) }}">
        @csrf
        @method('PUT')
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $productTemplate->name) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sprache *</label>
                    <select name="language" id="language" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(['de' => 'Deutsch', 'en' => 'English', 'fr' => 'Français', 'it' => 'Italiano', 'es' => 'Español'] as $code => $label)
                            <option value="{{ $code }}" {{ old('language', $productTemplate->language) === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('language') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sortierung</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $productTemplate->sort_order) }}" min="0" class="w-24 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('sort_order') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Standard-Produkttypen --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Standard-Produkttypen</p>
                <p class="text-xs text-gray-400 mb-4">Diese Produkttypen werden beim Verwenden der Vorlage vorausgewählt.</p>
                @error('default_product_types') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror
                @error('default_product_types.*') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

                @php $selectedTypes = old('default_product_types', $productTemplate->default_product_types ?? []); @endphp

                @foreach($productTypes as $group => $types)
                    <div class="mb-4">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">{{ $group }}</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach($types as $type)
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="default_product_types[]" value="{{ $type }}" {{ in_array($type, $selectedTypes) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-gray-700">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $type }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Release-Info --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <label for="default_release_info" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Standard Release-Info</label>
                <textarea name="default_release_info" id="default_release_info" rows="6" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('default_release_info', $productTemplate->default_release_info) }}</textarea>
                @error('default_release_info') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Speichern</button>
            <a href="{{ route('admin.product-templates.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>

    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-red-600 mb-2">Gefahrenzone</h3>
        <form method="POST" action="{{ route('admin.product-templates.destroy', $productTemplate) }}" onsubmit="return confirm('Vorlage wirklich löschen?')">
            @csrf
            @method('DELETE')
            <p class="text-sm text-gray-500 mb-3">Die Produktvorlage wird unwiderruflich gelöscht.</p>
            <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Vorlage löschen</button>
        </form>
    </div>
</div>
@endsection
