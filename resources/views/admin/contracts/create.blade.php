@extends('admin.layouts.app')

@section('title', 'Neuer Vertrag')

@php
    $defaultParties = old('parties', [
        ['type' => 'organization', 'organization_id' => '', 'contact_id' => '', 'share' => 50, 'role_label' => ''],
        ['type' => 'organization', 'organization_id' => '', 'contact_id' => '', 'share' => 50, 'role_label' => ''],
    ]);
@endphp

@php
    $blankContract = new \App\Models\Contract;
    $orgMeta = $organizations->mapWithKeys(fn ($o) => [(string) $o->id => [
        'name' => $o->primary_name,
        'address' => implode(', ', array_filter([$o->street, trim(($o->zip ?? '') . ' ' . ($o->city ?? ''))])),
    ]]);
    $contactMeta = $contacts->mapWithKeys(fn ($c) => [(string) $c->id => [
        'name' => $c->full_name,
        'address' => implode(', ', array_filter([$c->street, trim(($c->zip ?? '') . ' ' . ($c->city ?? ''))])),
    ]]);
@endphp
@section('content')
<div class="max-w-3xl" x-data="contractForm()">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Neuer Vertrag</h2>
        <p class="text-sm text-gray-500 mt-1">Die Vertragsnummer wird automatisch generiert.</p>
    </div>

    <form method="POST" action="{{ route('admin.contracts.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            @if($templates->count())
            <div>
                <label for="template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vorlage</label>
                <select id="template_id" name="template_id" @change="onTemplateChange($event.target.value)" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— Keine Vorlage —</option>
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Wähle eine Vorlage, um Typ und Bedingungen automatisch auszufüllen.</p>
                <p x-show="templateDocumentCount > 0" x-cloak class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                    <span x-text="templateDocumentCount"></span> Dokument(e) aus der Vorlage werden beim Speichern als eigene Kopie beigelegt.
                </p>
            </div>
            @endif

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titel *</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Typ *</label>
                    <select name="type" id="type" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($contractTypes as $ct)
                            <option value="{{ $ct->slug }}" {{ old('type') === $ct->slug ? 'selected' : '' }}>{{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                    <select name="status" id="status" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(['draft' => 'Entwurf', 'active' => 'Aktiv', 'expired' => 'Ausgelaufen', 'terminated' => 'Gekündigt'] as $v => $l)
                            <option value="{{ $v }}" {{ old('status', 'draft') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sprache</label>
                    <select name="language" id="language" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="de" {{ old('language', 'de') === 'de' ? 'selected' : '' }}>Deutsch</option>
                        <option value="en" {{ old('language', 'de') === 'en' ? 'selected' : '' }}>English</option>
                        <option value="es" {{ old('language', 'de') === 'es' ? 'selected' : '' }}>Español</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Startdatum</label>
                    <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enddatum</label>
                    <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            @include('admin.partials.contract-zession', [
                'has' => (bool) old('has_zession', $blankContract->has_zession),
                'amount' => old('zession_amount', $blankContract->zession_amount),
                'currency' => old('zession_currency', $blankContract->zession_currency ?? 'CHF'),
                'notes' => old('zession_notes', $blankContract->zession_notes),
            ])

            @include('admin.partials.contract-territory', [
                'territory' => old('territory', $blankContract->territory ?? []),
                'territoryPresets' => $territoryPresets,
            ])

            {{-- Vertragsparteien --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex justify-between items-center mb-3">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Vertragsparteien * <span class="text-gray-400 font-normal">(mind. 2)</span></p>
                    <button type="button" @click="addParty()" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Partei hinzufügen</button>
                </div>
                @error('parties') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

                <template x-for="(party, index) in parties" :key="index">
                    <div class="border border-gray-200 rounded-lg p-4 mb-3">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400" x-text="'Partei ' + (index + 1)"></span>
                            <button type="button" @click="removeParty(index)" x-show="parties.length > 2" class="text-red-400 hover:text-red-600">
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


                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Genereller Anteil (%)</label>
                                <input type="number" :name="'parties['+index+'][share]'" x-model="party.share" @input="balanceShare(index)" step="0.01" min="0" max="100" required class="w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Rolle im Vertrag</label>
                                <input type="text" :name="'parties['+index+'][role_label]'" x-model="party.role_label" list="contract-role-labels" placeholder="z.B. Label" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="text-[11px] text-gray-400 mt-0.5">Erscheint als «nachfolgend «…»». Gleiche Rolle = gemeinsame Partei.</p>
                            </div>
                        </div>
                    </div>
                </template>

                <datalist id="contract-role-labels">
                    <option value="Label"></option>
                    <option value="Künstlerin oder Künstler"></option>
                    <option value="Verlag"></option>
                    <option value="Urheberin oder Urheber"></option>
                    <option value="Management"></option>
                    <option value="Auftraggeber"></option>
                </datalist>

                <div class="flex items-center justify-between text-sm mt-2 px-1">
                    <span class="text-gray-500 dark:text-gray-400">Total:</span>
                    <span :class="Math.abs(totalShare - 100) < 0.01 ? 'text-green-600 font-medium' : 'text-red-600 font-medium'" x-text="totalShare.toFixed(2) + '%'"></span>
                </div>
                <p x-show="Math.abs(totalShare - 100) >= 0.01" class="text-red-500 text-xs mt-1">Die Summe der Anteile muss genau 100% ergeben.</p>
            </div>

            @include('admin.partials.contract-preamble', [
                'mode' => old('preamble_mode', $blankContract->preamble_mode ?? 'auto'),
                'showPartiesTable' => (bool) old('show_parties_table', $blankContract->show_parties_table ?? true),
                'text' => old('preamble_text', $blankContract->preamble_text ?? ''),
            ])

            {{-- Vertragsgegenstand --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex items-baseline justify-between mb-1">
                    <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vertragsgegenstand</label>
                    <input type="text" name="subject_heading" value="{{ old('subject_heading', $blankContract->subject_heading ?? '') }}" placeholder="Eigener Titel (Standard: Vertragsgegenstand)" class="w-72 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                </div>
                <textarea name="subject" id="subject" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Beschreibung des Vertragsgegenstands...">{{ old('subject') }}</textarea>
            </div>

            @include('admin.partials.rights-editor', [
                'rightsLabelA' => old('rights_label_a', ''),
                'rightsLabelB' => old('rights_label_b', ''),
                'rightsLabels' => old('rights_labels', []),
                'rightsData' => old('rights', []),
            ])

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex items-baseline justify-between mb-1">
                    <label for="relations_note" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Verknüpfungen</label>
                    <input type="text" name="relations_heading" value="{{ old('relations_heading', $blankContract->relations_heading ?? '') }}" placeholder="Eigener Titel, z.B. Anhang: Aufnahmen" class="w-72 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Einleitungstext zu den verknüpften Projekten, Tracks und Produkten (im PDF). Bei Tracks werden die Credits automatisch eingeblendet.</p>
                <textarea name="relations_note" id="relations_note" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('relations_note', 'Folgende Songs sind Bestandteil dieses Vertrages.') }}</textarea>
            </div>

            <div>
                @include('admin.partials.project-search', ['selected' => collect()])
            </div>

            <div>
                @include('admin.partials.track-search', ['selected' => collect()])
            </div>

            <div>
                @include('admin.partials.release-search', ['selected' => collect()])
            </div>

            @include('admin.partials.contract-sections', [
                'sections' => old('sections', $blankContract->sections ?? []),
                'autoNumber' => (bool) old('auto_number_sections', $blankContract->auto_number_sections ?? true),
                'termsValue' => old('terms', $blankContract->terms ?? ''),
                'closingValue' => old('closing_note', $blankContract->closing_note ?? ''),
            ])

            @include('admin.partials.contract-logo', ['model' => null, 'offerTemplateLogo' => true])

            <div>
                <label for="document" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vertragsdokument</label>
                <input type="file" name="document" id="document" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                <input type="text" name="document_notes" value="{{ old('document_notes') }}" placeholder="Notiz zum Dokument (optional)" class="w-full mt-2 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Vertrag erstellen</button>
            <a href="{{ route('admin.contracts.index') }}" class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Abbrechen</a>
        </div>
    </form>
</div>

<script>
function contractForm() {
    return {
        orgMeta: @json($orgMeta),
        contactMeta: @json($contactMeta),
        templateDocumentCount: 0,
        get preamblePreview() {
            return contractPreamblePreview(this.parties, this.orgMeta, this.contactMeta);
        },
        /** Checkboxes sit outside Alpine's reach, so they are set by name. */
        setCheckbox(name, value) {
            const el = document.querySelector(`input[type="checkbox"][name="${name}"]`);
            if (el) el.checked = !!value;
        },
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
            const names = this.parties.map(p => {
                if (p.type === 'organization' && p.organization_id) {
                    return this.orgNames[p.organization_id] || '';
                } else if (p.type === 'contact' && p.contact_id) {
                    return this.contactNames[p.contact_id] || '';
                }
                return '';
            });
            window.dispatchEvent(new CustomEvent('party-names-updated', {
                detail: { parties: names, party1: names[0] || '', party2: names[1] || '' }
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
        async onTemplateChange(templateId) {
            if (!templateId) return;
            try {
                const res = await fetch(`/admin/contract-templates/${templateId}/data`);
                const data = await res.json();
                if (data.contract_type_slug) {
                    document.getElementById('type').value = data.contract_type_slug;
                }
                if (data.default_status) {
                    document.getElementById('status').value = data.default_status;
                }
                if (data.language) {
                    document.getElementById('language').value = data.language;
                }
                if (data.default_terms) {
                    document.getElementById('terms').value = data.default_terms;
                }
                if (data.default_subject) {
                    document.getElementById('subject').value = data.default_subject;
                }
                if (data.default_relations_note) {
                    document.getElementById('relations_note').value = data.default_relations_note;
                }
                ['subject_heading', 'relations_heading', 'closing_note'].forEach(field => {
                    const value = data['default_' + field];
                    const el = document.querySelector(`[name="${field}"]`);
                    if (value && el) el.value = value;
                });
                if (data.default_sections && data.default_sections.length > 0) {
                    window.dispatchEvent(new CustomEvent('contract-sections-set', {
                        detail: { editorId: 'terms', sections: data.default_sections }
                    }));
                }

                this.setCheckbox('auto_number_sections', data.default_auto_number_sections);
                this.setCheckbox('show_parties_table', data.default_show_parties_table);

                window.dispatchEvent(new CustomEvent('preamble-set', {
                    detail: { mode: data.default_preamble_mode || 'auto' }
                }));
                const preambleText = document.querySelector('[name="preamble_text"]');
                if (preambleText) preambleText.value = data.default_preamble_text || '';

                window.dispatchEvent(new CustomEvent('zession-set', {
                    detail: {
                        has: data.default_has_zession,
                        amount: data.default_zession_amount,
                        currency: data.default_zession_currency,
                        notes: data.default_zession_notes,
                    }
                }));

                window.dispatchEvent(new CustomEvent('territory-set', { detail: data.default_territory || [] }));

                window.dispatchEvent(new CustomEvent('paste-projects', { detail: data.default_projects || [] }));
                window.dispatchEvent(new CustomEvent('paste-tracks', { detail: data.default_tracks || [] }));
                window.dispatchEvent(new CustomEvent('paste-releases', { detail: data.default_releases || [] }));

                window.dispatchEvent(new CustomEvent('template-logo-set', { detail: data.logo }));
                this.templateDocumentCount = data.document_count || 0;
                if (data.default_parties && data.default_parties.length > 0) {
                    this.parties = data.default_parties.map(p => ({
                        type: p.type || 'organization',
                        organization_id: p.organization_id ? String(p.organization_id) : '',
                        contact_id: p.contact_id ? String(p.contact_id) : '',
                        share: parseFloat(p.share) || 0,
                        role_label: p.role_label || '',
                    }));
                }
                // Apply rights from template
                const rightsEl = document.querySelector('[x-data="rightsEditor()"]');
                const rd = rightsEl
                    ? (window.Alpine && window.Alpine.$data ? window.Alpine.$data(rightsEl) : (rightsEl.__x && rightsEl.__x.$data))
                    : null;
                if (rd) {
                    let labels = (data.rights_labels && data.rights_labels.length)
                        ? data.rights_labels
                        : [data.rights_label_a || '', data.rights_label_b || ''];
                    rd.labels = labels.map(l => (l == null ? '' : String(l)));
                    while (rd.labels.length < 2) rd.labels.push('');
                    // Match init(): only freeze auto-grow when the template actually carries labels.
                    rd.autoGrow = !rd.labels.some(l => l !== '');
                    if (data.rights && data.rights.length > 0) {
                        rd.rights = data.rights.map(r => rd.normalizeRight(r));
                    }
                    // Always re-align existing rights' splits to the new label count.
                    rd.syncSplits();
                }
            } catch (e) {
                console.error('Template laden fehlgeschlagen', e);
            }
        },
        balanceShare(changedIndex) {
            if (this.parties.length === 2) {
                const otherIndex = changedIndex === 0 ? 1 : 0;
                this.parties[otherIndex].share = Math.max(0, parseFloat((100 - (parseFloat(this.parties[changedIndex].share) || 0)).toFixed(2)));
            }
        },
        addParty() {
            this.parties.push({ type: 'organization', organization_id: '', contact_id: '', share: 0, role_label: '' });
        },
        removeParty(index) {
            if (this.parties.length > 2) {
                this.parties.splice(index, 1);
            }
        }
    }
}
</script>
@endsection
