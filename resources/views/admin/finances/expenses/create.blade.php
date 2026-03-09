@extends('admin.layouts.app')

@section('title', 'Neue Ausgabe')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.expenses.store') }}">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Beschreibung *</label>
                <input type="text" name="description" id="description" value="{{ old('description') }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Betrag *</label>
                    <input type="number" name="amount" id="amount" value="{{ old('amount') }}" step="0.01" min="0" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Währung</label>
                    <select name="currency" id="currency" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(['CHF' => 'CHF', 'EUR' => 'EUR', 'USD' => 'USD'] as $value => $label)
                            <option value="{{ $value }}" {{ old('currency', 'CHF') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('currency') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="expense_date" class="block text-sm font-medium text-gray-700 mb-1">Datum *</label>
                    <input type="date" name="expense_date" id="expense_date" value="{{ old('expense_date') }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('expense_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Kategorie</label>
                    <input type="text" name="category" id="category" value="{{ old('category') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="contact_id" class="block text-sm font-medium text-gray-700 mb-1">Kontakt</label>
                <select name="contact_id" id="contact_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Kein Kontakt --</option>
                    @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>{{ $contact->full_name }}</option>
                    @endforeach
                </select>
                @error('contact_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Ausgabe erstellen</button>
            <a href="{{ route('admin.expenses.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
