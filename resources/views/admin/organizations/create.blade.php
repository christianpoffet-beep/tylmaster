@extends('admin.layouts.app')

@section('title', 'Organisation erstellen')

@php
    $typeLabels = \App\Models\OrganizationType::orderBy('sort_order')->pluck('name', 'slug')->toArray();
@endphp

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.organizations.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            <div>
                <label for="avatar" class="block text-sm font-medium text-gray-700 mb-1">Avatar</label>
                <input type="file" name="avatar" id="avatar" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('avatar') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Typ *</label>
                <select name="type" id="type" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— Bitte wählen —</option>
                    @foreach($typeLabels as $v => $l)
                        <option value="{{ $v }}" {{ old('type') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Dynamic names --}}
            <div x-data="{ names: {{ json_encode(old('names', [''])) }} }">
                <label class="block text-sm font-medium text-gray-700 mb-1">Namen * <span class="text-gray-400 font-normal">(mind. 1)</span></label>
                <template x-for="(name, index) in names" :key="index">
                    <div class="flex gap-2 mb-2">
                        <input type="text" :name="'names[' + index + ']'" x-model="names[index]" :placeholder="index === 0 ? 'Primärer Name *' : 'Alias-Name'" :required="index === 0" class="flex-1 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" x-show="names.length > 1" @click="names.splice(index, 1)" class="px-2 text-red-400 hover:text-red-600">&times;</button>
                    </div>
                </template>
                <button type="button" @click="names.push('')" class="text-sm text-blue-600 hover:text-blue-800">+ Weiterer Name</button>
                @error('names') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                @error('names.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="biography" class="block text-sm font-medium text-gray-700 mb-1">Biografie</label>
                <textarea name="biography" id="biography" rows="4" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('biography') }}</textarea>
            </div>

            @if($genres->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Genres</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($genres as $genre)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="genre_ids[]" value="{{ $genre->id }}" {{ in_array($genre->id, old('genre_ids', [])) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700">{{ $genre->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            <hr class="border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">Kontaktdaten</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-Mail</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <hr class="border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">Adresse</h3>

            <div>
                <label for="street" class="block text-sm font-medium text-gray-700 mb-1">Strasse</label>
                <input type="text" name="street" id="street" value="{{ old('street') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="zip" class="block text-sm font-medium text-gray-700 mb-1">PLZ</label>
                    <input type="text" name="zip" id="zip" value="{{ old('zip') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ort</label>
                    <input type="text" name="city" id="city" value="{{ old('city') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Land</label>
                    <input type="text" name="country" id="country" value="{{ old('country', 'CH') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <hr class="border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700">Bankverbindung</h3>

            <div>
                <label for="iban" class="block text-sm font-medium text-gray-700 mb-1">IBAN</label>
                <input type="text" name="iban" id="iban" value="{{ old('iban') }}" placeholder="CH00 0000 0000 0000 0000 0" class="w-full rounded-lg border-gray-300 text-sm font-mono focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">Name Bank</label>
                    <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="bic" class="block text-sm font-medium text-gray-700 mb-1">BIC/SWIFT</label>
                    <input type="text" name="bic" id="bic" value="{{ old('bic') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="bank_zip" class="block text-sm font-medium text-gray-700 mb-1">PLZ Bank</label>
                    <input type="text" name="bank_zip" id="bank_zip" value="{{ old('bank_zip') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="bank_city" class="block text-sm font-medium text-gray-700 mb-1">Ort Bank</label>
                    <input type="text" name="bank_city" id="bank_city" value="{{ old('bank_city') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="bank_country" class="block text-sm font-medium text-gray-700 mb-1">Land Bank</label>
                    <input type="text" name="bank_country" id="bank_country" value="{{ old('bank_country') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label for="vat_number" class="block text-sm font-medium text-gray-700 mb-1">UID/MWST-Nr.</label>
                <input type="text" name="vat_number" id="vat_number" value="{{ old('vat_number') }}" placeholder="CHE-000.000.000 MWST" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            {{-- Dynamic websites --}}
            <div x-data="{ websites: {{ json_encode(old('websites', [''])) }} }">
                <label class="block text-sm font-medium text-gray-700 mb-1">Websites</label>
                <template x-for="(url, index) in websites" :key="index">
                    <div class="flex gap-2 mb-2">
                        <input type="url" :name="'websites[' + index + ']'" x-model="websites[index]" placeholder="https://..." class="flex-1 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" x-show="websites.length > 1" @click="websites.splice(index, 1)" class="px-2 text-red-400 hover:text-red-600">&times;</button>
                    </div>
                </template>
                <button type="button" @click="websites.push('')" class="text-sm text-blue-600 hover:text-blue-800">+ Weitere Website</button>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kontakte</label>
                <select name="contact_ids[]" multiple class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" size="5">
                    @foreach($contacts as $contact)
                        <option value="{{ $contact->id }}" {{ in_array($contact->id, old('contact_ids', [])) ? 'selected' : '' }}>{{ $contact->full_name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Ctrl/Cmd gedrückt halten für Mehrfachauswahl</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Projekte</label>
                <select name="project_ids[]" multiple class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" size="4">
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ in_array($project->id, old('project_ids', [])) ? 'selected' : '' }}>{{ $project->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Ctrl/Cmd gedrückt halten für Mehrfachauswahl</p>
            </div>

            @if($tracks->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tracks</label>
                <select name="track_ids[]" multiple class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" size="4">
                    @foreach($tracks as $track)
                        <option value="{{ $track->id }}" {{ in_array($track->id, old('track_ids', [])) ? 'selected' : '' }}>{{ $track->title }}{{ $track->isrc ? ' (' . $track->isrc . ')' : '' }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Ctrl/Cmd gedrückt halten für Mehrfachauswahl</p>
            </div>
            @endif

            @if($releases->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Releases</label>
                <select name="release_ids[]" multiple class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" size="4">
                    @foreach($releases as $release)
                        <option value="{{ $release->id }}" {{ in_array($release->id, old('release_ids', [])) ? 'selected' : '' }}>{{ $release->title }}{{ $release->upc ? ' (' . $release->upc . ')' : '' }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Ctrl/Cmd gedrückt halten für Mehrfachauswahl</p>
            </div>
            @endif

            @if($contracts->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Verträge</label>
                <select name="contract_ids[]" multiple class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" size="4">
                    @foreach($contracts as $contract)
                        <option value="{{ $contract->id }}" {{ in_array($contract->id, old('contract_ids', [])) ? 'selected' : '' }}>{{ $contract->title }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Ctrl/Cmd gedrückt halten für Mehrfachauswahl</p>
            </div>
            @endif

            <div>
                <label for="document" class="block text-sm font-medium text-gray-700 mb-1">Dokument hochladen</label>
                <input type="file" name="document" id="document" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <input type="text" name="document_notes" value="{{ old('document_notes') }}" placeholder="Notiz zum Dokument (optional)" class="w-full mt-2 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Organisation erstellen</button>
            <a href="{{ route('admin.organizations.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
