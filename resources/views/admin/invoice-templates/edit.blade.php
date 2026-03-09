@extends('admin.layouts.app')

@section('title', 'Rechnungsvorlage bearbeiten')

@section('content')
<div class="max-w-3xl" x-data="templateForm()">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Rechnungsvorlage bearbeiten</h2>
        <p class="text-sm text-gray-500 mt-1">{{ $template->name }}</p>
    </div>

    <form method="POST" action="{{ route('admin.invoice-templates.update', $template) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Vorlagenname *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $template->name) }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Absender --}}
            <div class="border-t border-gray-200 pt-6">
                <p class="text-sm font-medium text-gray-700 mb-3">Absender</p>
                <input type="hidden" name="sender_type" :value="senderType">
                <input type="hidden" name="contact_id" :value="senderContactId">
                <div class="flex gap-4 mb-4">
                    <label class="inline-flex items-center">
                        <input type="radio" x-model="senderType" value="" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700">Keiner</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" x-model="senderType" value="organization" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700">Organisation</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" x-model="senderType" value="contact" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700">Kontakt</span>
                    </label>
                </div>

                <div x-show="senderType === 'organization'" class="space-y-3">
                    <select name="organization_id" x-model="senderOrganizationId" @change="onSenderOrgChange()" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Organisation wählen —</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->primary_name }}</option>
                        @endforeach
                    </select>

                    <div x-show="senderOrgContacts.length > 0">
                        <label class="block text-sm text-gray-600 mb-1">Ansprechperson (optional)</label>
                        <select x-model="senderContactId" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— Keine Person —</option>
                            <template x-for="c in senderOrgContacts" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div x-show="senderType === 'contact'">
                    <select x-model="senderContactId" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Kontakt wählen —</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-xs text-gray-400 mt-2">Adresse, Bankverbindung und UID/MWST-Nr. werden vom gewählten Kontakt bzw. der Organisation übernommen.</p>
            </div>

            {{-- Empfänger --}}
            <div class="border-t border-gray-200 pt-6">
                <p class="text-sm font-medium text-gray-700 mb-3">Standard-Empfänger</p>
                <input type="hidden" name="recipient_type" :value="recipientType">
                <input type="hidden" name="recipient_contact_id" :value="recipientContactId">
                <div class="flex gap-4 mb-4">
                    <label class="inline-flex items-center">
                        <input type="radio" x-model="recipientType" value="" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700">Keiner</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" x-model="recipientType" value="organization" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700">Organisation</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" x-model="recipientType" value="contact" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700">Kontakt</span>
                    </label>
                </div>

                <div x-show="recipientType === 'organization'" class="space-y-3">
                    <select name="recipient_organization_id" x-model="recipientOrganizationId" @change="onRecipientOrgChange()" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Organisation wählen —</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->primary_name }}</option>
                        @endforeach
                    </select>

                    <div x-show="recipientOrgContacts.length > 0">
                        <label class="block text-sm text-gray-600 mb-1">Ansprechperson (optional)</label>
                        <select x-model="recipientContactId" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— Keine Person —</option>
                            <template x-for="c in recipientOrgContacts" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div x-show="recipientType === 'contact'">
                    <select x-model="recipientContactId" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Kontakt wählen —</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-xs text-gray-400 mt-2">Wird beim Erstellen einer Rechnung mit dieser Vorlage vorausgefüllt.</p>
            </div>

            {{-- MWST + Zahlungsfrist --}}
            <div class="border-t border-gray-200 pt-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="vat_rate" class="block text-sm font-medium text-gray-700 mb-1">MWST-Satz (%)</label>
                        <input type="number" name="vat_rate" id="vat_rate" value="{{ old('vat_rate', $template->vat_rate) }}" step="0.01" min="0" max="100" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="z.B. 8.10">
                        <p class="text-xs text-gray-400 mt-1">Leer lassen = ohne MWST</p>
                    </div>
                    <div>
                        <label for="payment_terms_days" class="block text-sm font-medium text-gray-700 mb-1">Zahlungsfrist (Tage) *</label>
                        <input type="number" name="payment_terms_days" id="payment_terms_days" value="{{ old('payment_terms_days', $template->payment_terms_days) }}" required min="0" max="365" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            {{-- Standardpositionen --}}
            <div class="border-t border-gray-200 pt-6">
                <div class="flex justify-between items-center mb-3">
                    <p class="text-sm font-medium text-gray-700">Standardpositionen</p>
                    <button type="button" @click="addItem()" class="text-xs text-blue-600 hover:text-blue-800">+ Position hinzufügen</button>
                </div>
                <p class="text-xs text-gray-400 mb-3">Diese Positionen werden beim Erstellen einer Rechnung mit dieser Vorlage vorausgefüllt.</p>

                <template x-if="items.length > 0">
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
                                    <input type="text" :name="'template_items['+index+'][description]'" x-model="item.description" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="z.B. Porto & Verpackung">
                                </div>
                                <div class="col-span-2">
                                    <input type="number" :name="'template_items['+index+'][quantity]'" x-model="item.quantity" step="0.001" min="0.001" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div class="col-span-2">
                                    <input type="number" :name="'template_items['+index+'][unit_price]'" x-model="item.unit_price" step="0.01" min="0" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div class="col-span-1 text-right text-sm font-mono text-gray-700" x-text="(item.quantity * item.unit_price).toFixed(2)"></div>
                                <div class="col-span-1 text-right">
                                    <button type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Logo --}}
            <div class="border-t border-gray-200 pt-6" x-data="{ logoSource: '{{ old('logo_source', $template->use_avatar_as_logo ? 'avatar' : 'custom') }}' }">
                <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
                <div class="flex gap-4 mb-3">
                    <label class="inline-flex items-center">
                        <input type="radio" name="logo_source" value="avatar" x-model="logoSource" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700">Avatar verwenden</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="logo_source" value="custom" x-model="logoSource" class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-1.5 text-sm text-gray-700">Eigenes Logo hochladen</span>
                    </label>
                </div>
                <div x-show="logoSource === 'avatar'">
                    <p class="text-xs text-gray-400">Das Avatar-Bild des gewählten Kontakts bzw. der Organisation wird als Logo verwendet.</p>
                </div>
                <div x-show="logoSource === 'custom'">
                    @if($template->logo_path)
                        <div class="flex items-center gap-3 mb-2">
                            <img src="{{ Storage::url($template->logo_path) }}" alt="Logo" class="h-10 w-auto border rounded">
                        </div>
                    @endif
                    <input type="file" name="logo" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
            </div>

            {{-- Fusszeile --}}
            <div class="border-t border-gray-200 pt-6">
                <label for="footer_text" class="block text-xs text-gray-500 mb-1">Fusszeile</label>
                <textarea name="footer_text" id="footer_text" rows="2" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('footer_text', $template->footer_text) }}</textarea>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Speichern</button>
            <a href="{{ route('admin.invoice-templates.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Abbrechen</a>
        </div>
    </form>

    @if($template->usage_count === 0)
        <div class="mt-8 bg-red-50 rounded-xl border border-red-200 p-6">
            <h3 class="text-sm font-medium text-red-800 mb-2">Vorlage löschen</h3>
            <form method="POST" action="{{ route('admin.invoice-templates.destroy', $template) }}" onsubmit="return confirm('Vorlage wirklich löschen?')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">Löschen</button>
            </form>
        </div>
    @endif
</div>

@php
    $senderTypeDefault = $template->organization_id ? 'organization' : ($template->contact_id ? 'contact' : '');
    $recipientTypeDefault = $template->recipient_organization_id ? 'organization' : ($template->recipient_contact_id ? 'contact' : '');
@endphp

<script>
function templateForm() {
    return {
        items: @json(old('template_items', $templateItems)),
        orgContactsMap: @json($orgContactsMap),
        senderType: @json(old('sender_type', $senderTypeDefault)),
        senderContactId: @json(old('contact_id', $template->contact_id ?? '')),
        senderOrganizationId: @json(old('organization_id', $template->organization_id ?? '')),
        senderOrgContacts: [],
        recipientType: @json(old('recipient_type', $recipientTypeDefault)),
        recipientContactId: @json(old('recipient_contact_id', $template->recipient_contact_id ?? '')),
        recipientOrganizationId: @json(old('recipient_organization_id', $template->recipient_organization_id ?? '')),
        recipientOrgContacts: [],
        init() {
            const savedSenderContactId = this.senderContactId;
            const savedRecipientContactId = this.recipientContactId;
            const sOrgId = this.senderOrganizationId;
            this.senderOrgContacts = (sOrgId && this.orgContactsMap[sOrgId]) ? this.orgContactsMap[sOrgId] : [];
            const rOrgId = this.recipientOrganizationId;
            this.recipientOrgContacts = (rOrgId && this.orgContactsMap[rOrgId]) ? this.orgContactsMap[rOrgId] : [];
            this.$nextTick(() => {
                this.senderContactId = savedSenderContactId;
                this.recipientContactId = savedRecipientContactId;
            });
        },
        onSenderOrgChange() {
            const orgId = this.senderOrganizationId;
            this.senderOrgContacts = (orgId && this.orgContactsMap[orgId]) ? this.orgContactsMap[orgId] : [];
            const ids = this.senderOrgContacts.map(c => String(c.id));
            if (!ids.includes(String(this.senderContactId))) this.senderContactId = '';
        },
        onRecipientOrgChange() {
            const orgId = this.recipientOrganizationId;
            this.recipientOrgContacts = (orgId && this.orgContactsMap[orgId]) ? this.orgContactsMap[orgId] : [];
            const ids = this.recipientOrgContacts.map(c => String(c.id));
            if (!ids.includes(String(this.recipientContactId))) this.recipientContactId = '';
        },
        addItem() {
            this.items.push({ description: '', quantity: 1, unit_price: 0 });
        },
        removeItem(index) {
            this.items.splice(index, 1);
        }
    }
}
</script>
@endsection
