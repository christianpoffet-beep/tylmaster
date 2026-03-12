@extends('admin.layouts.app')

@section('title', 'Neue Buchung')

@section('content')
<div class="max-w-2xl" x-data="bookingForm()">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Neue Buchung</h2>
        <p class="text-sm text-gray-500 mt-1">{{ $accounting->name }} · {{ $accounting->accountable_name }} · {{ $accounting->currency }}</p>
    </div>

    <form method="POST" action="{{ route('admin.bookings.store', $accounting) }}" enctype="multipart/form-data">
        @csrf
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Datum *
                        <span x-data="{ show: false }" class="relative inline-block ml-1">
                            <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-gray-500 text-xs hover:bg-gray-300 focus:outline-none">?</button>
                            <div x-show="show" x-transition class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg">
                                Das Buchungsdatum bestimmt, in welcher Periode die Buchung erscheint.
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                            </div>
                        </span>
                    </label>
                    <input type="date" name="booking_date" id="booking_date" value="{{ old('booking_date', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('booking_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Beleg-Nr.
                        <span x-data="{ show: false }" class="relative inline-block ml-1">
                            <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-gray-500 text-xs hover:bg-gray-300 focus:outline-none">?</button>
                            <div x-show="show" x-transition class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg">
                                Optionale Referenznummer für den Beleg (z.B. Rechnungsnummer, Quittungsnummer).
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                            </div>
                        </span>
                    </label>
                    <input type="text" name="reference" id="reference" value="{{ old('reference') }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Optional">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Beschreibung *
                    <span x-data="{ show: false }" class="relative inline-block ml-1">
                        <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-gray-500 text-xs hover:bg-gray-300 focus:outline-none">?</button>
                        <div x-show="show" x-transition class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg">
                            Kurze Beschreibung der Buchung, z.B. &laquo;Studiokosten März&raquo; oder &laquo;Mitgliedsbeitrag 2026&raquo;.
                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                        </div>
                    </span>
                </label>
                <input type="text" name="description" id="description" value="{{ old('description') }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="z.B. Studiokosten März">
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                {{-- Soll-Konto (suchbar) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Soll-Konto *
                        <span x-data="{ show: false }" class="relative inline-block ml-1">
                            <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-gray-500 text-xs hover:bg-gray-300 focus:outline-none">?</button>
                            <div x-show="show" x-transition class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg">
                                Das Konto, dem der Betrag belastet wird (Zugang bei Aktiven/Aufwand, Abgang bei Passiven/Ertrag).
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                            </div>
                        </span>
                    </label>
                    <div x-data="{ search: '', open: false }" @click.outside="open = false" class="relative">
                        <input type="text" x-model="search" @focus="open = true" @input="open = true"
                            :placeholder="debitId ? accounts.find(a => a.id == debitId)?.number + ' ' + accounts.find(a => a.id == debitId)?.name : 'Konto suchen...'"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <input type="hidden" name="debit_account_id" :value="debitId">
                        <div x-show="open" x-transition class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="acc in accounts.filter(a => !search || (a.number + ' ' + a.name).toLowerCase().includes(search.toLowerCase()))" :key="acc.id">
                                <button type="button" @click="debitId = acc.id; search = ''; open = false"
                                    class="w-full text-left px-3 py-1.5 text-sm hover:bg-blue-50 flex justify-between"
                                    :class="acc.id == debitId ? 'bg-blue-50 font-medium' : ''">
                                    <span x-text="acc.number + ' ' + acc.name"></span>
                                    <span class="text-xs text-gray-400" x-text="acc.type === 'asset' ? 'Aktiv' : acc.type === 'liability' ? 'Passiv' : acc.type === 'income' ? 'Ertrag' : 'Aufwand'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    @error('debit_account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                {{-- Haben-Konto (suchbar) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Haben-Konto *
                        <span x-data="{ show: false }" class="relative inline-block ml-1">
                            <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-gray-500 text-xs hover:bg-gray-300 focus:outline-none">?</button>
                            <div x-show="show" x-transition class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg">
                                Das Konto, dem der Betrag gutgeschrieben wird (Abgang bei Aktiven/Aufwand, Zugang bei Passiven/Ertrag).
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                            </div>
                        </span>
                    </label>
                    <div x-data="{ search: '', open: false }" @click.outside="open = false" class="relative">
                        <input type="text" x-model="search" @focus="open = true" @input="open = true"
                            :placeholder="creditId ? accounts.find(a => a.id == creditId)?.number + ' ' + accounts.find(a => a.id == creditId)?.name : 'Konto suchen...'"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <input type="hidden" name="credit_account_id" :value="creditId">
                        <div x-show="open" x-transition class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="acc in accounts.filter(a => !search || (a.number + ' ' + a.name).toLowerCase().includes(search.toLowerCase()))" :key="acc.id">
                                <button type="button" @click="creditId = acc.id; search = ''; open = false"
                                    class="w-full text-left px-3 py-1.5 text-sm hover:bg-blue-50 flex justify-between"
                                    :class="acc.id == creditId ? 'bg-blue-50 font-medium' : ''">
                                    <span x-text="acc.number + ' ' + acc.name"></span>
                                    <span class="text-xs text-gray-400" x-text="acc.type === 'asset' ? 'Aktiv' : acc.type === 'liability' ? 'Passiv' : acc.type === 'income' ? 'Ertrag' : 'Aufwand'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    @error('credit_account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Betrag ({{ $accounting->currency }}) *
                    <span x-data="{ show: false }" class="relative inline-block ml-1">
                        <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-gray-500 text-xs hover:bg-gray-300 focus:outline-none">?</button>
                        <div x-show="show" x-transition class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg">
                            Immer positiv eingeben. Die Richtung wird durch Soll/Haben bestimmt.
                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                        </div>
                    </span>
                </label>
                <input type="number" name="amount" id="amount" value="{{ old('amount') }}" step="0.01" min="0.01" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="0.00">
                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notizen</label>
                <textarea name="notes" id="notes" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
            </div>

            {{-- Verknüpfungen --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Verknüpfungen (optional)</p>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="project_id" class="block text-xs text-gray-500 mb-1">Projekt</label>
                        <select name="project_id" id="project_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">—</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="organization_id" class="block text-xs text-gray-500 mb-1">Organisation</label>
                        <select name="organization_id" id="organization_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">—</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ old('organization_id') == $org->id ? 'selected' : '' }}>{{ $org->primary_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="contact_id" class="block text-xs text-gray-500 mb-1">Kontakt</label>
                        <select name="contact_id" id="contact_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">—</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>{{ $contact->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Belege --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <label for="documents" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Belege hochladen</label>
                <input type="file" name="documents[]" id="documents" multiple class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                <p class="text-xs text-gray-400 mt-1">Mehrere Dateien möglich (max. 50 MB pro Datei)</p>
                @error('documents.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Buchen</button>
            <a href="{{ route('admin.accountings.journal', $accounting) }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>
</div>

@php
    $accountsJson = $accounts->map(function($a) {
        return ['id' => $a->id, 'number' => $a->number, 'name' => $a->name, 'type' => $a->type];
    })->values();
@endphp
<script>
function bookingForm() {
    return {
        accounts: @json($accountsJson),
        debitId: '{{ old('debit_account_id', '') }}',
        creditId: '{{ old('credit_account_id', '') }}',
    }
}
</script>
@endsection
