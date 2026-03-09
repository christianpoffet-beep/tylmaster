@extends('admin.layouts.app')

@section('title', 'Projekttyp erstellen')

@section('content')
<div class="max-w-lg">
    <form method="POST" action="{{ route('admin.project-types.store') }}">
        @csrf
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="z.B. Musikvideo, Tour..." class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Farbe *</label>
                <select name="color" id="color" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($colorOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('color', 'bg-gray-100 text-gray-600') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($colorOptions as $value => $label)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $value }}">{{ $label }}</span>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Reihenfolge</label>
                <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="w-24 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Typ erstellen</button>
            <a href="{{ route('admin.project-types.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
