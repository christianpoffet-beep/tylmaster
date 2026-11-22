@extends('admin.layouts.app')

@section('title', 'Aus Webling importieren')

@php
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
    $defaultTemplate = old('chart_template_id', $templates->firstWhere('slug', 'musiklabel-verein')?->id);
@endphp

@section('content')
<div class="max-w-3xl" x-data="weblingImport()">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Aus Webling importieren</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ein Jahr, das in Webling geführt wurde, als Buchhaltung übernehmen – zum Beispiel als Vorjahr, aus dem Du das nächste Jahr eröffnest.</p>
    </div>

    <form method="POST" action="{{ route('admin.accountings.importWebling.store') }}" enctype="multipart/form-data" @submit="submitForm($event)">
        @csrf
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Webling-Export *</label>
                <input type="file" name="file" id="file" accept=".xlsx" required
                       class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 dark:file:bg-blue-900/50 dark:file:text-blue-300 hover:file:bg-blue-100">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Der Excel-Export «Bilanz und Erfolgsrechnung» aus Webling. Gebraucht werden die Blätter Erfolgsrechnung, Bilanz und Eröffnungsbilanz.</p>
                @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Zuordnung *</label>
                <input type="hidden" name="accountable_type" :value="party.type">
                <input type="hidden" name="accountable_id" :value="party.type === 'organization' ? party.organization_id : party.contact_id">
                @include('admin.partials.contract-party-picker', ['members' => false, 'withContactPerson' => false])
                <p x-show="ownerError" x-cloak x-text="ownerError" class="text-red-500 text-xs mt-1"></p>
                @error('accountable_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="z.B. TYL 2024" class="{{ $input }}">
                    <p class="text-xs text-gray-400 mt-1">Leer: «Buchhaltung 2024 (Webling)»</p>
                </div>
                <div>
                    <label for="chart_template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kontenplan-Vorlage</label>
                    <select name="chart_template_id" id="chart_template_id" class="{{ $input }}">
                        <option value="">— Kontenplan aus Webling —</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}" @selected((string) $defaultTemplate === (string) $template->id)>{{ $template->name }} ({{ $template->accounts_count }} Konten)</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Gibt die Kontonummern vor – am besten dieselbe wie in den späteren Jahren.</p>
                </div>
            </div>

            <div class="rounded-lg bg-blue-50 dark:bg-blue-900/30 p-4 text-sm text-blue-800 dark:text-blue-200">
                <p class="font-medium">So wird übernommen</p>
                <ul class="list-disc list-inside mt-1 space-y-0.5 text-blue-700 dark:text-blue-300">
                    <li>Die Webling-Konten werden den Konten der Vorlage zugeordnet (nach Namen, z.B. «Herstellungskosten für Tonträger» → 6004). Was die Vorlage nicht kennt, kommt neu in die passende Gruppe.</li>
                    <li>Die Eröffnungsbilanz wird zu den Eröffnungssaldi.</li>
                    <li>Einzelbuchungen enthält der Export nicht: Pro Konto entsteht eine Sammelbuchung über das Jahr gegen die Bank. Erfolgsrechnung und Schlussbilanz stimmen danach mit Webling überein.</li>
                    <li>Die Datei wird nur gelesen, nicht gespeichert.</li>
                </ul>
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Importieren</button>
            <a href="{{ route('admin.accountings.index') }}" class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Abbrechen</a>
        </div>
    </form>
</div>

@include('admin.partials.contract-party-picker-script', ['organizations' => $organizations, 'contacts' => $contacts])

<script>
function weblingImport() {
    return {
        party: {
            type: @js(old('accountable_type') === 'contact' ? 'contact' : 'organization'),
            organization_id: @js(old('accountable_type', 'organization') === 'organization' ? (string) old('accountable_id', '') : ''),
            contact_id: @js(old('accountable_type') === 'contact' ? (string) old('accountable_id', '') : ''),
        },
        ownerError: '',
        init() {
            this.$watch('party', () => { this.ownerError = ''; });
        },
        submitForm(event) {
            const ownerId = this.party.type === 'organization' ? this.party.organization_id : this.party.contact_id;
            if (!ownerId) {
                event.preventDefault();
                this.ownerError = 'Bitte eine Organisation oder Person wählen.';
            }
        },
    };
}
</script>
@endsection
