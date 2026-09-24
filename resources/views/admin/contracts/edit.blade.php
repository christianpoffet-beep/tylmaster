@extends('admin.layouts.app')

@section('title', 'Vertrag bearbeiten')


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
<div class="max-w-3xl" x-data="contractForm()">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Vertrag bearbeiten</h2>
        @if($contract->contract_number)
            <p class="text-sm text-gray-500 mt-1">{{ $contract->contract_number }}</p>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.contracts.update', $contract) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titel *</label>
                <input type="text" name="title" id="title" value="{{ old('title', $contract->title) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Typ *</label>
                    <select name="type" id="type" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($contractTypes as $ct)
                            <option value="{{ $ct->slug }}" {{ old('type', $contract->type) === $ct->slug ? 'selected' : '' }}>{{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                    <select name="status" id="status" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(['draft' => 'Entwurf', 'active' => 'Aktiv', 'expired' => 'Ausgelaufen', 'terminated' => 'Gekündigt'] as $v => $l)
                            <option value="{{ $v }}" {{ old('status', $contract->status) === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sprache</label>
                    <select name="language" id="language" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="de" {{ old('language', $contract->language ?? 'de') === 'de' ? 'selected' : '' }}>Deutsch</option>
                        <option value="en" {{ old('language', $contract->language ?? 'de') === 'en' ? 'selected' : '' }}>English</option>
                        <option value="es" {{ old('language', $contract->language ?? 'de') === 'es' ? 'selected' : '' }}>Español</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Startdatum</label>
                    <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $contract->start_date?->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enddatum</label>
                    <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $contract->end_date?->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            @include('admin.partials.contract-zession', [
                'has' => (bool) old('has_zession', $contract->has_zession),
                'amount' => old('zession_amount', $contract->zession_amount),
                'currency' => old('zession_currency', $contract->zession_currency ?? 'CHF'),
                'notes' => old('zession_notes', $contract->zession_notes),
            ])

            @include('admin.partials.contract-territory', [
                'territory' => old('territory', $contract->territory ?? []),
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
                'mode' => old('preamble_mode', $contract->preamble_mode ?? 'auto'),
                'showPartiesTable' => (bool) old('show_parties_table', $contract->show_parties_table ?? true),
                'text' => old('preamble_text', $contract->preamble_text ?? ''),
            ])

            {{-- Vertragsgegenstand --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex items-baseline justify-between mb-1">
                    <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vertragsgegenstand</label>
                    <input type="text" name="subject_heading" value="{{ old('subject_heading', $contract->subject_heading ?? '') }}" placeholder="Eigener Titel (Standard: Vertragsgegenstand)" class="w-72 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                </div>
                <textarea name="subject" id="subject" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Beschreibung des Vertragsgegenstands...">{{ old('subject', $contract->subject) }}</textarea>
            </div>

            @include('admin.partials.rights-editor', [
                'rightsLabelA' => old('rights_label_a', $contract->rights_label_a ?? ''),
                'rightsLabelB' => old('rights_label_b', $contract->rights_label_b ?? ''),
                'rightsLabels' => old('rights_labels', $contract->rights_labels ?? []),
                'rightsData' => old('rights', $contract->rights ?? []),
            ])

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex items-baseline justify-between mb-1">
                    <label for="relations_note" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Verknüpfungen</label>
                    <input type="text" name="relations_heading" value="{{ old('relations_heading', $contract->relations_heading ?? '') }}" placeholder="Eigener Titel, z.B. Anhang: Aufnahmen" class="w-72 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Einleitungstext zu den verknüpften Projekten, Tracks und Produkten (im PDF). Bei Tracks werden die Credits automatisch eingeblendet.</p>
                <textarea name="relations_note" id="relations_note" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('relations_note', $contract->relations_note ?? 'Folgende Songs sind Bestandteil dieses Vertrages.') }}</textarea>
            </div>

            <div>
                @include('admin.partials.project-search', ['selected' => $contract->projects])
            </div>

            <div>
                @include('admin.partials.track-search', ['selected' => $contract->tracks])
            </div>

            <div>
                @include('admin.partials.release-search', ['selected' => $contract->releases])
            </div>

            @include('admin.partials.contract-sections', [
                'sections' => old('sections', $contract->sections ?? []),
                'autoNumber' => (bool) old('auto_number_sections', $contract->auto_number_sections ?? true),
                'termsValue' => old('terms', $contract->terms ?? ''),
                'closingValue' => old('closing_note', $contract->closing_note ?? ''),
            ])

            @include('admin.partials.contract-logo', ['model' => $contract])

            <div>
                <label for="document" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Neues Dokument hochladen</label>
                <input type="file" name="document" id="document" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                <input type="text" name="document_notes" value="{{ old('document_notes') }}" placeholder="Notiz zum Dokument (optional)" class="w-full mt-2 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Vertrag aktualisieren</button>
            <a href="{{ route('admin.contracts.show', $contract) }}" class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Abbrechen</a>
        </div>
    </form>

    {{-- Bestehende Dokumente — ausserhalb der Hauptform --}}
    @if($contract->documents->count())
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Vertragsdokumente</label>
        <div class="space-y-2">
            @foreach($contract->documents as $doc)
                <div class="flex items-center justify-between py-2 px-3 rounded-lg {{ $doc->trashed() ? 'bg-red-50 dark:bg-red-900/20' : ($doc->is_archived ? 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' : 'bg-gray-50 dark:bg-gray-700/50') }}">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded bg-gray-200 dark:bg-gray-600 text-[10px] font-bold text-gray-500 dark:text-gray-300 flex-shrink-0">{{ $doc->file_extension }}</span>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm truncate {{ $doc->trashed() ? 'text-red-400 line-through' : 'text-gray-700 dark:text-gray-200' }}">{{ $doc->title }}</span>
                                @if($doc->is_archived && !$doc->trashed())
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 flex-shrink-0">
                                        <svg class="w-2.5 h-2.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        Archiviert
                                    </span>
                                @endif
                            </div>
                            @if($doc->notes)
                                <span class="text-xs block truncate {{ $doc->trashed() ? 'text-red-300 line-through' : 'text-gray-400 dark:text-gray-500' }}">{{ $doc->notes }}</span>
                            @endif
                            @if($doc->trashed())
                                <span class="text-xs text-red-400">Gelöscht am {{ $doc->deleted_at->format('d.m.Y H:i') }}</span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0">{{ $doc->file_size ? number_format($doc->file_size / 1024, 0) . ' KB' : '' }}</span>
                    </div>
                    <div class="flex items-center gap-2 ml-2 flex-shrink-0">
                        @if(!$doc->trashed())
                            <a href="{{ route('admin.documents.download', $doc) }}" title="Download" class="text-gray-400 hover:text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                            @if(!$doc->is_archived)
                            <form method="POST" action="{{ route('admin.contracts.documents.archive', [$contract, $doc]) }}" onsubmit="return confirm('Dokument wirklich archivieren?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-red-400 hover:text-red-600" title="Archivieren">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

<script>
function contractForm() {
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
            // Ensure contact selections persist after x-for renders
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
            if (this.parties.length > 2) {
                this.parties.splice(index, 1);
            }
        }
    }
}
</script>
@endsection
