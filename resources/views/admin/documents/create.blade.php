@extends('admin.layouts.app')

@section('title', 'Dokument hochladen')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.documents.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Titel *</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Kategorie *</label>
                <select name="category" id="category" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach(['contract' => 'Vertrag', 'invoice' => 'Rechnung', 'legal' => 'Rechtliches', 'music' => 'Musik', 'other' => 'Sonstiges'] as $v => $l)
                        <option value="{{ $v }}" {{ old('category') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Datei *</label>
                <input type="file" name="file" id="file" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-xs text-gray-400 mt-1">Max. 50 MB</p>
                @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notiz</label>
                <input type="text" name="notes" id="notes" value="{{ old('notes') }}" placeholder="Optionale Notiz zum Dokument..." class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Hochladen</button>
            <a href="{{ route('admin.documents.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
