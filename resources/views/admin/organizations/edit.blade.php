@extends('admin.layouts.app')

@section('title', 'Organisation bearbeiten')

@php
    $typeLabels = \App\Models\OrganizationType::orderBy('sort_order')->pluck('name', 'slug')->toArray();
@endphp

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.organizations.update', $organization) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Avatar</label>
                @if($organization->avatar_path)
                    <div class="flex items-center gap-3 mb-2">
                        <img src="{{ Storage::url($organization->avatar_path) }}" alt="Avatar" class="h-16 w-16 rounded-full object-cover border">
                        <label class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <input type="checkbox" name="remove_avatar" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                            Entfernen
                        </label>
                    </div>
                @endif
                <input type="file" name="avatar" id="avatar" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                @error('avatar') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Typ *</label>
                    <select name="type" id="type" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($typeLabels as $v => $l)
                            <option value="{{ $v }}" {{ old('type', $organization->type) === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="legal_form" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rechtsform</label>
                    <select name="legal_form" id="legal_form" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Bitte wählen —</option>
                        @foreach(['AG' => 'AG', 'GmbH' => 'GmbH', 'Verein' => 'Verein', 'Stiftung' => 'Stiftung', 'Einzelfirma' => 'Einzelfirma', 'Ltd' => 'Ltd', 'LLP' => 'LLP (UK)', 'LLC' => 'LLC'] as $v => $l)
                            <option value="{{ $v }}" {{ old('legal_form', $organization->legal_form) === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Dynamic names --}}
            <div x-data="{ names: {{ json_encode(old('names', $organization->names ?? [''])) }} }">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Namen * <span class="text-gray-400 font-normal">(mind. 1)</span></label>
                <template x-for="(name, index) in names" :key="index">
                    <div class="flex gap-2 mb-2">
                        <input type="text" :name="'names[' + index + ']'" x-model="names[index]" :placeholder="index === 0 ? 'Primärer Name *' : 'Alias-Name'" :required="index === 0" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" x-show="names.length > 1" @click="names.splice(index, 1)" class="px-2 text-red-400 hover:text-red-600">&times;</button>
                    </div>
                </template>
                <button type="button" @click="names.push('')" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Weiterer Name</button>
                @error('names') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                @error('names.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="biography" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Biografie</label>
                <textarea name="biography" id="biography" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('biography', $organization->biography) }}</textarea>
            </div>

            @if($genres->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Genres</label>
                @php $selectedGenres = old('genre_ids', $organization->genres->pluck('id')->toArray()); @endphp
                <div class="flex flex-wrap gap-2">
                    @foreach($genres as $genre)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="genre_ids[]" value="{{ $genre->id }}" {{ in_array($genre->id, $selectedGenres) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">{{ $genre->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            <hr class="border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Kontaktdaten</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-Mail</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $organization->email) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $organization->phone) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Adresse</h3>

            <div>
                <label for="street" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Strasse</label>
                <input type="text" name="street" id="street" value="{{ old('street', $organization->street) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @include('admin.partials.postal-code-input', [
                    'zipName' => 'zip', 'cityName' => 'city',
                    'zipValue' => old('zip', $organization->zip), 'cityValue' => old('city', $organization->city),
                ])
                <div>
                    @include('admin.partials.country-select', ['name' => 'country', 'label' => 'Land', 'value' => old('country', $organization->country ?? '')])
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Bankverbindung</h3>

            <div>
                <label for="iban" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IBAN</label>
                <input type="text" name="iban" id="iban" value="{{ old('iban', $organization->iban) }}" placeholder="CH00 0000 0000 0000 0000 0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm font-mono focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="bank_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name Bank</label>
                    <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $organization->bank_name) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="bic" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">BIC/SWIFT</label>
                    <input type="text" name="bic" id="bic" value="{{ old('bic', $organization->bic) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @include('admin.partials.postal-code-input', [
                    'zipName' => 'bank_zip', 'cityName' => 'bank_city',
                    'zipValue' => old('bank_zip', $organization->bank_zip), 'cityValue' => old('bank_city', $organization->bank_city),
                    'zipLabel' => 'PLZ Bank', 'cityLabel' => 'Ort Bank',
                    'zipId' => 'bank_zip', 'cityId' => 'bank_city',
                ])
                <div>
                    @include('admin.partials.country-select', ['name' => 'bank_country', 'id' => 'bank_country', 'label' => 'Land Bank', 'value' => old('bank_country', $organization->bank_country ?? '')])
                </div>
            </div>

            <div>
                <label for="vat_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">UID/MWST-Nr.</label>
                <input type="text" name="vat_number" id="vat_number" value="{{ old('vat_number', $organization->vat_number) }}" placeholder="CHE-000.000.000 MWST" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            {{-- Dynamic websites --}}
            <div x-data="{ websites: {{ json_encode(old('websites', $organization->websites ?? [''])) }} }">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Websites</label>
                <template x-for="(url, index) in websites" :key="index">
                    <div class="flex gap-2 mb-2">
                        <input type="url" :name="'websites[' + index + ']'" x-model="websites[index]" placeholder="https://..." class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" x-show="websites.length > 1" @click="websites.splice(index, 1)" class="px-2 text-red-400 hover:text-red-600">&times;</button>
                    </div>
                </template>
                <button type="button" @click="websites.push('')" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Weitere Website</button>
            </div>

            @include('admin.partials.contact-search', ['selected' => $organization->contacts, 'inputName' => 'contact_ids[]'])

            @include('admin.partials.project-search', ['selected' => $organization->projects])

            @if($tracks->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tracks</label>
                <select name="track_ids[]" multiple class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" size="4">
                    @foreach($tracks as $track)
                        <option value="{{ $track->id }}" {{ in_array($track->id, old('track_ids', $organization->tracks->pluck('id')->toArray())) ? 'selected' : '' }}>{{ $track->title }}{{ $track->isrc ? ' (' . $track->isrc . ')' : '' }}</option>
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
                        <option value="{{ $release->id }}" {{ in_array($release->id, old('release_ids', $organization->releases->pluck('id')->toArray())) ? 'selected' : '' }}>{{ $release->title }}{{ $release->upc ? ' (' . $release->upc . ')' : '' }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Ctrl/Cmd gedrückt halten für Mehrfachauswahl</p>
            </div>
            @endif

            @include('admin.partials.contract-search', ['selected' => $organization->contracts])

            <div>
                <label for="document" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Neues Dokument hochladen</label>
                <input type="file" name="document" id="document" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                <input type="text" name="document_notes" value="{{ old('document_notes') }}" placeholder="Notiz zum Dokument (optional)" class="w-full mt-2 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Organisation aktualisieren</button>
            <a href="{{ route('admin.organizations.show', $organization) }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>

    {{-- Bestehende Dokumente --}}
    @if($organization->documents->count())
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Dokumente</label>
        <div class="space-y-2">
            @foreach($organization->documents as $doc)
                <div class="flex items-center justify-between py-2 px-3 rounded-lg {{ $doc->trashed() ? 'bg-red-50' : 'bg-gray-50' }}">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded bg-gray-200 text-[10px] font-bold text-gray-500 flex-shrink-0">{{ $doc->file_extension }}</span>
                        <div class="min-w-0">
                            <span class="text-sm truncate block {{ $doc->trashed() ? 'text-red-400 line-through' : 'text-gray-700' }}">{{ $doc->title }}</span>
                            @if($doc->notes)
                                <span class="text-xs block truncate {{ $doc->trashed() ? 'text-red-300 line-through' : 'text-gray-400' }}">{{ $doc->notes }}</span>
                            @endif
                            @if($doc->trashed())
                                <span class="text-xs text-red-400">Gelöscht am {{ $doc->deleted_at->format('d.m.Y H:i') }}</span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0">{{ $doc->file_size ? number_format($doc->file_size / 1024, 0) . ' KB' : '' }}</span>
                    </div>
                    @if(!$doc->trashed())
                    <div class="flex items-center gap-2 ml-2 flex-shrink-0">
                        <a href="{{ route('admin.documents.download', $doc) }}" title="Download" class="text-gray-400 hover:text-blue-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.organizations.destroy', $organization) }}" class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700" onsubmit="return confirm('Organisation wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')">
        @csrf
        @method('DELETE')
        <h3 class="text-sm font-semibold text-red-600 mb-2">Gefahrenzone</h3>
        <p class="text-sm text-gray-500 mb-3">Die Organisation und alle zugehörigen Verknüpfungen werden unwiderruflich gelöscht.</p>
        <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Organisation löschen</button>
    </form>
</div>
@endsection
