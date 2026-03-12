@extends('admin.layouts.app')

@section('title', 'Neue Rechnung')

@section('content')
<div class="max-w-3xl" x-data="invoiceForm()">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Neue Rechnung</h2>
        <p class="text-sm text-gray-500 mt-1">Nr. {{ $nextInvoiceNumber }} (wird automatisch vergeben)</p>
    </div>

    <form method="POST" action="{{ route('admin.invoices.store') }}" @submit="submitting = true">
        @csrf

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            {{-- Titel --}}
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titel</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="z.B. Webdesign März 2026">
            </div>

            {{-- Vorlage, Buchhaltung, Projekt --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="invoice_template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rechnungsvorlage</label>
                    <select name="invoice_template_id" id="invoice_template_id" @change="onTemplateChange($event)" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Keine Vorlage —</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}" {{ old('invoice_template_id') == $tpl->id ? 'selected' : '' }}>{{ $tpl->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="accounting_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buchhaltung</label>
                    <select name="accounting_id" id="accounting_id" x-model="accountingId" @change="onAccountingChange()" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Keine —</option>
                        @foreach($accountings as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="project_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Projekt</label>
                    <select name="project_id" id="project_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Kein Projekt —</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Soll-/Haben-Konto (wenn Buchhaltung gewählt) --}}
            <div x-show="accountingId && accounts.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div x-data="{ search: '', open: false }" @click.outside="open = false" class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Soll-Konto *
                        <span x-data="{ show: false }" class="relative inline-block ml-1">
                            <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-gray-500 text-xs hover:bg-gray-300 focus:outline-none">?</button>
                            <div x-show="show" x-transition class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg">
                                Bei ausgehenden Rechnungen z.B. Debitoren (1100). Bei eingehenden z.B. Aufwandkonto.
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                            </div>
                        </span>
                    </label>
                    <input type="text" x-model="search" @focus="open = true" @input="open = true"
                        :placeholder="debitAccountId ? accounts.find(a => String(a.id) === String(debitAccountId))?.number + ' ' + accounts.find(a => String(a.id) === String(debitAccountId))?.name : 'Konto suchen...'"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <input type="hidden" name="debit_account_id" :value="debitAccountId">
                    <div x-show="open" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                        <template x-for="acc in accounts.filter(a => !search || (a.number + ' ' + a.name).toLowerCase().includes(search.toLowerCase()))" :key="acc.id">
                            <button type="button" @click="debitAccountId = acc.id; search = ''; open = false"
                                class="w-full text-left px-3 py-1.5 text-sm hover:bg-blue-50 flex justify-between"
                                :class="String(acc.id) === String(debitAccountId) ? 'bg-blue-50 font-medium' : ''">
                                <span x-text="acc.number + ' ' + acc.name"></span>
                                <span class="text-xs text-gray-400" x-text="acc.type"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <div x-data="{ search: '', open: false }" @click.outside="open = false" class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Haben-Konto *
                        <span x-data="{ show: false }" class="relative inline-block ml-1">
                            <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 text-gray-500 text-xs hover:bg-gray-300 focus:outline-none">?</button>
                            <div x-show="show" x-transition class="absolute z-20 bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg">
                                Bei ausgehenden Rechnungen z.B. Ertragskonto (3xxx). Bei eingehenden z.B. Kreditoren (2001).
                                <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                            </div>
                        </span>
                    </label>
                    <input type="text" x-model="search" @focus="open = true" @input="open = true"
                        :placeholder="creditAccountId ? accounts.find(a => String(a.id) === String(creditAccountId))?.number + ' ' + accounts.find(a => String(a.id) === String(creditAccountId))?.name : 'Konto suchen...'"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <input type="hidden" name="credit_account_id" :value="creditAccountId">
                    <div x-show="open" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                        <template x-for="acc in accounts.filter(a => !search || (a.number + ' ' + a.name).toLowerCase().includes(search.toLowerCase()))" :key="acc.id">
                            <button type="button" @click="creditAccountId = acc.id; search = ''; open = false"
                                class="w-full text-left px-3 py-1.5 text-sm hover:bg-blue-50 flex justify-between"
                                :class="String(acc.id) === String(creditAccountId) ? 'bg-blue-50 font-medium' : ''">
                                <span x-text="acc.number + ' ' + acc.name"></span>
                                <span class="text-xs text-gray-400" x-text="acc.type"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Typ, Währung --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Typ *</label>
                    <select name="type" id="type" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(['outgoing' => 'Ausgehend', 'incoming' => 'Eingehend'] as $value => $label)
                            <option value="{{ $value }}" {{ old('type', 'outgoing') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Währung *</label>
                    <select name="currency" id="currency" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(['CHF' => 'CHF', 'EUR' => 'EUR', 'USD' => 'USD'] as $value => $label)
                            <option value="{{ $value }}" {{ old('currency', 'CHF') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Absender --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Absender</p>
                <input type="hidden" name="sender_type" :value="senderType">
                <input type="hidden" name="sender_contact_id" :value="senderContactId">
                <div class="flex gap-4 mb-4">
                    <label class="inline-flex items-center">
                        <input type="radio" x-model="senderType" value="" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">Kein Absender</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" x-model="senderType" value="contact" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">Kontakt</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" x-model="senderType" value="organization" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">Organisation</span>
                    </label>
                </div>

                <div x-show="senderType === 'contact'">
                    <select x-model="senderContactId" :disabled="senderType !== 'contact'" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Kontakt wählen —</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="senderType === 'organization'" class="space-y-3">
                    <select name="sender_organization_id" x-model="senderOrganizationId" :disabled="senderType !== 'organization'" @change="onSenderOrgChange()" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Organisation wählen —</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->primary_name }}</option>
                        @endforeach
                    </select>

                    <div x-show="senderOrgContacts.length > 0">
                        <label class="block text-sm text-gray-600 mb-1">Ansprechperson (optional)</label>
                        <select x-model="senderContactId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— Keine Person —</option>
                            <template x-for="c in senderOrgContacts" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Empfänger --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Empfänger *</p>
                @error('contact_id') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror
                <input type="hidden" name="recipient_type" :value="recipientType">
                <input type="hidden" name="contact_id" :value="recipientContactId">
                <div class="flex gap-4 mb-4">
                    <label class="inline-flex items-center">
                        <input type="radio" x-model="recipientType" value="contact" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">Kontakt</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" x-model="recipientType" value="organization" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">Organisation</span>
                    </label>
                </div>

                <div x-show="recipientType === 'contact'">
                    <select x-model="recipientContactId" :disabled="recipientType !== 'contact'" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Kontakt wählen —</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="recipientType === 'organization'" class="space-y-3">
                    <select name="organization_id" x-model="recipientOrganizationId" :disabled="recipientType !== 'organization'" @change="onRecipientOrgChange()" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Organisation wählen —</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->primary_name }}</option>
                        @endforeach
                    </select>

                    <div x-show="recipientOrgContacts.length > 0">
                        <label class="block text-sm text-gray-600 mb-1">Ansprechperson (optional)</label>
                        <select x-model="recipientContactId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— Keine Person —</option>
                            <template x-for="c in recipientOrgContacts" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Daten --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="invoice_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rechnungsdatum *</label>
                        <input type="date" name="invoice_date" id="invoice_date" value="{{ old('invoice_date', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('invoice_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fälligkeitsdatum</label>
                        <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('due_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select name="status" id="status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach(['open' => 'Offen', 'paid' => 'Bezahlt', 'overdue' => 'Überfällig'] as $value => $label)
                                <option value="{{ $value }}" {{ old('status', 'open') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- MWST --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="w-48">
                    <label for="vat_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">MWST-Satz (%)</label>
                    <input type="number" name="vat_rate" id="vat_rate" x-model="vatRate" step="0.01" min="0" max="100" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="z.B. 8.10">
                    <p class="text-xs text-gray-400 mt-1">Leer lassen = ohne MWST</p>
                </div>
            </div>

            {{-- Positionen --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex justify-between items-center mb-3">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Positionen *</p>
                    <button type="button" @click="addItem()" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Position hinzufügen</button>
                </div>
                @error('items') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

                <div class="space-y-2">
                    <div class="grid grid-cols-12 gap-2 text-xs text-gray-500 font-medium px-1">
                        <div class="col-span-6">Beschreibung</div>
                        <div class="col-span-2">Menge</div>
                        <div class="col-span-2">Einzelpreis</div>
                        <div class="col-span-1 text-right">Total</div>
                        <div class="col-span-1"></div>
                    </div>

                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid grid-cols-12 gap-2 items-center">
                            <div class="col-span-6">
                                <input type="text" :name="'items['+index+'][description]'" x-model="item.description" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Beschreibung">
                            </div>
                            <div class="col-span-2">
                                <input type="number" :name="'items['+index+'][quantity]'" x-model="item.quantity" step="0.001" min="0.001" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div class="col-span-2">
                                <input type="number" :name="'items['+index+'][unit_price]'" x-model="item.unit_price" step="0.01" min="0" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div class="col-span-1 text-right text-sm font-mono text-gray-700 dark:text-gray-300" x-text="(item.quantity * item.unit_price).toFixed(2)"></div>
                            <div class="col-span-1 text-right">
                                <button type="button" @click="removeItem(index)" x-show="items.length > 1" class="text-red-400 hover:text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end mt-3 pt-3 border-t border-gray-100 dark:border-gray-700/50">
                    <div class="text-sm text-gray-700 space-y-1 text-right">
                        <div>Zwischensumme: <span class="font-mono" x-text="subtotal().toFixed(2)"></span></div>
                        <template x-if="vatRate > 0">
                            <div>MWST <span x-text="vatRate"></span>%: <span class="font-mono" x-text="vatAmount().toFixed(2)"></span></div>
                        </template>
                        <div class="font-medium text-gray-900 dark:text-gray-100">Total: <span class="font-mono font-bold" x-text="total().toFixed(2)"></span></div>
                    </div>
                </div>
            </div>

            {{-- Notizen --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notizen</label>
                <textarea name="notes" id="notes" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" :disabled="submitting" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
                <svg x-show="submitting" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <span x-text="submitting ? 'Wird gespeichert…' : 'Rechnung erstellen'"></span>
            </button>
            <a href="{{ route('admin.invoices.index') }}" x-show="!submitting" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>
</div>

<script>
function invoiceForm() {
    return {
        submitting: false,
        items: @json(old('items', $defaultItems)),
        vatRate: @json(old('vat_rate', '')),
        accountingId: @json(old('accounting_id', '')),
        accounts: [],
        debitAccountId: @json(old('debit_account_id', '')),
        creditAccountId: @json(old('credit_account_id', '')),
        senderType: @json(old('sender_type', '')),
        senderContactId: @json(old('sender_contact_id', '')),
        senderOrganizationId: @json(old('sender_organization_id', '')),
        orgContactsMap: @json($orgContactsMap),
        senderOrgContacts: [],
        recipientType: @json(old('recipient_type', old('organization_id') ? 'organization' : 'contact')),
        recipientContactId: @json(old('contact_id', '')),
        recipientOrganizationId: @json(old('organization_id', '')),
        recipientOrgContacts: [],
        init() {
            this.onSenderOrgChange();
            this.onRecipientOrgChange();
            if (this.accountingId) this.onAccountingChange();
        },
        onSenderOrgChange() {
            const orgId = this.senderOrganizationId;
            if (orgId && this.orgContactsMap[orgId]) {
                this.senderOrgContacts = this.orgContactsMap[orgId];
            } else {
                this.senderOrgContacts = [];
            }
            const ids = this.senderOrgContacts.map(c => String(c.id));
            if (!ids.includes(String(this.senderContactId))) {
                this.senderContactId = '';
            }
        },
        onRecipientOrgChange() {
            const orgId = this.recipientOrganizationId;
            if (orgId && this.orgContactsMap[orgId]) {
                this.recipientOrgContacts = this.orgContactsMap[orgId];
            } else {
                this.recipientOrgContacts = [];
            }
            const ids = this.recipientOrgContacts.map(c => String(c.id));
            if (!ids.includes(String(this.recipientContactId))) {
                this.recipientContactId = '';
            }
        },
        async onAccountingChange() {
            this.accounts = [];
            this.debitAccountId = '';
            this.creditAccountId = '';
            if (!this.accountingId) return;
            try {
                const resp = await fetch(`/admin/accountings/${this.accountingId}/accounts`);
                this.accounts = await resp.json();
            } catch (err) {
                console.error('Konten konnten nicht geladen werden', err);
            }
        },
        addItem() {
            this.items.push({ description: '', quantity: 1, unit_price: 0 });
        },
        removeItem(index) {
            this.items.splice(index, 1);
        },
        subtotal() {
            return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0), 0);
        },
        vatAmount() {
            const rate = parseFloat(this.vatRate) || 0;
            return rate > 0 ? Math.round(this.subtotal() * rate / 100 * 100) / 100 : 0;
        },
        total() {
            return this.subtotal() + this.vatAmount();
        },
        async onTemplateChange(e) {
            const id = e.target.value;
            if (!id) return;
            try {
                const resp = await fetch(`/admin/invoice-templates/${id}/data`);
                const data = await resp.json();
                if (data.payment_terms_days) {
                    const invoiceDate = document.getElementById('invoice_date').value;
                    if (invoiceDate) {
                        const d = new Date(invoiceDate);
                        d.setDate(d.getDate() + parseInt(data.payment_terms_days));
                        document.getElementById('due_date').value = d.toISOString().split('T')[0];
                    }
                }
                if (data.vat_rate !== null && data.vat_rate !== undefined) {
                    this.vatRate = data.vat_rate;
                }
                if (data.items && data.items.length > 0) {
                    this.items = data.items.map(i => ({ description: i.description, quantity: i.quantity, unit_price: i.unit_price }));
                }
                // Prefill sender from template
                if (data.sender_organization_id) {
                    this.senderType = 'organization';
                    this.senderOrganizationId = String(data.sender_organization_id);
                    this.onSenderOrgChange();
                    this.$nextTick(() => {
                        this.senderContactId = data.sender_contact_id ? String(data.sender_contact_id) : '';
                    });
                } else if (data.sender_contact_id) {
                    this.senderType = 'contact';
                    this.senderContactId = String(data.sender_contact_id);
                    this.senderOrganizationId = '';
                }
                // Prefill recipient from template
                if (data.recipient_organization_id) {
                    this.recipientType = 'organization';
                    this.recipientOrganizationId = String(data.recipient_organization_id);
                    this.onRecipientOrgChange();
                    this.$nextTick(() => {
                        this.recipientContactId = data.recipient_contact_id ? String(data.recipient_contact_id) : '';
                    });
                } else if (data.recipient_contact_id) {
                    this.recipientType = 'contact';
                    this.recipientContactId = String(data.recipient_contact_id);
                    this.recipientOrganizationId = '';
                }
            } catch (err) {
                console.error('Template-Daten konnten nicht geladen werden', err);
            }
        }
    }
}
</script>
@endsection
