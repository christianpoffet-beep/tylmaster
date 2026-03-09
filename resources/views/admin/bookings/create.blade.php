@extends('admin.layouts.app')

@section('title', 'Neue Buchung')

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Neue Buchung</h2>
        <p class="text-sm text-gray-500 mt-1">{{ $accounting->name }} · {{ $accounting->accountable_name }} · {{ $accounting->currency }}</p>
    </div>

    <form method="POST" action="{{ route('admin.bookings.store', $accounting) }}" enctype="multipart/form-data">
        @csrf
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="booking_date" class="block text-sm font-medium text-gray-700 mb-1">Datum *</label>
                    <input type="date" name="booking_date" id="booking_date" value="{{ old('booking_date', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('booking_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="reference" class="block text-sm font-medium text-gray-700 mb-1">Beleg-Nr.</label>
                    <input type="text" name="reference" id="reference" value="{{ old('reference') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Optional">
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Beschreibung *</label>
                <input type="text" name="description" id="description" value="{{ old('description') }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="z.B. Studiokosten März">
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="debit_account_id" class="block text-sm font-medium text-gray-700 mb-1">Soll-Konto *</label>
                    <select name="debit_account_id" id="debit_account_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Konto wählen —</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('debit_account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->number }} {{ $acc->name }}</option>
                        @endforeach
                    </select>
                    @error('debit_account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="credit_account_id" class="block text-sm font-medium text-gray-700 mb-1">Haben-Konto *</label>
                    <select name="credit_account_id" id="credit_account_id" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Konto wählen —</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('credit_account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->number }} {{ $acc->name }}</option>
                        @endforeach
                    </select>
                    @error('credit_account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Betrag ({{ $accounting->currency }}) *</label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="0.00">
                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notizen</label>
                <textarea name="notes" id="notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
            </div>

            {{-- Verknüpfungen --}}
            <div class="border-t border-gray-200 pt-6">
                <p class="text-sm font-medium text-gray-700 mb-3">Verknüpfungen (optional)</p>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="project_id" class="block text-xs text-gray-500 mb-1">Projekt</label>
                        <select name="project_id" id="project_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">—</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="organization_id" class="block text-xs text-gray-500 mb-1">Organisation</label>
                        <select name="organization_id" id="organization_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">—</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ old('organization_id') == $org->id ? 'selected' : '' }}>{{ $org->primary_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="contact_id" class="block text-xs text-gray-500 mb-1">Kontakt</label>
                        <select name="contact_id" id="contact_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">—</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>{{ $contact->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Belege --}}
            <div class="border-t border-gray-200 pt-6">
                <label for="documents" class="block text-sm font-medium text-gray-700 mb-1">Belege hochladen</label>
                <input type="file" name="documents[]" id="documents" multiple class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-xs text-gray-400 mt-1">Mehrere Dateien möglich (max. 50 MB pro Datei)</p>
                @error('documents.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Buchen</button>
            <a href="{{ route('admin.accountings.journal', $accounting) }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
