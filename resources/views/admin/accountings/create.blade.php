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

                @if($sources->isNotEmpty())
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <label class="flex items-start gap-2 p-3 rounded-lg border cursor-pointer transition-colors" :class="mode === 'new' ? 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/50' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                                <input type="radio" value="new" x-model="mode" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Neue Buchhaltung</span>
                                    <p class="text-xs text-gray-500 mt-0.5">Mit einer Vorlage oder einem eigenen Kontenplan.</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 p-3 rounded-lg border cursor-pointer transition-colors" :class="mode === 'carry' ? 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/50' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                                <input type="radio" value="carry" x-model="mode" @change="if (!sourceId && sources.length) { sourceId = String(sources[0].id); applySource(); }" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Folgejahr eröffnen</span>
                                    <p class="text-xs text-gray-500 mt-0.5">Kontenplan und Schlusssalden einer bestehenden Buchhaltung übernehmen (Saldovortrag).</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Folgejahr: Vorjahr, Eigenkapitalkonto, Vorschau der Eröffnungsbilanz --}}
                    <fieldset x-show="mode === 'carry'" x-cloak :disabled="mode !== 'carry'" class="space-y-4">
                        <div>
                            <label for="source_accounting_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Schlusssalden übernehmen aus *</label>
                            <select name="source_accounting_id" id="source_accounting_id" x-model="sourceId" @change="applySource()" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach($sources as $source)
                                    <option value="{{ $source->id }}">{{ $source->name }} · {{ $source->accountable_name }} ({{ $source->period_start->format('d.m.Y') }} – {{ $source->period_end->format('d.m.Y') }}){{ $source->is_closed ? ' · abgeschlossen' : '' }}</option>
                                @endforeach
                            </select>
                            @error('source_accounting_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-show="source" x-text="source ? 'Inhaber ' + source.owner + ' · ' + source.currency + ' · wie im Vorjahr' : ''"></p>
                            <p class="text-xs text-amber-700 dark:text-amber-300 mt-1" x-show="source && source.next.length" x-text="source && source.next.length ? 'Aus dieser Buchhaltung wurde schon «' + source.next.join('», «') + '» eröffnet.' : ''"></p>
                        </div>

                        <div>
                            <label for="profit_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gewinn / Verlust des Vorjahres auf *</label>
                            <select name="profit_account_id" id="profit_account_id" x-model="profitAccountId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">— Eigenkapitalkonto wählen —</option>
                                <template x-for="account in liabilityAccounts" :key="account.id">
                                    <option :value="account.id" :selected="account.id == profitAccountId" x-text="account.number + ' ' + account.name"></option>
                                </template>
                            </select>
                            @error('profit_account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="preview" class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Eröffnungsbilanz <span class="font-normal text-gray-500 dark:text-gray-400" x-text="'per ' + dateLabel(periodStart)"></span></p>
                            <template x-if="preview">
                                <div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-3 text-sm">
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Aktiven</p>
                                            <template x-for="line in preview.assets" :key="line.id">
                                                <div class="flex justify-between gap-3 text-gray-700 dark:text-gray-300">
                                                    <span class="truncate" x-text="line.number + ' ' + line.name"></span>
                                                    <span class="font-mono" x-text="fmt(line.opening)"></span>
                                                </div>
                                            </template>
                                            <p x-show="!preview.assets.length" class="text-xs text-gray-400">Keine Saldi</p>
                                            <div class="flex justify-between gap-3 mt-1 pt-1 border-t border-gray-200 dark:border-gray-700 font-medium text-gray-900 dark:text-gray-100">
                                                <span>Total</span><span class="font-mono" x-text="fmt(preview.totalAssets)"></span>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Passiven</p>
                                            <template x-for="line in preview.liabilities" :key="line.id">
                                                <div class="flex justify-between gap-3 text-gray-700 dark:text-gray-300">
                                                    <span class="truncate"><span x-text="line.number + ' ' + line.name"></span><span x-show="line.id == profitAccountId && preview.profit !== 0" class="text-xs text-gray-400" x-text="preview.profit > 0 ? ' inkl. Gewinn' : ' inkl. Verlust'"></span></span>
                                                    <span class="font-mono" x-text="fmt(line.opening)"></span>
                                                </div>
                                            </template>
                                            <p x-show="!preview.liabilities.length" class="text-xs text-gray-400">Keine Saldi</p>
                                            <div class="flex justify-between gap-3 mt-1 pt-1 border-t border-gray-200 dark:border-gray-700 font-medium text-gray-900 dark:text-gray-100">
                                                <span>Total</span><span class="font-mono" x-text="fmt(preview.totalLiabilities)"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-3" x-text="profitText()"></p>
                                    <p x-show="preview.difference !== 0" class="text-xs text-red-600 dark:text-red-400 mt-1" x-text="'Die Eröffnungsbilanz geht um ' + fmt(preview.difference) + ' nicht auf – schon die Eröffnungssaldi von «' + source.name + '» waren nicht ausgeglichen.'"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="'Übernommen werden alle ' + source.accounts.length + ' Konten samt IBAN. Ertrags- und Aufwandkonten starten bei 0, Regeln für den Bankabgleich gelten weiter.'"></p>
                                </div>
                            </template>
                        </div>

                        <label x-show="source && !source.is_closed" class="flex items-start gap-2">
                            <input type="checkbox" name="close_source" value="1" @checked(old('close_source')) class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300"><span x-text="source ? '«' + source.name + '» abschliessen' : ''"></span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Dann lässt sich dort nichts mehr buchen. Kommt doch noch etwas dazu: wieder öffnen, buchen und hier die Schlusssalden neu übernehmen.</span>
                            </span>
                        </label>
                    </fieldset>
                @endif

                <fieldset x-show="mode === 'new'" :disabled="mode !== 'new'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Zuordnung *</label>
                    {{-- Search field for organization or person; the form sends the same two fields as before --}}
                    <input type="hidden" name="accountable_type" :value="party.type">
                    <input type="hidden" name="accountable_id" :value="party.type === 'organization' ? party.organization_id : party.contact_id">
                    @include('admin.partials.contract-party-picker', ['members' => false, 'withContactPerson' => false])
                    <p x-show="ownerError" x-cloak x-text="ownerError" class="text-red-500 text-xs mt-1"></p>
                    @error('accountable_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </fieldset>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                    <input type="text" name="name" id="name" x-model="name" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="period_start" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periode von *</label>
                            <input type="date" name="period_start" id="period_start" x-model="periodStart" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="period_end" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periode bis *</label>
                            <input type="date" name="period_end" id="period_end" x-model="periodEnd" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    @error('period_start') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @error('period_end') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <fieldset x-show="mode === 'new'" :disabled="mode !== 'new'" class="space-y-6">
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
                        <label class="flex items-start gap-2 p-3 rounded-lg border cursor-pointer transition-colors" :class="openingMode === 'zero' ? 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/50' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                            <input type="radio" value="zero" x-model="openingMode" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Vorlage direkt übernehmen</span>
                                <p class="text-xs text-gray-500 mt-0.5">Alle Konten starten bei 0. Du kannst sie danach anpassen.</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-2 p-3 rounded-lg border cursor-pointer transition-colors" :class="openingMode === 'manual' ? 'border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/50' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                            <input type="radio" value="manual" x-model="openingMode" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Kontenplan anpassen</span>
                                <p class="text-xs text-gray-500 mt-0.5">Konten bearbeiten, hinzufügen oder löschen und Eröffnungssaldi festlegen.</p>
                            </div>
                        </label>
                    </div>
                </div>
                </fieldset>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notizen</label>
                    <textarea name="notes" id="notes" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-3">
                <template x-if="mode === 'new' && templateId && openingMode === 'manual'">
                    <button type="button" @click="goToStep2()" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Weiter &rarr;</button>
                </template>
                <template x-if="mode === 'carry' || !templateId || openingMode === 'zero'">
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700" x-text="mode === 'carry' ? 'Folgejahr eröffnen' : 'Buchhaltung erstellen'">Buchhaltung erstellen</button>
                </template>
                <a href="{{ route('admin.accountings.index') }}" class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Abbrechen</a>
            </div>
        </div>

        {{-- Schritt 2: Kontenplan anpassen --}}
        <div x-show="step === 2" x-cloak>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Kontenplan anpassen</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Bearbeite, lösche oder füge Konten hinzu. Eröffnungssaldi nur für Aktiv- und Passivkonten.</p>
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
                                <tr :class="account.is_header ? 'bg-gray-50 dark:bg-gray-700/50' : ''">
                                    <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100" :class="account.is_header ? 'font-bold' : ''">
                                        {{-- Hidden inputs im ersten td (ausserhalb td = ungültiges HTML) --}}
                                        <input type="hidden" :name="'custom_accounts[' + index + '][number]'" :value="account.number">
                                        <input type="hidden" :name="'custom_accounts[' + index + '][name]'" :value="account.name">
                                        <input type="hidden" :name="'custom_accounts[' + index + '][type]'" :value="account.type">
                                        <input type="hidden" :name="'custom_accounts[' + index + '][is_header]'" :value="account.is_header ? '1' : '0'">
                                        <input type="hidden" :name="'custom_accounts[' + index + '][opening_balance]'" :value="account.opening_balance || 0">
                                        <span x-text="account.number"></span>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100" :class="account.is_header ? 'font-bold' : ''">
                                        <span x-text="account.name"></span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-gray-100">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                            :class="typeColors[account.type]"
                                            x-text="typeLabels[account.type]"></span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <template x-if="!account.is_header && (account.type === 'asset' || account.type === 'liability')">
                                            <input type="number" step="0.01"
                                                x-model.number="account.opening_balance"
                                                class="w-full text-right rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                                        </template>
                                        <template x-if="!account.is_header && account.type !== 'asset' && account.type !== 'liability'">
                                            <span class="text-sm text-gray-400 dark:text-gray-500 font-mono">0.00</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <template x-if="!account.is_header">
                                            <button type="button" @click="removeAccount(index)" class="text-red-400 hover:text-red-600 dark:hover:text-red-300" title="Konto entfernen">
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
                <div class="px-4 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Konto hinzufügen</p>
                    <div class="flex flex-wrap items-end gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Nr.</label>
                            <input type="text" x-model="newAccount.number" placeholder="1000" class="w-20 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="flex-1 min-w-[120px]">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Bezeichnung</label>
                            <input type="text" x-model="newAccount.name" placeholder="Kasse" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Typ</label>
                            <select x-model="newAccount.type" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="asset">Aktiven</option>
                                <option value="liability">Passiven</option>
                                <option value="income">Ertrag</option>
                                <option value="expense">Aufwand</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">Eröffnungssaldo</label>
                            <input type="number" step="0.01" x-model.number="newAccount.opening_balance" placeholder="0.00" class="w-28 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-right">
                        </div>
                        <button type="button" @click="addAccount()" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">+</button>
                    </div>
                    <p x-show="addError" x-text="addError" class="text-red-500 dark:text-red-400 text-xs mt-1"></p>
                </div>
            </div>

            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                <span x-text="accounts.filter(a => !a.is_header).length"></span> Konten
            </div>

            <div class="mt-3 flex items-center gap-3">
                <button type="button" @click="step = 1" class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">&larr; Zurück</button>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Buchhaltung erstellen</button>
            </div>
        </div>
    </form>
</div>

@include('admin.partials.contract-party-picker-script', ['organizations' => $organizations, 'contacts' => $contacts])

<script>
function accountingWizard() {
    return {
        step: 1,
        mode: @js(old('source_accounting_id') || $fromId ? 'carry' : 'new'),
        sources: @json($sourcesJson),
        sourceId: @js((string) old('source_accounting_id', $fromId ?? '')),
        profitAccountId: @js((string) old('profit_account_id', '')),
        name: @js(old('name', 'Buchhaltung ' . $year)),
        periodStart: @js(old('period_start', $year . '-01-01')),
        periodEnd: @js(old('period_end', $year . '-12-31')),
        // The owner, as the party search keeps it
        party: {
            type: @js(old('accountable_type') === 'contact' ? 'contact' : 'organization'),
            organization_id: @js(old('accountable_type', 'organization') === 'organization' ? (string) old('accountable_id', '') : ''),
            contact_id: @js(old('accountable_type') === 'contact' ? (string) old('accountable_id', '') : ''),
        },
        ownerError: '',
        templateId: '{{ old('chart_template_id', '') }}',
        openingMode: 'zero',

        init() {
            // Coming from "Folgejahr eröffnen": fill in the next year right away
            if (this.mode === 'carry' && @js(!old('source_accounting_id'))) this.applySource();
            // (Alpine watches objects deeply)
            this.$watch('party', () => { this.ownerError = ''; });
        },

        get source() {
            return this.sources.find(s => String(s.id) === String(this.sourceId)) || null;
        },

        get liabilityAccounts() {
            return this.source ? this.source.accounts.filter(a => a.type === 'liability') : [];
        },

        /** The next year's name, period and equity account, from the chosen year. */
        applySource() {
            const s = this.source;
            if (!s) return;
            const shift = (iso, years, days) => {
                const [y, m, d] = iso.split('-').map(Number);
                return new Date(Date.UTC(y + years, m - 1, d + days)).toISOString().slice(0, 10);
            };
            this.periodStart = shift(s.period_end, 0, 1);
            this.periodEnd = shift(s.period_end, 1, 0);
            // "Buchhaltung 2025" -> "Buchhaltung 2026", "Saison 2025/2026" -> "Saison 2026/2027"
            this.name = /\b(19|20)\d{2}\b/.test(s.name)
                ? s.name.replace(/\b(19|20)\d{2}\b/g, y => String(Number(y) + 1))
                : s.name + ' ' + this.periodEnd.slice(0, 4);
            this.profitAccountId = s.profit_account_id ? String(s.profit_account_id) : '';
        },

        /** Closing balances of the chosen year as opening balances, its profit on the equity account. */
        get preview() {
            const s = this.source;
            if (!s) return null;
            const round = v => Math.round(v * 100) / 100;
            let profit = 0;
            s.accounts.forEach(a => {
                if (a.type === 'income') profit += a.balance;
                if (a.type === 'expense') profit -= a.balance;
            });
            profit = round(profit);
            const lines = s.accounts.filter(a => a.type === 'asset' || a.type === 'liability')
                .map(a => Object.assign({}, a, { opening: round(a.balance + (String(a.id) === String(this.profitAccountId) ? profit : 0)) }));
            const total = type => round(lines.filter(l => l.type === type).reduce((sum, l) => sum + l.opening, 0));

            return {
                profit,
                assets: lines.filter(l => l.type === 'asset' && l.opening !== 0),
                liabilities: lines.filter(l => l.type === 'liability' && l.opening !== 0),
                totalAssets: total('asset'),
                totalLiabilities: total('liability'),
                difference: round(total('asset') - total('liability')),
            };
        },

        profitText() {
            const p = this.preview;
            if (!p) return '';
            if (p.profit === 0) return 'Das Vorjahr schliesst ohne Gewinn oder Verlust.';
            const account = this.liabilityAccounts.find(a => String(a.id) === String(this.profitAccountId));
            return (p.profit > 0 ? 'Gewinn' : 'Verlust') + ' von «' + this.source.name + '»: ' + this.fmt(Math.abs(p.profit)) + ' ' + this.source.currency
                + (account ? ' → ' + account.number + ' ' + account.name : ' – bitte ein Eigenkapitalkonto wählen');
        },

        fmt(value) {
            const n = Math.round(value * 100) / 100;
            const [int, dec] = Math.abs(n).toFixed(2).split('.');
            return (n < 0 ? '-' : '') + int.replace(/\B(?=(\d{3})+(?!\d))/g, "'") + '.' + dec;
        },

        dateLabel(iso) {
            if (!iso) return '';
            const [y, m, d] = iso.split('-');
            return d + '.' + m + '.' + y;
        },
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
            const ownerId = this.party.type === 'organization' ? this.party.organization_id : this.party.contact_id;
            if (this.mode === 'new' && !ownerId) {
                this.step = 1;
                this.ownerError = 'Bitte eine Organisation oder Person wählen.';
                return;
            }
            event.target.submit();
        }
    }
}
</script>
@endsection
