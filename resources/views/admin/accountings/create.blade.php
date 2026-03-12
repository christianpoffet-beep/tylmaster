@extends('admin.layouts.app')

@section('title', 'Neue Buchhaltung')

@php
    $typeLabels = ['asset' => 'Aktiven', 'liability' => 'Passiven', 'income' => 'Ertrag', 'expense' => 'Aufwand'];
    $typeColors = ['asset' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300', 'liability' => 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300', 'income' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300', 'expense' => 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300'];

    $templatesJson = $templates->mapWithKeys(function($tpl) {
        return [$tpl->id => $tpl->accounts->map(function($a) {
            return ['number' => $a->number, 'name' => $a->name, 'type' => $a->type, 'is_header' => $a->is_header ? true : false];
        })->values()];
    });
@endphp

@section('content')
<div class="max-w-3xl" x-data="accountingWizard()">
    <form method="POST" action="{{ route('admin.accountings.store') }}" @submit.prevent="submitForm($event)">
        @csrf

        {{-- Schritt 1: Grunddaten --}}
        <div x-show="step === 1">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Zuordnung *</label>
                    <div class="flex gap-4 mb-3">
                        <label class="inline-flex items-center">
                            <input type="radio" name="accountable_type" value="organization" x-model="entityType" class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">Organisation</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="accountable_type" value="contact" x-model="entityType" class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">Kontakt</span>
                        </label>
                    </div>

                    <div x-show="entityType === 'organization'" x-cloak>
                        <select name="accountable_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" :disabled="entityType !== 'organization'" :required="entityType === 'organization'">
                            <option value="">— Organisation wählen —</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ old('accountable_id') == $org->id && old('accountable_type') === 'organization' ? 'selected' : '' }}>{{ $org->primary_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="entityType === 'contact'" x-cloak>
                        <select name="accountable_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" :disabled="entityType !== 'contact'" :required="entityType === 'contact'">
                            <option value="">— Kontakt wählen —</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}" {{ old('accountable_id') == $contact->id && old('accountable_type') === 'contact' ? 'selected' : '' }}>{{ $contact->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('accountable_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', 'Buchhaltung ' . $year) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="period_start" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periode von *</label>
                        <input type="date" name="period_start" id="period_start" value="{{ old('period_start', $year . '-01-01') }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="period_end" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periode bis *</label>
                        <input type="date" name="period_end" id="period_end" value="{{ old('period_end', $year . '-12-31') }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Währung *</label>
                    <select name="currency" id="currency" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="CHF" {{ old('currency', 'CHF') === 'CHF' ? 'selected' : '' }}>CHF</option>
                        <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR</option>
                        <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD</option>
                    </select>
                </div>

                <div>
                    <label for="chart_template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kontoplan-Vorlage</label>
                    <select name="chart_template_id" id="chart_template_id" x-model="templateId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Keine Vorlage —</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}" {{ old('chart_template_id') == $tpl->id ? 'selected' : '' }}>{{ $tpl->name }} ({{ $tpl->accounts->count() }} Konten)</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Konten werden aus der Vorlage kopiert.</p>
                </div>

                {{-- Eröffnungssaldi-Option (nur sichtbar wenn Vorlage gewählt) --}}
                <div x-show="templateId" x-cloak x-transition>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Eröffnungssaldi &amp; Kontenplan</label>
                    <div class="space-y-2">
                        <label class="flex items-start gap-2 p-3 rounded-lg border cursor-pointer transition-colors" :class="openingMode === 'zero' ? 'border-blue-300 bg-blue-50' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                            <input type="radio" value="zero" x-model="openingMode" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Vorlage direkt übernehmen</span>
                                <p class="text-xs text-gray-500 mt-0.5">Alle Konten starten bei 0. Du kannst sie danach anpassen.</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-2 p-3 rounded-lg border cursor-pointer transition-colors" :class="openingMode === 'manual' ? 'border-blue-300 bg-blue-50' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                            <input type="radio" value="manual" x-model="openingMode" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Kontenplan anpassen</span>
                                <p class="text-xs text-gray-500 mt-0.5">Konten bearbeiten, hinzufügen oder löschen und Eröffnungssaldi festlegen.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notizen</label>
                    <textarea name="notes" id="notes" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-3">
                <template x-if="templateId && openingMode === 'manual'">
                    <button type="button" @click="goToStep2()" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Weiter &rarr;</button>
                </template>
                <template x-if="!templateId || openingMode === 'zero'">
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Buchhaltung erstellen</button>
                </template>
                <a href="{{ route('admin.accountings.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
            </div>
        </div>

        {{-- Schritt 2: Kontenplan anpassen --}}
        <div x-show="step === 2" x-cloak>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Kontenplan anpassen</h3>
                    <p class="text-xs text-gray-500 mt-1">Bearbeite, lösche oder füge Konten hinzu. Eröffnungssaldi nur für Aktiv- und Passivkonten.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-20">Nr.</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bezeichnung</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-28">Typ</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-36">Eröffnungssaldo</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="(account, index) in accounts" :key="index">
                                <tr :class="account.is_header ? 'bg-gray-50' : ''">
                                    <td class="px-3 py-2 text-sm" :class="account.is_header ? 'font-bold' : ''">
                                        {{-- Hidden inputs im ersten td (ausserhalb td = ungültiges HTML) --}}
                                        <input type="hidden" :name="'custom_accounts[' + index + '][number]'" :value="account.number">
                                        <input type="hidden" :name="'custom_accounts[' + index + '][name]'" :value="account.name">
                                        <input type="hidden" :name="'custom_accounts[' + index + '][type]'" :value="account.type">
                                        <input type="hidden" :name="'custom_accounts[' + index + '][is_header]'" :value="account.is_header ? '1' : '0'">
                                        <input type="hidden" :name="'custom_accounts[' + index + '][opening_balance]'" :value="account.opening_balance || 0">
                                        <span x-text="account.number"></span>
                                    </td>
                                    <td class="px-3 py-2 text-sm" :class="account.is_header ? 'font-bold' : ''">
                                        <span x-text="account.name"></span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                            :class="typeColors[account.type]"
                                            x-text="typeLabels[account.type]"></span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <template x-if="!account.is_header && (account.type === 'asset' || account.type === 'liability')">
                                            <input type="number" step="0.01"
                                                x-model.number="account.opening_balance"
                                                class="w-full text-right rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                                        </template>
                                        <template x-if="!account.is_header && account.type !== 'asset' && account.type !== 'liability'">
                                            <span class="text-sm text-gray-400 dark:text-gray-500 font-mono">0.00</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <template x-if="!account.is_header">
                                            <button type="button" @click="removeAccount(index)" class="text-red-400 hover:text-red-600" title="Konto entfernen">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Konto hinzufügen --}}
                <div class="px-4 py-4 bg-gray-50 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Konto hinzufügen</p>
                    <div class="flex flex-wrap items-end gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Nr.</label>
                            <input type="text" x-model="newAccount.number" placeholder="1000" class="w-20 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="flex-1 min-w-[120px]">
                            <label class="block text-xs text-gray-500 mb-0.5">Bezeichnung</label>
                            <input type="text" x-model="newAccount.name" placeholder="Kasse" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Typ</label>
                            <select x-model="newAccount.type" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="asset">Aktiven</option>
                                <option value="liability">Passiven</option>
                                <option value="income">Ertrag</option>
                                <option value="expense">Aufwand</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Eröffnungssaldo</label>
                            <input type="number" step="0.01" x-model.number="newAccount.opening_balance" placeholder="0.00" class="w-28 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-right">
                        </div>
                        <button type="button" @click="addAccount()" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">+</button>
                    </div>
                    <p x-show="addError" x-text="addError" class="text-red-500 text-xs mt-1"></p>
                </div>
            </div>

            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                <span x-text="accounts.filter(a => !a.is_header).length"></span> Konten
            </div>

            <div class="mt-3 flex items-center gap-3">
                <button type="button" @click="step = 1" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">&larr; Zurück</button>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Buchhaltung erstellen</button>
            </div>
        </div>
    </form>
</div>

<script>
function accountingWizard() {
    return {
        step: 1,
        entityType: '{{ old('accountable_type', 'organization') }}',
        templateId: '{{ old('chart_template_id', '') }}',
        openingMode: 'zero',
        accounts: [],
        templates: @json($templatesJson),
        typeLabels: { asset: 'Aktiven', liability: 'Passiven', income: 'Ertrag', expense: 'Aufwand' },
        typeColors: {
            asset: 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
            liability: 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300',
            income: 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300',
            expense: 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300'
        },
        newAccount: { number: '', name: '', type: 'asset', opening_balance: 0 },
        addError: '',

        goToStep2() {
            if (!this.templateId || !this.templates[this.templateId]) return;
            // Kopiere Template-Konten in editierbare Liste (nur beim ersten Mal)
            if (this.accounts.length === 0) {
                this.accounts = this.templates[this.templateId].map(function(a) {
                    return {
                        number: a.number,
                        name: a.name,
                        type: a.type,
                        is_header: a.is_header,
                        opening_balance: 0
                    };
                });
            }
            this.step = 2;
        },

        removeAccount(index) {
            this.accounts.splice(index, 1);
        },

        addAccount() {
            this.addError = '';
            if (!this.newAccount.number || !this.newAccount.name) {
                this.addError = 'Nr. und Bezeichnung sind Pflichtfelder.';
                return;
            }
            if (this.accounts.some(function(a) { return a.number === this.newAccount.number; }.bind(this))) {
                this.addError = 'Kontonummer ' + this.newAccount.number + ' existiert bereits.';
                return;
            }

            // Einfügen an der richtigen Stelle (sortiert nach Nummer)
            var newAcc = {
                number: this.newAccount.number,
                name: this.newAccount.name,
                type: this.newAccount.type,
                is_header: false,
                opening_balance: this.newAccount.opening_balance || 0
            };

            var inserted = false;
            for (var i = 0; i < this.accounts.length; i++) {
                if (this.accounts[i].number > newAcc.number) {
                    this.accounts.splice(i, 0, newAcc);
                    inserted = true;
                    break;
                }
            }
            if (!inserted) {
                this.accounts.push(newAcc);
            }

            this.newAccount = { number: '', name: '', type: 'asset', opening_balance: 0 };
        },

        submitForm(event) {
            event.target.submit();
        }
    }
}
</script>
@endsection
