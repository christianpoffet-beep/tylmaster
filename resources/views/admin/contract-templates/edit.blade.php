@extends('admin.layouts.app')

@section('title', 'Vertragsvorlage bearbeiten')

@php
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
<div class="max-w-3xl" x-data="templateForm()">
    <form method="POST" action="{{ route('admin.contract-templates.update', $contractTemplate) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $contractTemplate->name) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label for="contract_type_slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vertragstyp *</label>
                    <select name="contract_type_slug" id="contract_type_slug" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($contractTypes as $ct)
                            <option value="{{ $ct->slug }}" {{ old('contract_type_slug', $contractTemplate->contract_type_slug) === $ct->slug ? 'selected' : '' }}>{{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sprache *</label>
                    @php
                        $languageOptions = ['de' => 'Deutsch', 'en' => 'English', 'es' => 'Español'];
                        // Keep a legacy/out-of-range stored value selectable so an unrelated edit can't silently coerce it.
                        if ($contractTemplate->language && !isset($languageOptions[$contractTemplate->language])) {
                            $languageOptions[$contractTemplate->language] = strtoupper($contractTemplate->language);
                        }
                    @endphp
                    <select name="language" id="language" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($languageOptions as $code => $label)
                            <option value="{{ $code }}" {{ old('language', $contractTemplate->language) === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="default_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Standard-Status</label>
                    <select name="default_status" id="default_status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Kein Standard —</option>
                        @foreach(['draft' => 'Entwurf', 'active' => 'Aktiv', 'expired' => 'Ausgelaufen', 'terminated' => 'Gekündigt'] as $v => $l)
                            <option value="{{ $v }}" {{ old('default_status', $contractTemplate->default_status) === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reihenfolge</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $contractTemplate->sort_order) }}" min="0" class="w-24 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            @include('admin.partials.contract-zession', [
                'prefix' => 'default_',
                'has' => (bool) old('default_has_zession', $contractTemplate->default_has_zession ?? false),
                'amount' => old('default_zession_amount', $contractTemplate->default_zession_amount ?? null),
                'currency' => old('default_zession_currency', $contractTemplate->default_zession_currency ?? 'CHF'),
                'notes' => old('default_zession_notes', $contractTemplate->default_zession_notes ?? ''),
            ])

            @include('admin.partials.contract-territory', [
                'field' => 'default_territory',
                'territory' => old('default_territory', $contractTemplate->default_territory ?? []),
                'territoryPresets' => $territoryPresets,
            ])

            {{-- Standard-Parteien --}}
            <div class="border-t border-gray-200 pt-6">
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

                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Genereller Anteil (%)</label>
                                <input type="number" :name="'parties['+index+'][share]'" x-model="party.share" @input="balanceShare(index)" step="0.01" min="0" max="100" required class="w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Rolle im Vertrag</label>
                                <input type="text" :name="'parties['+index+'][role_label]'" x-model="party.role_label" placeholder="z.B. Label" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                </template>

                <div x-show="parties.length > 0" class="flex items-center justify-between text-sm mt-2 px-1">
                    <span class="text-gray-500 dark:text-gray-400">Total:</span>
                    <span :class="Math.abs(totalShare - 100) < 0.01 ? 'text-green-600 font-medium' : 'text-red-600 font-medium'" x-text="totalShare.toFixed(2) + '%'"></span>
                </div>
            </div>

            @include('admin.partials.contract-preamble', [
                'prefix' => 'default_',
                'mode' => old('default_preamble_mode', $contractTemplate->default_preamble_mode ?? 'auto'),
                'showPartiesTable' => (bool) old('default_show_parties_table', $contractTemplate->default_show_parties_table ?? true),
                'text' => old('default_preamble_text', $contractTemplate->default_preamble_text ?? ''),
            ])

            {{-- Standard-Vertragsgegenstand --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex items-baseline justify-between mb-1">
                    <label for="default_subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Standard-Vertragsgegenstand</label>
                    <input type="text" name="default_subject_heading" value="{{ old('default_subject_heading', $contractTemplate->default_subject_heading) }}" placeholder="Eigener Titel (Standard: Vertragsgegenstand)" class="w-72 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                </div>
                <textarea name="default_subject" id="default_subject" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Wird beim Erstellen eines neuen Vertrags ins Feld «Vertragsgegenstand» übernommen.">{{ old('default_subject', $contractTemplate->default_subject) }}</textarea>
            </div>

            @include('admin.partials.rights-editor', [
                'rightsLabelA' => old('rights_label_a', $contractTemplate->rights_label_a ?? ''),
                'rightsLabelB' => old('rights_label_b', $contractTemplate->rights_label_b ?? ''),
                'rightsLabels' => old('rights_labels', $contractTemplate->rights_labels ?? []),
                'rightsData' => old('rights', $contractTemplate->rights ?? []),
            ])

            {{-- Standard-Verknüpfungstext --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex items-baseline justify-between mb-1">
                    <label for="default_relations_note" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Standard-Verknüpfungstext</label>
                    <input type="text" name="default_relations_heading" value="{{ old('default_relations_heading', $contractTemplate->default_relations_heading) }}" placeholder="Eigener Titel, z.B. Anhang: Aufnahmen" class="w-72 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                </div>
                <textarea name="default_relations_note" id="default_relations_note" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Einleitungstext zu den Verknüpfungen, z.B. «Folgende Songs sind Bestandteil dieses Vertrages.»">{{ old('default_relations_note', $contractTemplate->default_relations_note) }}</textarea>
                <p class="text-xs text-gray-400 mt-1">Wird beim Erstellen eines neuen Vertrags ins Feld «Verknüpfungen» übernommen.</p>
            </div>

            @include('admin.partials.contract-sections', [
                'heading' => 'Standard-Bedingungen / Abschnitte',
                'prefix' => 'default_sections',
                'termsField' => 'default_terms',
                'subjectField' => 'default_subject',
                'closingField' => 'default_closing_note',
                'autoNumberField' => 'default_auto_number_sections',
                'sections' => old('default_sections', $contractTemplate->default_sections ?? []),
                'autoNumber' => (bool) old('default_auto_number_sections', $contractTemplate->default_auto_number_sections ?? true),
                'termsValue' => old('default_terms', $contractTemplate->default_terms ?? ''),
                'closingValue' => old('default_closing_note', $contractTemplate->default_closing_note ?? ''),
            ])

            {{-- Standard-Verknüpfungen --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-6">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Standard-Verknüpfungen</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 -mt-4">Projekte, Tracks und Produkte, mit denen ein neuer Vertrag aus dieser Vorlage startet.</p>

                @include('admin.partials.project-search', ['selected' => $contractTemplate->defaultProjects(), 'projectInputName' => 'default_project_ids[]'])
                @include('admin.partials.track-search', ['selected' => $contractTemplate->defaultTracks(), 'trackInputName' => 'default_track_ids[]'])
                @include('admin.partials.release-search', ['selected' => $contractTemplate->defaultReleases(), 'releaseInputName' => 'default_release_ids[]'])
            </div>

            @include('admin.partials.contract-logo', ['model' => $contractTemplate])

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <label for="document" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Standard-Vertragsdokument</label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Wird jedem Vertrag aus dieser Vorlage als eigene Kopie beigelegt.</p>
                <input type="file" name="document" id="document" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                <input type="text" name="document_notes" value="{{ old('document_notes') }}" placeholder="Notiz zum Dokument (optional)" class="w-full mt-2 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('document') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Speichern</button>
            <a href="{{ route('admin.contract-templates.index') }}" class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Abbrechen</a>
        </div>
    </form>

    {{-- Dokumente der Vorlage — ausserhalb der Hauptform, damit das Archivieren ein eigenes Formular sein kann --}}
    @if($contractTemplate->documents->count())
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Standard-Vertragsdokumente</label>
        <div class="space-y-2">
            @foreach($contractTemplate->documents as $doc)
                <div class="flex items-center justify-between py-2 px-3 rounded-lg {{ $doc->trashed() ? 'bg-red-50 dark:bg-red-900/20' : 'bg-gray-50 dark:bg-gray-700/50' }}">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded bg-gray-200 dark:bg-gray-600 text-[10px] font-bold text-gray-500 dark:text-gray-300 flex-shrink-0">{{ $doc->file_extension }}</span>
                        <div class="min-w-0">
                            <span class="text-sm truncate {{ $doc->trashed() ? 'text-red-400 line-through' : 'text-gray-700 dark:text-gray-200' }}">{{ $doc->title }}</span>
                            @if($doc->notes)
                                <span class="text-xs block truncate text-gray-400 dark:text-gray-500">{{ $doc->notes }}</span>
                            @endif
                            @if($doc->trashed())
                                <span class="text-xs text-red-400">Archiviert am {{ $doc->deleted_at->format('d.m.Y H:i') }}</span>
                            @endif
                        </div>
                    </div>
                    @if(!$doc->trashed())
                    <div class="flex items-center gap-2 ml-2 flex-shrink-0">
                        <a href="{{ route('admin.documents.download', $doc) }}" title="Download" class="text-gray-400 hover:text-blue-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                        <form method="POST" action="{{ route('admin.contract-templates.documents.archive', [$contractTemplate, $doc]) }}" onsubmit="return confirm('Dokument wirklich archivieren?')">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-red-400 hover:text-red-600" title="Archivieren">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-red-600 mb-2">Gefahrenzone</h3>
        <form method="POST" action="{{ route('admin.contract-templates.destroy', $contractTemplate) }}" onsubmit="return confirm('Vorlage wirklich löschen?')">
            @csrf
            @method('DELETE')
            <p class="text-sm text-gray-500 mb-3">Die Vertragsvorlage wird unwiderruflich gelöscht.</p>
            <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Vorlage löschen</button>
        </form>
    </div>
</div>

<script>
function templateForm() {
    return {
        orgMeta: @json($orgMeta),
        contactMeta: @json($contactMeta),
        get preamblePreview() {
            return contractPreamblePreview(this.parties, this.orgMeta, this.contactMeta);
        },
        orgContactsMap: @json($orgContactsMap),
        orgNames: @json($organizations->pluck('primary_name', 'id')),
        contactNames: @json($contacts->mapWithKeys(fn($c) => [$c->id => $c->full_name])),
        parties: @json(old('parties', $partiesData)),
        get totalShare() {
            return this.parties.reduce((sum, p) => sum + (parseFloat(p.share) || 0), 0);
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
        init() {
            this.$watch('parties', () => this.dispatchPartyNames());
            this.$nextTick(() => {
                this.parties.forEach((party, i) => {
                    if (party.type === 'organization' && party.contact_id) {
                        const saved = party.contact_id;
                        this.parties[i].contact_id = '';
                        this.$nextTick(() => {
                            this.parties[i].contact_id = saved;
                        });
                    }
                });
                this.dispatchPartyNames();
            });
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
            this.parties.push({ type: 'organization', organization_id: '', contact_id: '', share: 0, role_label: '' });
        },
        removeParty(index) {
            this.parties.splice(index, 1);
        }
    }
}
</script>
@endsection
