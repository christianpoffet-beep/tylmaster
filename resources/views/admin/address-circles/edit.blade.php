@extends('admin.layouts.app')

@section('title', 'Adresskreis: ' . $addressCircle->name)

@php
    $contactMembersJson = $addressCircle->contactMembers->map(fn($c) => [
        'id' => $c->id, 'name' => $c->full_name, 'email' => $c->email,
        'email_override' => $c->pivot->email_override,
        'genres' => $c->genres->pluck('name')->implode(', '),
        'city' => $c->city, 'zip' => $c->zip,
        'country' => $c->country, 'gender' => $c->gender,
    ]);
    $orgMembersJson = $addressCircle->organizationMembers->map(fn($o) => [
        'id' => $o->id, 'name' => $o->primary_name, 'email' => $o->email,
        'email_override' => $o->pivot->email_override,
        'genres' => $o->genres->pluck('name')->implode(', '),
        'city' => $o->city, 'zip' => $o->zip,
        'country' => $o->country, 'gender' => null,
    ]);
@endphp

@section('content')
<div x-data="addressCircleEditor()" x-init="init()">
    {{-- Tabs --}}
    <div class="flex items-center gap-4 mb-6 border-b border-gray-200 dark:border-gray-700">
        <button @click="tab = 'details'" :class="tab === 'details' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                class="pb-3 text-sm font-medium border-b-2 transition-colors">Details</button>
        <button @click="tab = 'members'" :class="tab === 'members' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                class="pb-3 text-sm font-medium border-b-2 transition-colors">
            Mitglieder
            <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300"
                  x-text="existingContacts.length + existingOrganizations.length"></span>
        </button>
        <button @click="tab = 'filter'" :class="tab === 'filter' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                class="pb-3 text-sm font-medium border-b-2 transition-colors">Filter & Hinzufügen</button>

        <div class="ml-auto flex gap-2">
            <a href="{{ route('admin.address-circles.export', $addressCircle) }}"
               class="px-3 py-1.5 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700">CSV Export</a>
            <form method="POST" action="{{ route('admin.address-circles.destroy', $addressCircle) }}" onsubmit="return confirm('Adresskreis wirklich löschen?')">
                @csrf @method('DELETE')
                <button type="submit" class="px-3 py-1.5 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700">Löschen</button>
            </form>
        </div>
    </div>

    {{-- Tab: Details --}}
    <div x-show="tab === 'details'" x-cloak>
        <form method="POST" action="{{ route('admin.address-circles.update', $addressCircle) }}" class="max-w-3xl">
            @csrf @method('PUT')

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $addressCircle->name) }}" required
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="info" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Beschreibung *</label>
                    <textarea name="info" id="info" rows="3" required
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('info', $addressCircle->info) }}</textarea>
                    @error('info') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Verknüpfte Organisationen --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Verknüpfte Organisationen</label>
                    @include('admin.partials.organization-search', ['fieldName' => 'organization_ids', 'multiple' => true, 'selectedItems' => $addressCircle->organizations])
                </div>

                {{-- Verknüpfte Projekte --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Verknüpfte Projekte</label>
                    @include('admin.partials.project-search', ['fieldName' => 'project_ids', 'multiple' => true, 'selectedItems' => $addressCircle->projects])
                </div>
            </div>

            <div class="mt-6 flex items-center gap-4">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Speichern</button>
                <a href="{{ route('admin.address-circles.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Zurück</a>
            </div>
        </form>
    </div>

    {{-- Tab: Mitglieder --}}
    <div x-show="tab === 'members'" x-cloak>
        {{-- Kontakte --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Kontakte <span class="text-gray-400" x-text="'(' + existingContacts.length + ')'"></span>
                </h3>
            </div>
            <template x-if="existingContacts.length === 0">
                <p class="text-sm text-gray-400 italic p-4">Noch keine Kontakte hinzugefügt.</p>
            </template>
            <template x-if="existingContacts.length > 0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">E-Mail (Adresskreis)</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Genre</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ort</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Land</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="member in existingContacts" :key="'ct-' + member.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <a :href="'/admin/contacts/' + member.id + '/edit'" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline" x-text="member.name"></a>
                                            <template x-if="member.gender">
                                                <span class="text-[10px] px-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-400" x-text="member.gender === 'm' ? 'M' : member.gender === 'f' ? 'W' : 'D'"></span>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <template x-if="member._editingEmail">
                                            <div class="flex items-center gap-1">
                                                <input type="email" x-model="member._emailDraft" :placeholder="member.email || 'E-Mail'"
                                                       class="w-48 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs py-1 px-2 focus:border-blue-500 focus:ring-blue-500">
                                                <button @click="saveMemberEmail(member, 'contact')" type="button" class="text-xs text-green-600 hover:text-green-800">OK</button>
                                                <button @click="member._editingEmail = false" type="button" class="text-xs text-gray-400 hover:text-gray-600">&times;</button>
                                            </div>
                                        </template>
                                        <template x-if="!member._editingEmail">
                                            <div class="flex items-center gap-1.5 cursor-pointer group" @click="member._editingEmail = true; member._emailDraft = member.email_override || ''">
                                                <span class="text-xs" :class="member.email_override ? 'text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-500 dark:text-gray-400'"
                                                      x-text="member.email_override || member.email || '—'"></span>
                                                <template x-if="member.email_override && member.email">
                                                    <span class="text-[10px] text-gray-400 line-through" x-text="member.email"></span>
                                                </template>
                                                <svg class="w-3 h-3 text-gray-300 group-hover:text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400" x-text="member.genres || '—'"></td>
                                    <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap" x-text="[member.zip, member.city].filter(Boolean).join(' ') || '—'"></td>
                                    <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400" x-text="member.country || '—'"></td>
                                    <td class="px-3 py-2 text-right">
                                        <button @click="removeMember(member.id, 'contact')" type="button" class="text-xs text-red-500 hover:text-red-700">Entfernen</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>

        {{-- Organisationen --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Organisationen <span class="text-gray-400" x-text="'(' + existingOrganizations.length + ')'"></span>
                </h3>
            </div>
            <template x-if="existingOrganizations.length === 0">
                <p class="text-sm text-gray-400 italic p-4">Noch keine Organisationen hinzugefügt.</p>
            </template>
            <template x-if="existingOrganizations.length > 0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">E-Mail (Adresskreis)</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Genre</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ort</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Land</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="member in existingOrganizations" :key="'ot-' + member.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <a :href="'/admin/organizations/' + member.id + '/edit'" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline" x-text="member.name"></a>
                                    </td>
                                    <td class="px-3 py-2">
                                        <template x-if="member._editingEmail">
                                            <div class="flex items-center gap-1">
                                                <input type="email" x-model="member._emailDraft" :placeholder="member.email || 'E-Mail'"
                                                       class="w-48 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs py-1 px-2 focus:border-blue-500 focus:ring-blue-500">
                                                <button @click="saveMemberEmail(member, 'organization')" type="button" class="text-xs text-green-600 hover:text-green-800">OK</button>
                                                <button @click="member._editingEmail = false" type="button" class="text-xs text-gray-400 hover:text-gray-600">&times;</button>
                                            </div>
                                        </template>
                                        <template x-if="!member._editingEmail">
                                            <div class="flex items-center gap-1.5 cursor-pointer group" @click="member._editingEmail = true; member._emailDraft = member.email_override || ''">
                                                <span class="text-xs" :class="member.email_override ? 'text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-500 dark:text-gray-400'"
                                                      x-text="member.email_override || member.email || '—'"></span>
                                                <template x-if="member.email_override && member.email">
                                                    <span class="text-[10px] text-gray-400 line-through" x-text="member.email"></span>
                                                </template>
                                                <svg class="w-3 h-3 text-gray-300 group-hover:text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400" x-text="member.genres || '—'"></td>
                                    <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap" x-text="[member.zip, member.city].filter(Boolean).join(' ') || '—'"></td>
                                    <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400" x-text="member.country || '—'"></td>
                                    <td class="px-3 py-2 text-right">
                                        <button @click="removeMember(member.id, 'organization')" type="button" class="text-xs text-red-500 hover:text-red-700">Entfernen</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>

    {{-- Tab: Filter & Hinzufügen --}}
    <div x-show="tab === 'filter'" x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Filter Panel --}}
            <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Filter</h3>

                {{-- Filter Type Toggle --}}
                <div class="flex gap-2 mb-4">
                    <button @click="filterType = 'contact'" type="button"
                            :class="filterType === 'contact' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                            class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors">Kontakte</button>
                    <button @click="filterType = 'organization'" type="button"
                            :class="filterType === 'organization' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                            class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors">Organisationen</button>
                </div>

                <div class="space-y-3">
                    {{-- Contact Filters --}}
                    <template x-if="filterType === 'contact'">
                        <div class="space-y-3">
                            <input x-model="filters.f_first_name" type="text" placeholder="Vorname"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                            <input x-model="filters.f_last_name" type="text" placeholder="Nachname"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                            <input x-model="filters.f_email" type="text" placeholder="E-Mail"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                            <div class="grid grid-cols-2 gap-2">
                                <input x-model="filters.f_zip" type="text" placeholder="PLZ"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                                <input x-model="filters.f_city" type="text" placeholder="Ort"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <select x-model="filters.f_country"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Land</option>
                                <option value="CH">Schweiz</option>
                                <option value="DE">Deutschland</option>
                                <option value="AT">Österreich</option>
                                <option value="FR">Frankreich</option>
                                <option value="IT">Italien</option>
                                <option value="GB">Vereinigtes Königreich</option>
                                <option value="US">Vereinigte Staaten</option>
                            </select>
                            <select x-model="filters.f_gender"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Geschlecht</option>
                                <option value="m">Männlich</option>
                                <option value="f">Weiblich</option>
                                <option value="d">Divers</option>
                            </select>
                            <select x-model="filters.f_contact_type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Kontakt-Typ</option>
                                @foreach($contactTypes as $ct)
                                    <option value="{{ $ct->slug }}">{{ $ct->name }}</option>
                                @endforeach
                            </select>
                            <select x-model="filters.f_genre"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Genre</option>
                                @foreach($genres as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                            <input x-model="filters.f_notes" type="text" placeholder="Notizen enthalten..."
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">Geburtsdatum</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input x-model="filters.f_birth_from" type="date"
                                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                                    <input x-model="filters.f_birth_to" type="date"
                                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Organization Filters --}}
                    <template x-if="filterType === 'organization'">
                        <div class="space-y-3">
                            <input x-model="filters.f_org_name" type="text" placeholder="Name"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                            <select x-model="filters.f_org_type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Organisations-Typ</option>
                                @foreach($organizationTypes as $ot)
                                    <option value="{{ $ot->slug }}">{{ $ot->name }}</option>
                                @endforeach
                            </select>
                            <input x-model="filters.f_email" type="text" placeholder="E-Mail"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                            <div class="grid grid-cols-2 gap-2">
                                <input x-model="filters.f_zip" type="text" placeholder="PLZ"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                                <input x-model="filters.f_city" type="text" placeholder="Ort"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <select x-model="filters.f_country"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Land</option>
                                <option value="CH">Schweiz</option>
                                <option value="DE">Deutschland</option>
                                <option value="AT">Österreich</option>
                                <option value="FR">Frankreich</option>
                                <option value="IT">Italien</option>
                                <option value="GB">Vereinigtes Königreich</option>
                                <option value="US">Vereinigte Staaten</option>
                            </select>
                            <select x-model="filters.f_genre"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Genre</option>
                                @foreach($genres as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                            <input x-model="filters.f_org_bio" type="text" placeholder="Biografie enthält..."
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </template>
                </div>

                {{-- Active filter tags --}}
                <div class="mt-3 flex flex-wrap gap-1.5" x-show="activeFilterCount() > 0">
                    <template x-for="tag in activeFilterTags()" :key="tag.key">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs">
                            <span x-text="tag.label"></span>
                            <button @click="clearFilter(tag.key)" type="button" class="hover:text-blue-900 dark:hover:text-blue-100 font-bold">&times;</button>
                        </span>
                    </template>
                </div>

                <div class="mt-4 flex gap-2">
                    <button @click="runFilter()" type="button"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700"
                            :disabled="filtering">
                        <span x-show="!filtering">Suchen</span>
                        <span x-show="filtering">Suche...</span>
                    </button>
                    <button @click="resetFilters()" type="button"
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">Zurücksetzen</button>
                </div>
            </div>

            {{-- Results Panel --}}
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Ergebnisse <span class="text-gray-400" x-text="'(' + filterResults.length + ')'"></span>
                    </h3>
                    <div class="flex gap-2">
                        <template x-if="nonMemberResults().length > 0">
                            <button @click="addAllResults()" type="button"
                                    class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">
                                <span x-text="'Alle ' + nonMemberResults().length + ' hinzufügen'"></span>
                            </button>
                        </template>
                        <template x-if="selectedResults.length > 0">
                            <button @click="addSelected()" type="button"
                                    class="px-3 py-1.5 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700">
                                <span x-text="selectedResults.length + ' ausgewählte hinzufügen'"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <template x-if="filterResults.length === 0 && !filtering">
                    <p class="text-sm text-gray-400 italic py-8 text-center">Verwende die Filter links, um Kontakte oder Organisationen zu finden.</p>
                </template>

                <div class="space-y-1 max-h-[600px] overflow-y-auto">
                    <template x-for="result in filterResults" :key="result.type + '-' + result.id">
                        <div class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50"
                             :class="result.is_member ? 'opacity-50' : ''">
                            <template x-if="!result.is_member">
                                <input type="checkbox" :value="result.id + ':' + result.type"
                                       @change="toggleResult(result)"
                                       :checked="selectedResults.some(r => r.id === result.id && r.type === result.type)"
                                       class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            </template>
                            <template x-if="result.is_member">
                                <span class="text-xs text-green-600 dark:text-green-400 font-medium w-5 text-center">&#10003;</span>
                            </template>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200" x-text="result.name"></span>
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400"
                                          x-text="result.type === 'contact' ? 'Kontakt' : 'Organisation'"></span>
                                </div>
                                <div class="text-xs text-gray-400 flex gap-2">
                                    <span x-text="result.email || ''"></span>
                                    <span x-text="result.city || ''"></span>
                                    <span x-text="result.country || ''"></span>
                                </div>
                            </div>
                            <template x-if="!result.is_member">
                                <button @click="addSingle(result)" type="button"
                                        class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 whitespace-nowrap">+ Hinzufügen</button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Status messages --}}
    <div x-show="statusMessage" x-transition x-cloak
         class="fixed bottom-4 right-4 px-4 py-3 rounded-lg text-sm text-white shadow-lg z-50"
         :class="statusType === 'success' ? 'bg-green-600' : 'bg-red-600'"
         x-text="statusMessage"
         @click="statusMessage = ''"></div>
</div>

@push('scripts')
<script>
function addressCircleEditor() {
    return {
        tab: 'details',
        filterType: 'contact',
        filtering: false,
        filters: {},
        filterResults: [],
        selectedResults: [],
        statusMessage: '',
        statusType: 'success',

        existingContacts: @json($contactMembersJson),
        existingOrganizations: @json($orgMembersJson),

        init() {
            // nothing extra needed
        },

        filterLabels: {
            f_first_name: 'Vorname', f_last_name: 'Nachname', f_email: 'E-Mail',
            f_zip: 'PLZ', f_city: 'Ort', f_country: 'Land', f_gender: 'Geschlecht',
            f_contact_type: 'Kontakt-Typ', f_genre: 'Genre', f_notes: 'Notizen',
            f_birth_from: 'Geb. von', f_birth_to: 'Geb. bis',
            f_org_name: 'Name', f_org_type: 'Org-Typ', f_org_bio: 'Biografie'
        },

        activeFilterCount() {
            return Object.values(this.filters).filter(v => v).length;
        },

        activeFilterTags() {
            return Object.entries(this.filters)
                .filter(([k, v]) => v)
                .map(([k, v]) => ({ key: k, label: (this.filterLabels[k] || k) + ': ' + v }));
        },

        clearFilter(key) {
            this.filters[key] = '';
        },

        nonMemberResults() {
            return this.filterResults.filter(r => !r.is_member);
        },

        resetFilters() {
            this.filters = {};
            this.filterResults = [];
            this.selectedResults = [];
        },

        async runFilter() {
            this.filtering = true;
            this.selectedResults = [];
            const params = new URLSearchParams();
            params.append('filter_type', this.filterType);
            for (const [key, val] of Object.entries(this.filters)) {
                if (val) params.append(key, val);
            }

            try {
                const response = await fetch(`{{ route('admin.address-circles.filter', $addressCircle) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: params.toString()
                });
                this.filterResults = await response.json();
            } catch (e) {
                this.showStatus('Fehler beim Filtern.', 'error');
            }
            this.filtering = false;
        },

        toggleResult(result) {
            const idx = this.selectedResults.findIndex(r => r.id === result.id && r.type === result.type);
            if (idx >= 0) {
                this.selectedResults.splice(idx, 1);
            } else {
                this.selectedResults.push({ id: result.id, type: result.type });
            }
        },

        async addSingle(result) {
            await this.addMembersToServer([{ id: result.id, type: result.type }]);
            result.is_member = true;
            if (result.type === 'contact') {
                this.existingContacts.push({ id: result.id, name: result.name, email: result.email, city: result.city, country: result.country });
            } else {
                this.existingOrganizations.push({ id: result.id, name: result.name, email: result.email, city: result.city, country: result.country });
            }
        },

        async addSelected() {
            await this.addMembersToServer(this.selectedResults);
            // Update local state
            for (const sel of this.selectedResults) {
                const result = this.filterResults.find(r => r.id === sel.id && r.type === sel.type);
                if (result) {
                    result.is_member = true;
                    if (sel.type === 'contact') {
                        if (!this.existingContacts.some(c => c.id === sel.id)) {
                            this.existingContacts.push({ id: sel.id, name: result.name, email: result.email, city: result.city, country: result.country });
                        }
                    } else {
                        if (!this.existingOrganizations.some(o => o.id === sel.id)) {
                            this.existingOrganizations.push({ id: sel.id, name: result.name, email: result.email, city: result.city, country: result.country });
                        }
                    }
                }
            }
            this.selectedResults = [];
        },

        async addAllResults() {
            const toAdd = this.nonMemberResults().map(r => ({ id: r.id, type: r.type }));
            if (toAdd.length === 0) return;
            await this.addMembersToServer(toAdd);
            for (const r of this.filterResults) {
                if (!r.is_member) {
                    r.is_member = true;
                    if (r.type === 'contact') {
                        if (!this.existingContacts.some(c => c.id === r.id)) {
                            this.existingContacts.push({ id: r.id, name: r.name, email: r.email, city: r.city, country: r.country });
                        }
                    } else {
                        if (!this.existingOrganizations.some(o => o.id === r.id)) {
                            this.existingOrganizations.push({ id: r.id, name: r.name, email: r.email, city: r.city, country: r.country });
                        }
                    }
                }
            }
            this.selectedResults = [];
        },

        async addMembersToServer(members) {
            try {
                const response = await fetch(`{{ route('admin.address-circles.add-members', $addressCircle) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ members })
                });
                const data = await response.json();
                this.showStatus(data.message || 'Hinzugefügt.', 'success');
            } catch (e) {
                this.showStatus('Fehler beim Hinzufügen.', 'error');
            }
        },

        async removeMember(id, type) {
            try {
                const response = await fetch(`{{ route('admin.address-circles.remove-members', $addressCircle) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ members: [{ id, type }] })
                });
                const data = await response.json();
                if (type === 'contact') {
                    this.existingContacts = this.existingContacts.filter(c => c.id !== id);
                } else {
                    this.existingOrganizations = this.existingOrganizations.filter(o => o.id !== id);
                }
                // Also update filter results if visible
                const fr = this.filterResults.find(r => r.id === id && r.type === type);
                if (fr) fr.is_member = false;
                this.showStatus(data.message || 'Entfernt.', 'success');
            } catch (e) {
                this.showStatus('Fehler beim Entfernen.', 'error');
            }
        },

        async saveMemberEmail(member, type) {
            try {
                const response = await fetch(`{{ route('admin.address-circles.update-member-email', $addressCircle) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ id: member.id, type, email_override: member._emailDraft || '' })
                });
                const data = await response.json();
                if (data.success) {
                    member.email_override = member._emailDraft || null;
                    member._editingEmail = false;
                    this.showStatus(data.message || 'E-Mail aktualisiert.', 'success');
                } else {
                    this.showStatus('Fehler beim Speichern.', 'error');
                }
            } catch (e) {
                this.showStatus('Fehler beim Speichern.', 'error');
            }
        },

        showStatus(message, type) {
            this.statusMessage = message;
            this.statusType = type;
            setTimeout(() => { this.statusMessage = ''; }, 3000);
        }
    };
}
</script>
@endpush
@endsection
