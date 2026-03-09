@extends('admin.layouts.app')

@section('title', 'Neue Buchhaltung')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.accountings.store') }}" x-data="{ entityType: '{{ old('accountable_type', 'organization') }}' }">
        @csrf
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Zuordnung *</label>
                <div class="flex gap-4 mb-3">
                    <label class="inline-flex items-center">
                        <input type="radio" name="accountable_type" value="organization" x-model="entityType" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700">Organisation</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="accountable_type" value="contact" x-model="entityType" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700">Kontakt</span>
                    </label>
                </div>

                <div x-show="entityType === 'organization'" x-cloak>
                    <select name="accountable_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" :disabled="entityType !== 'organization'" :required="entityType === 'organization'">
                        <option value="">— Organisation wählen —</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ old('accountable_id') == $org->id && old('accountable_type') === 'organization' ? 'selected' : '' }}>{{ $org->primary_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="entityType === 'contact'" x-cloak>
                    <select name="accountable_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" :disabled="entityType !== 'contact'" :required="entityType === 'contact'">
                        <option value="">— Kontakt wählen —</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}" {{ old('accountable_id') == $contact->id && old('accountable_type') === 'contact' ? 'selected' : '' }}>{{ $contact->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                @error('accountable_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', 'Buchhaltung ' . $year) }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="period_start" class="block text-sm font-medium text-gray-700 mb-1">Periode von *</label>
                    <input type="date" name="period_start" id="period_start" value="{{ old('period_start', $year . '-01-01') }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="period_end" class="block text-sm font-medium text-gray-700 mb-1">Periode bis *</label>
                    <input type="date" name="period_end" id="period_end" value="{{ old('period_end', $year . '-12-31') }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Währung *</label>
                <select name="currency" id="currency" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="CHF" {{ old('currency', 'CHF') === 'CHF' ? 'selected' : '' }}>CHF</option>
                    <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR</option>
                    <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD</option>
                </select>
            </div>

            <div>
                <label for="chart_template_id" class="block text-sm font-medium text-gray-700 mb-1">Kontoplan-Vorlage</label>
                <select name="chart_template_id" id="chart_template_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— Keine Vorlage —</option>
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}" {{ old('chart_template_id') == $tpl->id ? 'selected' : '' }}>{{ $tpl->name }} ({{ $tpl->accounts()->count() }} Konten)</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Konten werden aus der Vorlage kopiert. Du kannst sie danach anpassen.</p>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notizen</label>
                <textarea name="notes" id="notes" rows="3" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Buchhaltung erstellen</button>
            <a href="{{ route('admin.accountings.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
