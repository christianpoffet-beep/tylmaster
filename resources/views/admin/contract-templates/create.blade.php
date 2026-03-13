@extends('admin.layouts.app')

@section('title', 'Vertragsvorlage erstellen')

@php
    $defaultParties = old('parties', [
        ['type' => 'organization', 'organization_id' => '', 'contact_id' => '', 'share' => 50],
        ['type' => 'organization', 'organization_id' => '', 'contact_id' => '', 'share' => 50],
    ]);
@endphp

@section('content')
<div class="max-w-3xl" x-data="templateForm()">
    <form method="POST" action="{{ route('admin.contract-templates.store') }}">
        @csrf
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="z.B. Publishing Standard, Label Exclusive..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="contract_type_slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vertragstyp *</label>
                    <select name="contract_type_slug" id="contract_type_slug" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($contractTypes as $ct)
                            <option value="{{ $ct->slug }}" {{ old('contract_type_slug') === $ct->slug ? 'selected' : '' }}>{{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="default_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Standard-Status</label>
                    <select name="default_status" id="default_status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Kein Standard —</option>
                        @foreach(['draft' => 'Entwurf', 'active' => 'Aktiv', 'expired' => 'Ausgelaufen', 'terminated' => 'Gekündigt'] as $v => $l)
                            <option value="{{ $v }}" {{ old('default_status') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reihenfolge</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="w-24 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            {{-- Standard-Parteien --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex justify-between items-center mb-3">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Standard-Parteien <span class="text-gray-400 font-normal">(optional)</span></p>
                    <button type="button" @click="addParty()" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Partei hinzufügen</button>
                </div>
                <p class="text-xs text-gray-400 mb-3">Diese Parteien werden beim Erstellen eines neuen Vertrags vorausgefüllt.</p>

                <template x-for="(party, index) in parties" :key="index">
                    <div class="border border-gray-200 rounded-lg p-4 mb-3">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400" x-text="'Partei ' + (index + 1)"></span>
                            <button type="button" @click="removeParty(index)" class="text-red-400 hover:text-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <input type="hidden" :name="'parties['+index+'][type]'" :value="party.type">
                        <input type="hidden" :name="'parties['+index+'][organization_id]'" :value="party.type === 'organization' ? party.organization_id : ''">
                        <input type="hidden" :name="'parties['+index+'][contact_id]'" :value="party.contact_id">

                        <div class="flex gap-4 mb-3">
                            <label class="inline-flex items-center">
                                <input type="radio" :checked="party.type === 'organization'" @click="party.type = 'organization'; party.contact_id = ''" class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">Organisation</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" :checked="party.type === 'contact'" @click="party.type = 'contact'; party.organization_id = ''; party.contact_id = ''" class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">Kontakt</span>
                            </label>
                        </div>

                        <div x-show="party.type === 'organization'" class="space-y-2">
                            <select x-model="party.organization_id" @change="onPartyOrgChange(index)" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">— Organisation wählen —</option>
                                @foreach($organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->primary_name }}</option>
                                @endforeach
                            </select>
                            <div x-show="getOrgContacts(party.organization_id).length > 0">
                                <label class="block text-xs text-gray-500 mb-1">Ansprechperson (optional)</label>
                                <select x-model="party.contact_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">— Keine Person —</option>
                                    <template x-for="c in getOrgContacts(party.organization_id)" :key="c.id">
                                        <option :value="c.id" x-text="c.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div x-show="party.type === 'contact'">
                            <select x-model="party.contact_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">— Kontakt wählen —</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}">{{ $contact->full_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-3">
                            <label class="block text-xs text-gray-500 mb-1">Genereller Anteil (%)</label>
                            <input type="number" :name="'parties['+index+'][share]'" x-model="party.share" @input="balanceShare(index)" step="0.01" min="0" max="100" required class="w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </template>

                <div x-show="parties.length > 0" class="flex items-center justify-between text-sm mt-2 px-1">
                    <span class="text-gray-500 dark:text-gray-400">Total:</span>
                    <span :class="Math.abs(totalShare - 100) < 0.01 ? 'text-green-600 font-medium' : 'text-red-600 font-medium'" x-text="totalShare.toFixed(2) + '%'"></span>
                </div>
            </div>

            @include('admin.partials.rights-editor', [
                'rightsLabelA' => old('rights_label_a', ''),
                'rightsLabelB' => old('rights_label_b', ''),
                'rightsData' => old('rights', []),
            ])

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <label for="default_terms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Standard-Bedingungen / Vertragstext</label>
                <textarea name="default_terms" id="default_terms" rows="12" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono" placeholder="Vertragstext, der beim Erstellen eines neuen Vertrags vorausgefüllt wird...">{{ old('default_terms') }}</textarea>
                <p class="text-xs text-gray-400 mt-1">Dieser Text wird beim Erstellen eines neuen Vertrags in das Feld «Bedingungen / Notizen» übernommen.</p>
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Vorlage erstellen</button>
            <a href="{{ route('admin.contract-templates.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>
</div>

<script>
function templateForm() {
    return {
        orgContactsMap: @json($orgContactsMap),
        orgNames: @json($organizations->pluck('primary_name', 'id')),
        contactNames: @json($contacts->mapWithKeys(fn($c) => [$c->id => $c->full_name])),
        parties: @json($defaultParties),
        get totalShare() {
            return this.parties.reduce((sum, p) => sum + (parseFloat(p.share) || 0), 0);
        },
        init() {
            this.$watch('parties', () => this.dispatchPartyNames());
            this.$nextTick(() => this.dispatchPartyNames());
        },
        dispatchPartyNames() {
            const names = this.parties.slice(0, 2).map(p => {
                if (p.type === 'organization' && p.organization_id) {
                    return this.orgNames[p.organization_id] || '';
                } else if (p.type === 'contact' && p.contact_id) {
                    return this.contactNames[p.contact_id] || '';
                }
                return '';
            });
            window.dispatchEvent(new CustomEvent('party-names-updated', {
                detail: { party1: names[0] || '', party2: names[1] || '' }
            }));
        },
        getOrgContacts(orgId) {
            return (orgId && this.orgContactsMap[orgId]) ? this.orgContactsMap[orgId] : [];
        },
        onPartyOrgChange(index) {
            const orgId = this.parties[index].organization_id;
            const contacts = this.getOrgContacts(orgId);
            const ids = contacts.map(c => String(c.id));
            if (!ids.includes(String(this.parties[index].contact_id))) {
                this.parties[index].contact_id = '';
            }
        },
        balanceShare(changedIndex) {
            if (this.parties.length === 2) {
                const otherIndex = changedIndex === 0 ? 1 : 0;
                this.parties[otherIndex].share = Math.max(0, parseFloat((100 - (parseFloat(this.parties[changedIndex].share) || 0)).toFixed(2)));
            }
        },
        addParty() {
            this.parties.push({ type: 'organization', organization_id: '', contact_id: '', share: 0 });
        },
        removeParty(index) {
            this.parties.splice(index, 1);
        }
    }
}
</script>
@endsection
