@extends('admin.layouts.app')

@section('title', 'Track bearbeiten')

@section('content')
<div class="max-w-4xl">
    <form method="POST" action="{{ route('admin.tracks.update', $track) }}" enctype="multipart/form-data" data-turbo="false">
        @csrf
        @method('PUT')

        {{-- Metadaten einfügen --}}
        <div x-data="pasteMetadata()" x-show="hasClipboard" class="mb-4">
            <button type="button" @click="paste()" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span x-show="!pasted">Metadaten einfügen</span>
                <span x-show="pasted" x-cloak>Eingefügt!</span>
            </button>
            <span class="text-xs text-gray-400 ml-2">Aus Zwischenablage</span>
        </div>

        {{-- Grunddaten (immer offen) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Grunddaten</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titel *</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $track->title) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Version</label>
                    <input type="text" name="version" id="version" value="{{ old('version', $track->version) }}" placeholder="z.B. Radio Edit, Remix" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('version') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="isrc" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ISRC</label>
                    <input type="text" name="isrc" id="isrc" value="{{ old('isrc', $track->isrc) }}" placeholder="CC-XXX-YY-NNNNN" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('isrc') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                    <select name="status" id="status" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(['draft' => 'Draft', 'released' => 'Released', 'archived' => 'Archived'] as $value => $label)
                            <option value="{{ $value }}" {{ old('status', $track->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="genre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Genre</label>
                    <input type="text" name="genre" id="genre" value="{{ old('genre', $track->genre) }}" list="genre-list" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <datalist id="genre-list">
                        @foreach($genres as $genre)
                            <option value="{{ $genre->name }}">
                        @endforeach
                    </datalist>
                    @error('genre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="duration_seconds" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dauer (Sekunden)</label>
                    <input type="number" name="duration_seconds" id="duration_seconds" value="{{ old('duration_seconds', $track->duration_seconds) }}" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('duration_seconds') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Audio --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mt-6" x-data="{ open: {{ $track->audio_file_path ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Audio</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="px-6 pb-6">
                    @if($track->audio_file_path)
                        <div class="flex items-center gap-3 mb-3">
                            <button type="button"
                                    @click="$dispatch('play-track', { title: '{{ addslashes($track->display_title) }}', artist: '{{ addslashes($track->organizations->where("type", "band")->pluck("primary_name")->join(", ")) }}', url: '{{ Storage::url($track->audio_file_path) }}' })"
                                    class="w-8 h-8 flex items-center justify-center rounded-full bg-blue-600 hover:bg-blue-700 text-white transition flex-shrink-0">
                                <svg class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </button>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ basename($track->audio_file_path) }}</p>
                        </div>
                    @endif
                    <input type="file" name="audio_file" id="audio_file" accept="audio/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                    @error('audio_file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Band --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mt-6" x-data="{ open: {{ $track->organizations->where('type', 'band')->count() ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Band</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="px-6 pb-6">
                    @include('admin.partials.organization-search', ['selected' => $track->organizations->where('type', 'band'), 'inputName' => 'band_ids[]', 'orgSearchLabel' => 'Band', 'orgTypeFilter' => 'band'])
                </div>
            </div>
        </div>

        {{-- Credits --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mt-6" x-data="{ open: {{ $track->contacts->count() ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Credits</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="px-6 pb-6">
                    @include('admin.partials.music-credits', ['selectedCredits' => $track->contacts])
                </div>
            </div>
        </div>

        {{-- Label --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mt-6" x-data="{ open: {{ $track->organizations->where('type', 'label')->count() ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Label</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="px-6 pb-6">
                    @include('admin.partials.organization-search', ['selected' => $track->organizations->where('type', 'label'), 'inputName' => 'label_ids[]', 'orgSearchLabel' => 'Label', 'orgTypeFilter' => 'label'])
                </div>
            </div>
        </div>

        {{-- Publisher --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mt-6" x-data="{ open: {{ $track->organizations->where('type', 'publishing')->count() ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Publisher</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="px-6 pb-6">
                    @include('admin.partials.organization-search', ['selected' => $track->organizations->where('type', 'publishing'), 'inputName' => 'publisher_ids[]', 'orgSearchLabel' => 'Publisher', 'orgTypeFilter' => 'publishing'])
                </div>
            </div>
        </div>

        {{-- Releases --}}
        @php
            $existingReleases = old('releases', $track->releases->map(function ($r) {
                return [
                    'release_id' => (string) $r->id,
                    'track_number' => $r->pivot->track_number,
                    'disc_number' => $r->pivot->disc_number ?? 1,
                    'role' => $r->pivot->role ?? 'main',
                ];
            })->toArray());
        @endphp
        <div x-data="{
            open: {{ $track->releases->count() ? 'true' : 'false' }},
            rows: @json(array_values($existingReleases)),
            addRow() { this.rows.push({ release_id: '', track_number: '', disc_number: 1, role: 'main' }); },
            removeRow(index) { this.rows.splice(index, 1); }
        }" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mt-6">
            {{-- Hidden inputs OUTSIDE collapse, always in DOM --}}
            <input type="hidden" name="release_ids_submitted" value="1">
            <template x-for="(row, index) in rows" :key="'hidden-'+index">
                <div>
                    <input type="hidden" :name="'release_ids[' + index + ']'" :value="row.release_id">
                    <input type="hidden" :name="'release_track_numbers[' + index + ']'" :value="row.track_number">
                    <input type="hidden" :name="'release_disc_numbers[' + index + ']'" :value="row.disc_number">
                    <input type="hidden" :name="'release_roles[' + index + ']'" :value="row.role">
                </div>
            </template>

            <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Produkte</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="px-6 pb-6">
                        <template x-for="(row, index) in rows" :key="'ui-'+index">
                            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 mb-3 items-end">
                                <div class="sm:col-span-2">
                                    <label x-show="index === 0" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Produkt</label>
                                    <select x-model="row.release_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">-- Produkt wählen --</option>
                                        @foreach($releases as $release)
                                            <option value="{{ $release->id }}">{{ $release->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label x-show="index === 0" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Track-Nr.</label>
                                    <input type="number" x-model="row.track_number" min="1" placeholder="#" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label x-show="index === 0" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Disc-Nr.</label>
                                    <input type="number" x-model="row.disc_number" min="1" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div class="flex gap-2 items-end">
                                    <div class="flex-1">
                                        <label x-show="index === 0" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rolle</label>
                                        <select x-model="row.role" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="main">Main</option>
                                            <option value="bonus_track">Bonus Track</option>
                                            <option value="hidden_track">Hidden Track</option>
                                            <option value="remix">Remix</option>
                                        </select>
                                    </div>
                                    <button type="button" @click="removeRow(index)" class="px-2 py-2 text-red-600 hover:text-red-800 dark:text-red-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <button type="button" @click="addRow()" class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Produkt hinzufügen
                        </button>
                </div>
            </div>
        </div>

        {{-- Projekte --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mt-6" x-data="{ open: {{ $track->projects->count() ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Projekte</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="px-6 pb-6">
                    <div class="max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-3 space-y-2">
                        @foreach($projects as $project)
                            <label class="flex items-center">
                                <input type="checkbox" name="project_ids[]" value="{{ $project->id }}" {{ in_array($project->id, old('project_ids', $track->projects->pluck('id')->toArray())) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $project->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @if($projects->isEmpty())
                        <p class="text-sm text-gray-400">Keine Projekte vorhanden.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Verträge --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mt-6" x-data="{ open: {{ $track->contracts->count() ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Verträge</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="px-6 pb-6">
                    <div class="max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-3 space-y-2">
                        @foreach($contracts as $contract)
                            <label class="flex items-center">
                                <input type="checkbox" name="contract_ids[]" value="{{ $contract->id }}" {{ in_array($contract->id, old('contract_ids', $track->contracts->pluck('id')->toArray())) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $contract->title }}</span>
                            </label>
                        @endforeach
                    </div>
                    @if($contracts->isEmpty())
                        <p class="text-sm text-gray-400">Keine Verträge vorhanden.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Notizen --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mt-6" x-data="{ open: {{ $track->description ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Notizen</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="px-6 pb-6">
                    <textarea name="description" id="description" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $track->description) }}</textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Musik-Details (am Ende, zugeklappt) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mt-6" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Musik-Details</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="px-6 pb-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="bpm" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">BPM</label>
                            <input type="number" name="bpm" id="bpm" value="{{ old('bpm', $track->bpm) }}" min="20" max="300" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('bpm') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="musical_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tonart</label>
                            <select name="musical_key" id="musical_key" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">-- Keine --</option>
                                @foreach(\App\Models\Track::MUSICAL_KEYS as $key)
                                    <option value="{{ $key }}" {{ old('musical_key', $track->musical_key) === $key ? 'selected' : '' }}>{{ $key }}</option>
                                @endforeach
                            </select>
                            @error('musical_key') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sprache</label>
                            <select name="language" id="language" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">-- Keine --</option>
                                @foreach(['de' => 'Deutsch', 'en' => 'English', 'fr' => 'Français', 'it' => 'Italiano', 'es' => 'Español', 'pt' => 'Português', 'ja' => '日本語', 'ko' => '한국어', 'zh' => '中文'] as $code => $name)
                                    <option value="{{ $code }}" {{ old('language', $track->language) === $code ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('language') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Track aktualisieren</button>
            <a href="{{ route('admin.tracks.show', $track) }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>

    {{-- Delete --}}
    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <form method="POST" action="{{ route('admin.tracks.destroy', $track) }}" onsubmit="return confirm('Track wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Track löschen</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function pasteMetadata() {
    return {
        hasClipboard: !!localStorage.getItem('tyl_track_metadata'),
        pasted: false,

        setField(id, value) {
            const el = document.getElementById(id);
            if (el && value !== undefined && value !== null) {
                el.value = value;
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },

        paste() {
            const raw = localStorage.getItem('tyl_track_metadata');
            if (!raw) return;
            const data = JSON.parse(raw);

            if (data.title) this.setField('title', data.title);
            if (data.version) this.setField('version', data.version);
            if (data.status) this.setField('status', data.status);
            if (data.genre) this.setField('genre', data.genre);
            if (data.duration_seconds) this.setField('duration_seconds', data.duration_seconds);
            if (data.language) this.setField('language', data.language);
            if (data.bpm) this.setField('bpm', data.bpm);
            if (data.musical_key) this.setField('musical_key', data.musical_key);
            if (data.description) this.setField('description', data.description);

            // Band/Label/Publisher via Alpine events
            if (data.bands?.length) {
                window.dispatchEvent(new CustomEvent('paste-orgs-bandids', {
                    detail: data.bands.map(b => ({ id: b.id, primary_name: b.name, type: 'band' }))
                }));
            }
            if (data.labels?.length) {
                window.dispatchEvent(new CustomEvent('paste-orgs-labelids', {
                    detail: data.labels.map(l => ({ id: l.id, primary_name: l.name, type: 'label' }))
                }));
            }
            if (data.publishers?.length) {
                window.dispatchEvent(new CustomEvent('paste-orgs-publisherids', {
                    detail: data.publishers.map(p => ({ id: p.id, primary_name: p.name, type: 'publishing' }))
                }));
            }

            // Credits via Alpine event
            if (data.credits?.length) {
                window.dispatchEvent(new CustomEvent('paste-credits', { detail: data.credits }));
            }

            this.checkItems('project_ids[]', (data.projects || []).map(p => p.id));
            this.checkItems('contract_ids[]', (data.contracts || []).map(c => c.id));

            this.pasted = true;
            setTimeout(() => this.pasted = false, 2000);
        },

        checkItems(name, ids) {
            document.querySelectorAll(`input[name="${name}"]`).forEach(cb => {
                if (ids.includes(parseInt(cb.value))) {
                    cb.checked = true;
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
    };
}
</script>
@endpush
