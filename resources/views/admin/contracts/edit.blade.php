@extends('admin.layouts.app')

@section('title', 'Vertrag bearbeiten')


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

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

            {{-- Zession (Vorschusszahlung) --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6" x-data="{ hasZession: {{ old('has_zession', $contract->has_zession) ? 'true' : 'false' }} }">
                <div class="flex items-center gap-3 mb-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="has_zession" value="0">
                        <input type="checkbox" name="has_zession" value="1" x-model="hasZession" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 dark:bg-gray-600 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Zession (Vorschusszahlung)</span>
                </div>
                <div x-show="hasZession" x-transition class="space-y-3 ml-12">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Vorschuss, der mit künftigen Einnahmen verrechnet wird.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="zession_amount" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Betrag</label>
                            <input type="number" name="zession_amount" id="zession_amount" value="{{ old('zession_amount', $contract->zession_amount) }}" step="0.01" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="0.00">
                        </div>
                        <div>
                            <label for="zession_currency" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Währung</label>
                            <select name="zession_currency" id="zession_currency" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach(['CHF' => 'CHF', 'EUR' => 'EUR', 'USD' => 'USD'] as $code => $label)
                                    <option value="{{ $code }}" {{ old('zession_currency', $contract->zession_currency) === $code ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="zession_notes" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Notizen zur Zession</label>
                        <textarea name="zession_notes" id="zession_notes" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="z.B. Rückzahlungsbedingungen...">{{ old('zession_notes', $contract->zession_notes) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Territory --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6" x-data="territorySelector()">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Geltungsbereich / Territory</p>

                <div class="flex items-center gap-3 mb-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="worldwide" @change="onWorldwideToggle()" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 dark:bg-gray-600 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Weltweit</span>
                </div>
                <input type="hidden" name="territory_worldwide" :value="worldwide ? '1' : '0'">

                <div x-show="!worldwide" x-transition>
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach($territoryPresets as $key => $preset)
                            @if($key !== 'world')
                            <button type="button"
                                @click="togglePreset('{{ $key }}')"
                                :class="isPresetActive('{{ $key }}') ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-600' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border hover:shadow-sm transition-colors">
                                {{ $preset['label'] }}
                            </button>
                            @endif
                        @endforeach
                    </div>

                    <div class="max-h-48 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-1">
                            <template x-for="country in allCountries" :key="country.code">
                                <label class="inline-flex items-center gap-1.5 text-xs cursor-pointer py-0.5">
                                    <input type="checkbox" :value="country.code" name="territory[]" :checked="selected.includes(country.code)" @change="toggleCountry(country.code)" class="rounded text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                                    <span class="text-gray-700 dark:text-gray-300 truncate" x-text="country.name"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1"><span x-text="selected.length"></span> Länder ausgewählt</p>
                </div>
            </div>

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

                        <div class="mt-3">
                            <label class="block text-xs text-gray-500 mb-1">Genereller Anteil (%)</label>
                            <input type="number" :name="'parties['+index+'][share]'" x-model="party.share" @input="balanceShare(index)" step="0.01" min="0" max="100" required class="w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </template>

                <div class="flex items-center justify-between text-sm mt-2 px-1">
                    <span class="text-gray-500 dark:text-gray-400">Total:</span>
                    <span :class="Math.abs(totalShare - 100) < 0.01 ? 'text-green-600 font-medium' : 'text-red-600 font-medium'" x-text="totalShare.toFixed(2) + '%'"></span>
                </div>
                <p x-show="Math.abs(totalShare - 100) >= 0.01" class="text-red-500 text-xs mt-1">Die Summe der Anteile muss genau 100% ergeben.</p>
            </div>

            @include('admin.partials.rights-editor', [
                'rightsLabelA' => old('rights_label_a', $contract->rights_label_a ?? ''),
                'rightsLabelB' => old('rights_label_b', $contract->rights_label_b ?? ''),
                'rightsData' => old('rights', $contract->rights ?? []),
            ])

            {{-- Projekte --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Projekte</label>
                <select name="project_ids[]" multiple class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" size="4">
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ in_array($project->id, old('project_ids', $contract->projects->pluck('id')->toArray())) ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Ctrl/Cmd gedrückt halten für Mehrfachauswahl</p>
            </div>

            @if($tracks->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tracks</label>
                <select name="track_ids[]" multiple class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" size="4">
                    @foreach($tracks as $track)
                        <option value="{{ $track->id }}" {{ in_array($track->id, old('track_ids', $contract->tracks->pluck('id')->toArray())) ? 'selected' : '' }}>{{ $track->title }}{{ $track->isrc ? ' (' . $track->isrc . ')' : '' }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Ctrl/Cmd gedrückt halten für Mehrfachauswahl</p>
            </div>
            @endif

            @if($releases->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Releases</label>
                <select name="release_ids[]" multiple class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" size="4">
                    @foreach($releases as $release)
                        <option value="{{ $release->id }}" {{ in_array($release->id, old('release_ids', $contract->releases->pluck('id')->toArray())) ? 'selected' : '' }}>{{ $release->title }}{{ $release->upc ? ' (' . $release->upc . ')' : '' }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Ctrl/Cmd gedrückt halten für Mehrfachauswahl</p>
            </div>
            @endif

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <label for="terms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bedingungen / Notizen</label>
                <textarea name="terms" id="terms" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('terms', $contract->terms) }}</textarea>
            </div>

            <div>
                <label for="document" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Neues Dokument hochladen</label>
                <input type="file" name="document" id="document" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                <input type="text" name="document_notes" value="{{ old('document_notes') }}" placeholder="Notiz zum Dokument (optional)" class="w-full mt-2 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Vertrag aktualisieren</button>
            <a href="{{ route('admin.contracts.show', $contract) }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
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
function territorySelector() {
    const presets = @json($territoryPresets);
    const existingTerritory = @json(old('territory', $contract->territory ?? []));
    return {
        worldwide: existingTerritory.includes('ALL') || {{ old('territory_worldwide', (is_array($contract->territory) && in_array('ALL', $contract->territory)) ? '1' : '0') ? 'true' : 'false' }},
        selected: existingTerritory.filter(c => c !== 'ALL'),
        presets: presets,
        allCountries: [
            {code:'AF',name:'Afghanistan'},{code:'EG',name:'Ägypten'},{code:'AL',name:'Albanien'},{code:'DZ',name:'Algerien'},
            {code:'AD',name:'Andorra'},{code:'AR',name:'Argentinien'},{code:'AM',name:'Armenien'},{code:'AZ',name:'Aserbaidschan'},
            {code:'AU',name:'Australien'},{code:'BE',name:'Belgien'},{code:'BA',name:'Bosnien und Herzegowina'},{code:'BR',name:'Brasilien'},
            {code:'BG',name:'Bulgarien'},{code:'CL',name:'Chile'},{code:'CN',name:'China'},{code:'CR',name:'Costa Rica'},
            {code:'DK',name:'Dänemark'},{code:'DE',name:'Deutschland'},{code:'EC',name:'Ecuador'},{code:'EE',name:'Estland'},
            {code:'FI',name:'Finnland'},{code:'FR',name:'Frankreich'},{code:'GE',name:'Georgien'},{code:'GH',name:'Ghana'},
            {code:'GR',name:'Griechenland'},{code:'GB',name:'Grossbritannien'},{code:'GT',name:'Guatemala'},
            {code:'HN',name:'Honduras'},{code:'IN',name:'Indien'},{code:'ID',name:'Indonesien'},{code:'IQ',name:'Irak'},
            {code:'IR',name:'Iran'},{code:'IE',name:'Irland'},{code:'IS',name:'Island'},{code:'IL',name:'Israel'},
            {code:'IT',name:'Italien'},{code:'JP',name:'Japan'},{code:'JO',name:'Jordanien'},{code:'CA',name:'Kanada'},
            {code:'KZ',name:'Kasachstan'},{code:'KE',name:'Kenia'},{code:'CO',name:'Kolumbien'},{code:'XK',name:'Kosovo'},
            {code:'HR',name:'Kroatien'},{code:'CU',name:'Kuba'},{code:'LV',name:'Lettland'},{code:'LB',name:'Libanon'},
            {code:'LI',name:'Liechtenstein'},{code:'LT',name:'Litauen'},{code:'LU',name:'Luxemburg'},{code:'MY',name:'Malaysia'},
            {code:'MT',name:'Malta'},{code:'MA',name:'Marokko'},{code:'MX',name:'Mexiko'},{code:'MD',name:'Moldau'},
            {code:'MC',name:'Monaco'},{code:'ME',name:'Montenegro'},{code:'MZ',name:'Mosambik'},{code:'NZ',name:'Neuseeland'},
            {code:'NL',name:'Niederlande'},{code:'NG',name:'Nigeria'},{code:'MK',name:'Nordmazedonien'},{code:'NO',name:'Norwegen'},
            {code:'AT',name:'Österreich'},{code:'PK',name:'Pakistan'},{code:'PA',name:'Panama'},{code:'PY',name:'Paraguay'},
            {code:'PE',name:'Peru'},{code:'PH',name:'Philippinen'},{code:'PL',name:'Polen'},{code:'PT',name:'Portugal'},
            {code:'RO',name:'Rumänien'},{code:'RU',name:'Russland'},{code:'SA',name:'Saudi-Arabien'},{code:'SE',name:'Schweden'},
            {code:'CH',name:'Schweiz'},{code:'RS',name:'Serbien'},{code:'SG',name:'Singapur'},{code:'SK',name:'Slowakei'},
            {code:'SI',name:'Slowenien'},{code:'ES',name:'Spanien'},{code:'ZA',name:'Südafrika'},{code:'KR',name:'Südkorea'},
            {code:'TW',name:'Taiwan'},{code:'TH',name:'Thailand'},{code:'CZ',name:'Tschechien'},{code:'TN',name:'Tunesien'},
            {code:'TR',name:'Türkei'},{code:'UA',name:'Ukraine'},{code:'HU',name:'Ungarn'},{code:'UY',name:'Uruguay'},
            {code:'US',name:'USA'},{code:'AE',name:'VAE'},{code:'VE',name:'Venezuela'},{code:'VN',name:'Vietnam'},
            {code:'BY',name:'Weissrussland'},{code:'CY',name:'Zypern'}
        ],
        onWorldwideToggle() {
            if (this.worldwide) this.selected = [];
        },
        toggleCountry(code) {
            const idx = this.selected.indexOf(code);
            if (idx >= 0) { this.selected.splice(idx, 1); } else { this.selected.push(code); }
        },
        togglePreset(key) {
            const countries = this.presets[key]?.countries || [];
            const allPresent = countries.every(c => this.selected.includes(c));
            if (allPresent) {
                this.selected = this.selected.filter(c => !countries.includes(c));
            } else {
                countries.forEach(c => { if (!this.selected.includes(c)) this.selected.push(c); });
            }
        },
        isPresetActive(key) {
            const countries = this.presets[key]?.countries || [];
            return countries.length > 0 && countries.every(c => this.selected.includes(c));
        }
    };
}

function contractForm() {
    return {
        orgContactsMap: @json($orgContactsMap),
        orgNames: @json($organizations->pluck('primary_name', 'id')),
        contactNames: @json($contacts->mapWithKeys(fn($c) => [$c->id => $c->full_name])),
        parties: @json(old('parties', $partiesData)),
        get totalShare() {
            return this.parties.reduce((sum, p) => sum + (parseFloat(p.share) || 0), 0);
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
            this.parties.push({ type: 'organization', organization_id: '', contact_id: '', share: 0 });
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
