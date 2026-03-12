@extends('admin.layouts.app')

@section('title', 'Kontakt bearbeiten')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.contacts.update', $contact) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Avatar</label>
                @if($contact->avatar_path)
                    <div class="flex items-center gap-3 mb-2">
                        <img src="{{ Storage::url($contact->avatar_path) }}" alt="Avatar" class="h-16 w-16 rounded-full object-cover border">
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
                    <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vorname *</label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $contact->first_name) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('first_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nachname *</label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $contact->last_name) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('last_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="gender" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Geschlecht</label>
                    <select name="gender" id="gender" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">— Bitte wählen —</option>
                        <option value="male" {{ old('gender', $contact->gender) === 'male' ? 'selected' : '' }}>Männlich</option>
                        <option value="female" {{ old('gender', $contact->gender) === 'female' ? 'selected' : '' }}>Weiblich</option>
                        <option value="other" {{ old('gender', $contact->gender) === 'other' ? 'selected' : '' }}>Nicht definiert</option>
                    </select>
                </div>
                <div>
                    <label for="birth_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Geburtsdatum</label>
                    <input type="date" name="birth_date" id="birth_date" value="{{ old('birth_date', $contact->birth_date?->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('birth_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="death_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Todesdatum</label>
                    <input type="date" name="death_date" id="death_date" value="{{ old('death_date', $contact->death_date?->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('death_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    @include('admin.partials.country-select', ['name' => 'nationality', 'label' => 'Nationalität', 'value' => old('nationality', $contact->nationality ?? '')])
                </div>
                <div>
                    <label for="ahv_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">AHV-Nr.</label>
                    <input type="text" name="ahv_number" id="ahv_number" value="{{ old('ahv_number', $contact->ahv_number) }}" placeholder="756.XXXX.XXXX.XX"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm font-mono focus:border-blue-500 focus:ring-blue-500"
                        x-data x-mask="756.9999.9999.99">
                    @error('ahv_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Typ: Checkboxen (Mehrfachauswahl) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Typ *</label>
                <div class="flex flex-wrap gap-3">
                    @foreach($contactTypes as $ct)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="types[]" value="{{ $ct->slug }}"
                                {{ in_array($ct->slug, old('types', $contact->types ?? [])) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">{{ $ct->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('types') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- E-Mail: Primär + Sekundäre --}}
            <div x-data="{ emails: {{ json_encode(old('secondary_emails', $contact->secondary_emails ?? [])) }} }">
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-Mail</label>
                <input type="email" name="email" id="email" value="{{ old('email', $contact->email) }}" placeholder="Primäre E-Mail" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                <template x-for="(email, index) in emails" :key="index">
                    <div class="flex gap-2 mt-2">
                        <input type="email" :name="'secondary_emails[' + index + ']'" x-model="emails[index]" placeholder="Weitere E-Mail" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" @click="emails.splice(index, 1)" class="px-2 py-1 text-red-500 hover:text-red-700 text-lg" title="Entfernen">&times;</button>
                    </div>
                </template>
                <button type="button" @click="emails.push('')" class="mt-2 inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    E-Mail hinzufügen
                </button>
            </div>

            {{-- Telefon: Primär + Sekundäre --}}
            <div x-data="{ phones: {{ json_encode(old('secondary_phones', $contact->secondary_phones ?? [])) }} }">
                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $contact->phone) }}" placeholder="Primäre Telefonnummer" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                <template x-for="(phone, index) in phones" :key="index">
                    <div class="flex gap-2 mt-2">
                        <input type="text" :name="'secondary_phones[' + index + ']'" x-model="phones[index]" placeholder="Weitere Telefonnummer" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" @click="phones.splice(index, 1)" class="px-2 py-1 text-red-500 hover:text-red-700 text-lg" title="Entfernen">&times;</button>
                    </div>
                </template>
                <button type="button" @click="phones.push('')" class="mt-2 inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Telefon hinzufügen
                </button>
            </div>

            <hr class="border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Adresse</h3>

            <div>
                <label for="street" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Strasse</label>
                <input type="text" name="street" id="street" value="{{ old('street', $contact->street) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @include('admin.partials.postal-code-input', [
                    'zipName' => 'zip', 'cityName' => 'city',
                    'zipValue' => old('zip', $contact->zip), 'cityValue' => old('city', $contact->city),
                ])
                <div>
                    @include('admin.partials.country-select', ['name' => 'country', 'label' => 'Land', 'value' => old('country', $contact->country ?? '')])
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Bankverbindung</h3>

            <div>
                <label for="iban" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IBAN</label>
                <input type="text" name="iban" id="iban" value="{{ old('iban', $contact->iban) }}" placeholder="CH00 0000 0000 0000 0000 0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm font-mono focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="bank_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name Bank</label>
                    <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $contact->bank_name) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="bic" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">BIC/SWIFT</label>
                    <input type="text" name="bic" id="bic" value="{{ old('bic', $contact->bic) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @include('admin.partials.postal-code-input', [
                    'zipName' => 'bank_zip', 'cityName' => 'bank_city',
                    'zipValue' => old('bank_zip', $contact->bank_zip), 'cityValue' => old('bank_city', $contact->bank_city),
                    'zipLabel' => 'PLZ Bank', 'cityLabel' => 'Ort Bank',
                    'zipId' => 'bank_zip', 'cityId' => 'bank_city',
                ])
                <div>
                    @include('admin.partials.country-select', ['name' => 'bank_country', 'id' => 'bank_country', 'label' => 'Land Bank', 'value' => old('bank_country', $contact->bank_country ?? '')])
                </div>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notizen</label>
                <textarea name="notes" id="notes" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $contact->notes) }}</textarea>
            </div>

            {{-- IPI --}}
            @php
                $existingIpis = old('ipis', $contact->ipis ?? []);
            @endphp
            <div x-data="{ ipis: {{ json_encode(array_values($existingIpis)) }}.length ? {{ json_encode(array_values($existingIpis)) }} : [] }">
                <hr class="border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">IPI <span class="text-gray-400 font-normal">(Interested Parties Information)</span></h3>
                </div>
                <template x-for="(ipi, index) in ipis" :key="index">
                    <div class="flex gap-2 mt-2 items-center">
                        <input type="text" :name="'ipis[' + index + '][number]'" x-model="ipi.number" placeholder="IPI-Nr." class="w-36 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <input type="text" :name="'ipis[' + index + '][name]'" x-model="ipi.name" placeholder="IPI Name" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <label class="inline-flex items-center gap-1 text-xs text-gray-500 whitespace-nowrap">
                            <input type="radio" :name="'ipis_primary'" :value="index" :checked="ipi.primary" @change="ipis.forEach((item, i) => item.primary = i === index)" class="text-blue-600 focus:ring-blue-500">
                            <input type="hidden" :name="'ipis[' + index + '][primary]'" :value="ipi.primary ? '1' : '0'">
                            Primär
                        </label>
                        <button type="button" @click="ipis.splice(index, 1)" class="px-2 py-1 text-red-500 hover:text-red-700 text-lg" title="Entfernen">&times;</button>
                    </div>
                </template>
                <button type="button" @click="ipis.push({number: '', name: '', primary: ipis.length === 0})" class="mt-2 inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    IPI hinzufügen
                </button>
            </div>

            @if($tags->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tags</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="tags[]" value="{{ $tag->id }}" {{ $contact->tags->contains($tag->id) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">{{ $tag->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            @if($genres->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Genres</label>
                @php $selectedGenres = old('genre_ids', $contact->genres->pluck('id')->toArray()); @endphp
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

            {{-- Projekte --}}
            @if($projects->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Projekte</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($projects as $project)
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="project_ids[]" value="{{ $project->id }}"
                                {{ $contact->projects->contains($project->id) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">{{ $project->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Organisationen --}}
            @include('admin.partials.organization-search', ['selected' => $contact->organizations])
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Kontakt aktualisieren</button>
            <a href="{{ route('admin.contacts.show', $contact) }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>

    <!-- Delete -->
    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}" onsubmit="return confirm('Kontakt wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Kontakt löschen</button>
        </form>
    </div>
</div>
@endsection
