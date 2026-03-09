@extends('admin.layouts.app')

@section('title', 'Neuer Kontakt')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.contacts.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
            <div>
                <label for="avatar" class="block text-sm font-medium text-gray-700 mb-1">Avatar</label>
                <input type="file" name="avatar" id="avatar" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('avatar') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">Vorname *</label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('first_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Nachname *</label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('last_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">Geburtsdatum</label>
                    <input type="date" name="birth_date" id="birth_date" value="{{ old('birth_date') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('birth_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="death_date" class="block text-sm font-medium text-gray-700 mb-1">Todesdatum</label>
                    <input type="date" name="death_date" id="death_date" value="{{ old('death_date') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('death_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Typ: Checkboxen (Mehrfachauswahl) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Typ *</label>
                <div class="flex flex-wrap gap-3">
                    @foreach($contactTypes as $ct)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="types[]" value="{{ $ct->slug }}"
                                {{ in_array($ct->slug, old('types', [])) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700">{{ $ct->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('types') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- E-Mail: Primär + Sekundäre --}}
            <div x-data="{ emails: {{ json_encode(old('secondary_emails', [])) }} }">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-Mail</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="Primäre E-Mail" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                <template x-for="(email, index) in emails" :key="index">
                    <div class="flex gap-2 mt-2">
                        <input type="email" :name="'secondary_emails[' + index + ']'" x-model="emails[index]" placeholder="Weitere E-Mail" class="flex-1 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" @click="emails.splice(index, 1)" class="px-2 py-1 text-red-500 hover:text-red-700 text-lg" title="Entfernen">&times;</button>
                    </div>
                </template>
                <button type="button" @click="emails.push('')" class="mt-2 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    E-Mail hinzufügen
                </button>
            </div>

            {{-- Telefon: Primär + Sekundäre --}}
            <div x-data="{ phones: {{ json_encode(old('secondary_phones', [])) }} }">
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="Primäre Telefonnummer" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                <template x-for="(phone, index) in phones" :key="index">
                    <div class="flex gap-2 mt-2">
                        <input type="text" :name="'secondary_phones[' + index + ']'" x-model="phones[index]" placeholder="Weitere Telefonnummer" class="flex-1 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" @click="phones.splice(index, 1)" class="px-2 py-1 text-red-500 hover:text-red-700 text-lg" title="Entfernen">&times;</button>
                    </div>
                </template>
                <button type="button" @click="phones.push('')" class="mt-2 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Telefon hinzufügen
                </button>
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
                    <input type="text" name="country" id="country" value="{{ old('country', 'Schweiz') }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
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
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notizen</label>
                <textarea name="notes" id="notes" rows="3" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
            </div>

            {{-- IPI --}}
            <div x-data="{ ipis: {{ json_encode(old('ipis', [])) }}.length ? {{ json_encode(old('ipis', [])) }} : [] }">
                <hr class="border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">IPI <span class="text-gray-400 font-normal">(Interested Parties Information)</span></h3>
                </div>
                <template x-for="(ipi, index) in ipis" :key="index">
                    <div class="flex gap-2 mt-2 items-center">
                        <input type="text" :name="'ipis[' + index + '][number]'" x-model="ipi.number" placeholder="IPI-Nr." class="w-36 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <input type="text" :name="'ipis[' + index + '][name]'" x-model="ipi.name" placeholder="IPI Name" class="flex-1 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <label class="inline-flex items-center gap-1 text-xs text-gray-500 whitespace-nowrap">
                            <input type="radio" :name="'ipis_primary'" :value="index" :checked="ipi.primary" @change="ipis.forEach((item, i) => item.primary = i === index)" class="text-blue-600 focus:ring-blue-500">
                            <input type="hidden" :name="'ipis[' + index + '][primary]'" :value="ipi.primary ? '1' : '0'">
                            Primär
                        </label>
                        <button type="button" @click="ipis.splice(index, 1)" class="px-2 py-1 text-red-500 hover:text-red-700 text-lg" title="Entfernen">&times;</button>
                    </div>
                </template>
                <button type="button" @click="ipis.push({number: '', name: '', primary: ipis.length === 0})" class="mt-2 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    IPI hinzufügen
                </button>
            </div>

            @if($tags->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="tags[]" value="{{ $tag->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700">{{ $tag->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

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

            {{-- Projekte --}}
            @if($projects->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Projekte</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($projects as $project)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="project_ids[]" value="{{ $project->id }}"
                                {{ in_array($project->id, old('project_ids', [])) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700">{{ $project->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Organisationen --}}
            @include('admin.partials.organization-search', ['selected' => collect()])
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Kontakt erstellen</button>
            <a href="{{ route('admin.contacts.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Abbrechen</a>
        </div>
    </form>
</div>
@endsection
