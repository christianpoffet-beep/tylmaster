@extends('admin.layouts.app')

@section('title', $track->display_title)

@section('content')
<div class="max-w-4xl">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $track->title }}</h2>
                @if($track->version)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $track->version }}</p>
                @endif
                @if($track->alternative_titles_list)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Auch bekannt als: {{ $track->alternative_titles_list }}</p>
                @endif
                <div class="mt-2">
                    @switch($track->status)
                        @case('draft')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Draft</span>
                            @break
                        @case('released')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300">Released</span>
                            @break
                        @case('archived')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300">Archived</span>
                            @break
                    @endswitch
                </div>
            </div>
            <div class="flex gap-2" x-data="copyModal(@js($copyModalConfig))" x-init="init()">
                <button type="button" @click="openModal()" class="px-4 py-2 bg-gray-600 dark:bg-gray-500 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-400 transition">
                    Metadaten kopieren
                </button>
                <a href="{{ route('admin.tracks.edit', $track) }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">Bearbeiten</a>
                <form method="POST" action="{{ route('admin.tracks.destroy', $track) }}" onsubmit="return confirm('Track wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white text-sm rounded-lg hover:bg-red-700 dark:hover:bg-red-600">Löschen</button>
                </form>

                {{-- Copy metadata: 1. choose what to copy, per chapter or item - 2. paste it into other tracks --}}
                <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showModal = false">
                    <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[85vh] flex flex-col text-left" @click.stop>
                        {{-- Header --}}
                        <div class="px-6 pt-5 pb-3 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between gap-4">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100" x-text="step === 1 ? 'Metadaten kopieren' : 'In andere Tracks einfügen'"></h3>
                                <span class="text-xs text-gray-400 whitespace-nowrap" x-text="'Schritt ' + step + ' von 2'"></span>
                            </div>
                            <label x-show="data && step === 1" class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600"
                                       :checked="allState() === 'all'"
                                       x-effect="$el.indeterminate = allState() === 'some'"
                                       @change="toggleAll($event.target.checked)">
                                Alles auswählen
                            </label>
                        </div>

                        {{-- Body --}}
                        <div class="flex-1 overflow-y-auto px-6 py-4">
                            <div x-show="!data" class="py-8 text-center text-gray-400">Laden...</div>

                            {{-- Step 1: what to copy --}}
                            <div x-show="data && step === 1" class="space-y-4 text-sm">
                                <template x-for="section in sections" :key="section.key">
                                    <div>
                                        <label class="flex items-center gap-2 pb-1 mb-1.5 border-b border-gray-100 dark:border-gray-700 font-semibold text-gray-800 dark:text-gray-200"
                                               :class="available(section).length ? 'cursor-pointer' : 'opacity-50'">
                                            <input type="checkbox" class="rounded border-gray-300 text-blue-600"
                                                   :disabled="!available(section).length"
                                                   :checked="sectionState(section) === 'all'"
                                                   x-effect="$el.indeterminate = sectionState(section) === 'some'"
                                                   @change="toggleSection(section, $event.target.checked)">
                                            <span x-text="section.label"></span>
                                            <span class="font-normal text-gray-400" x-show="available(section).length" x-text="chosen(section).length + ' von ' + available(section).length"></span>
                                        </label>
                                        <div class="space-y-1 ml-6">
                                            <template x-for="field in (section.fields || [])" :key="field.key">
                                                <label class="flex items-start gap-2" :class="hasValue(field.key) ? 'cursor-pointer' : 'opacity-50'">
                                                    <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-blue-600" x-model="fields[field.key]" :disabled="!hasValue(field.key)">
                                                    <span><span class="text-gray-800 dark:text-gray-200" x-text="field.label"></span> <span class="text-gray-400" x-text="preview(field.key)"></span></span>
                                                </label>
                                            </template>
                                            <template x-for="item in listItems(section)" :key="section.key + '-' + item.value">
                                                <label class="flex items-start gap-2 cursor-pointer">
                                                    <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-blue-600"
                                                           :checked="selected[section.list].includes(item.value)"
                                                           @change="toggleItem(section.list, item.value, $event.target.checked)">
                                                    <span class="text-gray-800 dark:text-gray-200" x-text="item.label"></span>
                                                </label>
                                            </template>
                                            <p x-show="section.list && !listItems(section).length" class="text-gray-400">Keine</p>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Step 2: which tracks to paste into --}}
                            <div x-show="data && step === 2" class="space-y-3 text-sm">
                                <p class="text-gray-600 dark:text-gray-300">
                                    <span class="font-medium text-gray-800 dark:text-gray-200">Kopiert:</span>
                                    <span x-text="summary() || 'nichts ausgewählt'"></span>
                                    <button type="button" @click="step = 1" class="ml-1 text-blue-600 dark:text-blue-400 hover:underline">Auswahl ändern</button>
                                </p>

                                <div class="flex flex-wrap gap-2">
                                    <input type="search" x-model="search" @input.debounce.300ms="loadTargets()" placeholder="Suchen: Titel, Band, Person, Produkt, ISRC …"
                                           class="flex-1 min-w-[12rem] rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <select x-model="filterBand" @change="loadTargets()" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Alle Bands</option>
                                        <template x-for="band in bands" :key="'b' + band.id"><option :value="band.id" x-text="band.name"></option></template>
                                    </select>
                                    <select x-model="filterRelease" @change="loadTargets()" class="max-w-[14rem] rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Alle Produkte</option>
                                        <template x-for="release in releases" :key="'r' + release.id"><option :value="release.id" x-text="release.title"></option></template>
                                    </select>
                                </div>

                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                    <label class="flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 text-xs font-medium text-gray-600 dark:text-gray-300"
                                           :class="results.length && 'cursor-pointer'">
                                        <input type="checkbox" class="rounded border-gray-300 text-blue-600"
                                               :disabled="!results.length"
                                               :checked="results.length > 0 && results.every(r => isTarget(r.id))"
                                               x-effect="$el.indeterminate = results.some(r => isTarget(r.id)) && !results.every(r => isTarget(r.id))"
                                               @change="toggleAllResults($event.target.checked)">
                                        <span x-text="resultsLabel()"></span>
                                    </label>
                                    <div class="max-h-64 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                                        <template x-for="result in results" :key="'res' + result.id">
                                            <label class="flex items-start gap-3 px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-blue-600" :checked="isTarget(result.id)" @change="toggleTarget(result, $event.target.checked)">
                                                <span class="min-w-0 flex-1">
                                                    <span class="block font-medium text-gray-900 dark:text-gray-100" x-text="result.title"></span>
                                                    <span class="block text-xs text-gray-500 dark:text-gray-400 truncate" x-text="[result.artist, result.products, result.isrc].filter(Boolean).join(' · ')"></span>
                                                </span>
                                                <span class="text-xs text-gray-400" x-text="result.status"></span>
                                            </label>
                                        </template>
                                        <p x-show="!loading && !results.length" class="px-3 py-4 text-center text-gray-400">Keine Tracks gefunden.</p>
                                    </div>
                                </div>

                                <div x-show="targets.length" class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="'Ausgewählt (' + targets.length + '):'"></span>
                                    <template x-for="target in targets" :key="'t' + target.id">
                                        <span class="inline-flex items-center gap-0.5 pl-2 pr-1 py-0.5 rounded-full text-xs bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                            <span x-text="target.title"></span>
                                            <button type="button" @click="toggleTarget(target, false)" class="px-1 hover:text-blue-900 dark:hover:text-white" title="Abwählen">&times;</button>
                                        </span>
                                    </template>
                                </div>

                                <fieldset x-show="selectsLists()" class="space-y-1 pt-1">
                                    <legend class="font-medium text-gray-800 dark:text-gray-200 mb-1">Listen (Band, Credits, Label, Produkte …):</legend>
                                    <label class="flex items-start gap-2 cursor-pointer">
                                        <input type="radio" value="add" x-model="mode" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                        <span><span class="font-medium">Ergänzen</span> <span class="text-gray-500 dark:text-gray-400">– was die Tracks schon haben, bleibt</span></span>
                                    </label>
                                    <label class="flex items-start gap-2 cursor-pointer">
                                        <input type="radio" value="replace" x-model="mode" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                        <span><span class="font-medium">Ersetzen</span> <span class="text-gray-500 dark:text-gray-400">– danach stehen dort nur die kopierten Einträge</span></span>
                                    </label>
                                </fieldset>

                                <p x-show="overwritesTitle()" class="px-3 py-2 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300"
                                   x-text="'Achtung: ' + overwritesTitle() + ' wird in allen ausgewählten Tracks überschrieben.'"></p>
                                <p x-show="selected.releases.length" class="text-xs text-gray-500 dark:text-gray-400">Bei neu verknüpften Produkten bleibt die Track-Nummer leer.</p>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                            <div x-show="step === 1" class="flex flex-wrap items-center gap-3">
                                <button type="button" @click="copyAndContinue()" :disabled="!data" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition disabled:opacity-50">Kopieren &amp; Tracks wählen</button>
                                <button type="button" @click="copyOnly()" :disabled="!data" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition" title="Für den Knopf &quot;Metadaten einfügen&quot; beim Erfassen oder Bearbeiten eines Tracks">Nur kopieren</button>
                                <button type="button" @click="showModal = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100">Abbrechen</button>
                                <span x-show="copied" x-transition class="text-sm text-green-600 dark:text-green-400 ml-auto">Kopiert!</span>
                            </div>
                            <form x-show="step === 2" method="POST" action="{{ route('admin.tracks.applyMetadata', $track) }}" class="flex flex-wrap items-center gap-3"
                                  @submit="if (!confirm(confirmText())) $event.preventDefault()">
                                @csrf
                                <input type="hidden" name="mode" :value="mode">
                                <template x-for="target in targets" :key="'tid' + target.id"><input type="hidden" name="target_ids[]" :value="target.id"></template>
                                <template x-for="field in selectedFields()" :key="'f-' + field"><input type="hidden" name="fields[]" :value="field"></template>
                                <template x-if="fields.alternative_titles && hasValue('alternative_titles')"><input type="hidden" name="alternative_titles" value="1"></template>
                                <template x-for="entry in selectedListEntries()" :key="entry.name + entry.value"><input type="hidden" :name="entry.name" :value="entry.value"></template>
                                <button type="submit" :disabled="!targets.length || !summary()" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                        x-text="targets.length ? 'In ' + targets.length + (targets.length === 1 ? ' Track' : ' Tracks') + ' einfügen' : 'Tracks auswählen'"></button>
                                <button type="button" @click="step = 1" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">Zurück</button>
                                <button type="button" @click="showModal = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100 ml-auto">Schliessen</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Metadata grid --}}
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ISRC</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $track->isrc_formatted ?? $track->isrc ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Genre</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $track->genre ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dauer</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $track->formatted_duration ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">BPM</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $track->bpm ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tonart</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $track->musical_key ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sprache</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    @php
                        $languages = ['de' => 'Deutsch', 'en' => 'English', 'fr' => 'Français', 'it' => 'Italiano', 'es' => 'Español', 'pt' => 'Português', 'ja' => '日本語', 'ko' => '한국어', 'zh' => '中文'];
                    @endphp
                    {{ $languages[$track->language] ?? $track->language ?? '-' }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Aufnahmeort</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $track->recording_location ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Aufnahmejahr(e)</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $track->recording_years ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    {{-- Audio: alle Versionen, Main Version zuoberst --}}
    @if($track->audioFiles->isNotEmpty())
        <x-admin.collapsible-card title="Audiodateien" :count="$track->audioFiles->count()" class="mt-6">
            <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
                @foreach($track->audioFiles as $audioFile)
                    <div class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                        <button type="button" title="Abspielen"
                                @click="$dispatch('play-track', @js($audioFile->playerPayload()))"
                                class="w-9 h-9 flex items-center justify-center rounded-full bg-blue-600 hover:bg-blue-700 text-white transition flex-shrink-0">
                            <svg class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </button>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $audioFile->label }}</span>
                                @if($audioFile->is_main)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">Main Version</span>
                                @endif
                            </div>
                            @if($audioFile->isrc)
                                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300"><span class="text-gray-400 dark:text-gray-500">ISRC</span> <span class="font-mono">{{ $audioFile->isrc_formatted }}</span></p>
                            @endif
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 break-all">
                                @if($audioFile->title){{ $audioFile->original_name ?: basename($audioFile->file_path) }} &middot; @endif{{ $audioFile->technical_summary }}
                            </p>
                            @if($audioFile->notes)
                                <p class="mt-1.5 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $audioFile->notes }}</p>
                            @endif
                        </div>

                        <a href="{{ route('admin.tracks.audioFiles.download', [$track, $audioFile]) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span class="hidden sm:inline">Download</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Releases --}}
    @if($track->releases->count())
        <x-admin.collapsible-card title="Produkte" :count="$track->releases->count()" class="mt-6">
            <div class="space-y-4">
                @foreach($track->releases as $release)
                    <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-start gap-4">
                            {{-- Cover-Bild --}}
                            @php $primaryArtwork = $release->artworks->firstWhere('pivot.is_primary', true) ?? $release->artworks->first(); @endphp
                            @if($release->cover_image_path)
                                <a href="{{ route('admin.releases.show', $release) }}">
                                    <img src="{{ Storage::disk('public')->url($release->cover_image_path) }}" alt="{{ $release->title }}" class="w-20 h-20 object-cover rounded-lg shadow-sm flex-shrink-0">
                                </a>
                            @elseif($primaryArtwork && $primaryArtwork->artwork_url)
                                <a href="{{ route('admin.releases.show', $release) }}">
                                    <img src="{{ $primaryArtwork->artwork_url }}" alt="{{ $release->title }}" class="w-20 h-20 object-cover rounded-lg shadow-sm flex-shrink-0">
                                </a>
                            @endif

                            <div class="flex-1 min-w-0">
                                {{-- Titel + Track-Nr --}}
                                <div class="flex items-center justify-between gap-2">
                                    <a href="{{ route('admin.releases.show', $release) }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">{{ $release->title }}</a>
                                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">
                                        @if($release->pivot->track_number)
                                            <span>Track {{ $release->pivot->track_number }}</span>
                                        @endif
                                        @if($release->pivot->disc_number && $release->pivot->disc_number > 1)
                                            <span>Disc {{ $release->pivot->disc_number }}</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Produkttyp-Badges --}}
                                @php $productTypes = is_array($release->product_type) ? $release->product_type : []; @endphp
                                @if(count($productTypes))
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach($productTypes as $pt)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">{{ $pt }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Metadaten --}}
                                @php
                                    $meta = collect();
                                    if ($release->label_display) $meta->push($release->label_display);
                                    if ($release->catalog_number) $meta->push($release->catalog_number);
                                    if ($release->release_date) $meta->push($release->release_date->format('d.m.Y'));
                                    if ($release->ean_upc) $meta->push($release->ean_upc);
                                @endphp
                                @if($meta->count())
                                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $meta->join(' · ') }}</p>
                                @endif

                                {{-- Artwork-Logos --}}
                                @if($primaryArtwork?->logos->count())
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($primaryArtwork->logos as $logo)
                                            <img src="{{ $logo->url }}" alt="{{ $logo->original_name }}" class="h-8 w-auto rounded bg-gray-100 dark:bg-gray-700 object-contain p-0.5" title="{{ $logo->comment ?? $logo->original_name }}">
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Band --}}
    @php $bands = $track->organizations->where('type', 'band'); @endphp
    @if($bands->count())
        <x-admin.collapsible-card title="Band" :count="$bands->count()" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($bands as $band)
                    <a href="{{ route('admin.organizations.show', $band) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200">{{ $band->primary_name }}</a>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Credits --}}
    @if($track->contacts->count())
        @php
            $roleLabels = collect(\App\Models\Setting::creditRoles())->flatMap(fn($roles) => $roles)->toArray();
        @endphp
        <x-admin.collapsible-card title="Credits" :count="$track->contacts->count()" class="mt-6">
            <div class="space-y-2">
                @foreach($track->contacts as $contact)
                    @php
                        $pivotIpi = $contact->pivot->ipi_number ?? null;
                        $matchedIpi = null;
                        if ($pivotIpi) {
                            $matchedIpi = collect($contact->ipis ?? [])->first(fn ($i) => ($i['number'] ?? null) === $pivotIpi);
                        }
                        $displayName = ($matchedIpi && !empty($matchedIpi['name'])) ? $matchedIpi['name'] : $contact->full_name;
                    @endphp
                    <div class="flex items-center justify-between py-1 gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('admin.contacts.show', $contact) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800">{{ $displayName }}</a>
                                @if($pivotIpi)
                                    <span class="inline-flex items-center gap-1 text-xs" title="{{ $matchedIpi['name'] ?? '' }}">
                                        <span class="px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-mono">IPI</span>
                                        <span class="font-mono text-gray-600 dark:text-gray-300">{{ $pivotIpi }}</span>
                                    </span>
                                @endif
                            </div>
                            @if($pivotIpi && $displayName !== $contact->full_name)
                                <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kontakt: {{ $contact->full_name }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <span class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $roleLabels[$contact->pivot->role] ?? $contact->pivot->role }}</span>
                            @if($contact->pivot->instrument)
                                <span class="text-xs px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300">{{ $contact->pivot->instrument }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Label --}}
    @php $labels = $track->organizations->where('type', 'label'); @endphp
    @if($labels->count())
        <x-admin.collapsible-card title="Label" :count="$labels->count()" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($labels as $label)
                    <a href="{{ route('admin.organizations.show', $label) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200">{{ $label->primary_name }}</a>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Publisher --}}
    @php $publishers = $track->organizations->where('type', 'publishing'); @endphp
    @if($publishers->count())
        <x-admin.collapsible-card title="Publisher" :count="$publishers->count()" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($publishers as $publisher)
                    <a href="{{ route('admin.organizations.show', $publisher) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200">{{ $publisher->primary_name }}</a>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Projekte --}}
    @if($track->projects->count())
        <x-admin.collapsible-card title="Projekte" :count="$track->projects->count()" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($track->projects as $project)
                    <a href="{{ route('admin.projects.show', $project) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200">{{ $project->name }}</a>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Verträge --}}
    @if($track->contracts->count())
        <x-admin.collapsible-card title="Verträge" :count="$track->contracts->count()" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($track->contracts as $contract)
                    <a href="{{ route('admin.contracts.show', $contract) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200">{{ $contract->title }}</a>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Description --}}
    @if($track->description)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Beschreibung</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $track->description }}</p>
        </div>
    @endif

    <div class="mt-4">
        <a href="{{ route('admin.tracks.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-300">&larr; Zurück zur Übersicht</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
/*
 * "Metadaten kopieren": step 1 picks what to copy - single fields, or whole
 * chapters at once. The choice goes to localStorage, where the paste button
 * of the track form picks it up. Step 2 pastes it straight into other tracks,
 * found with the same search as the tracklist.
 */
function copyModal(config) {
    const FIELD_KEYS = ['title', 'version', 'status', 'genre', 'duration_seconds', 'description', 'bpm', 'musical_key', 'language', 'recording_location', 'recording_years'];
    const LISTS = ['bands', 'credits', 'labels', 'publishers', 'releases', 'projects', 'contracts'];

    return {
        showModal: false,
        step: 1,
        data: null,
        copied: false,
        bands: config.bands || [],
        releases: config.releases || [],
        roleLabels: config.roleLabels || {},

        fields: {
            title: true, version: true, alternative_titles: false, status: true, genre: true, duration_seconds: false,
            description: true, bpm: false, musical_key: false, language: true,
            recording_location: true, recording_years: true,
        },
        selected: { bands: [], credits: [], labels: [], publishers: [], releases: [], projects: [], contracts: [] },

        sections: [
            { key: 'basic', label: 'Grunddaten', fields: [
                { key: 'title', label: 'Titel' }, { key: 'version', label: 'Version' },
                { key: 'alternative_titles', label: 'Alternativtitel' }, { key: 'status', label: 'Status' },
                { key: 'genre', label: 'Genre' }, { key: 'duration_seconds', label: 'Dauer' },
            ] },
            { key: 'bands', label: 'Band', list: 'bands' },
            { key: 'credits', label: 'Credits', list: 'credits' },
            { key: 'labels', label: 'Label', list: 'labels' },
            { key: 'publishers', label: 'Publisher', list: 'publishers' },
            { key: 'releases', label: 'Produkte', list: 'releases' },
            { key: 'projects', label: 'Projekte', list: 'projects' },
            { key: 'contracts', label: 'Verträge', list: 'contracts' },
            { key: 'notes', label: 'Notizen', fields: [{ key: 'description', label: 'Beschreibung' }] },
            { key: 'details', label: 'Musik-Details', fields: [
                { key: 'bpm', label: 'BPM' }, { key: 'musical_key', label: 'Tonart' }, { key: 'language', label: 'Sprache' },
                { key: 'recording_location', label: 'Aufnahmeort' }, { key: 'recording_years', label: 'Aufnahmejahr(e)' },
            ] },
        ],

        // Step 2
        search: '',
        filterBand: '',
        filterRelease: '',
        results: [],
        loading: false,
        targets: [],
        mode: 'add',
        _searchTicket: 0,

        init() {},

        async openModal() {
            this.showModal = true;
            this.step = 1;
            this.copied = false;
            if (!this.data) {
                const res = await fetch(config.copyUrl, { headers: { 'Accept': 'application/json' } });
                this.data = await res.json();
                // Every linked entry is selected to begin with
                this.selected = {
                    bands: (this.data.bands || []).map(b => b.id),
                    credits: (this.data.credits || []).map(c => c.key),
                    labels: (this.data.labels || []).map(l => l.id),
                    publishers: (this.data.publishers || []).map(p => p.id),
                    releases: (this.data.releases || []).map(r => r.id),
                    projects: (this.data.projects || []).map(p => p.id),
                    contracts: (this.data.contracts || []).map(c => c.id),
                };
            }
        },

        // --- Step 1: what to copy ---------------------------------------------

        hasValue(key) {
            const value = this.data?.[key];
            if (Array.isArray(value)) return value.length > 0;
            return value !== null && value !== undefined && value !== '';
        },

        preview(key) {
            if (!this.hasValue(key)) return '(leer)';
            let value = this.data[key];
            if (key === 'alternative_titles') value = value.join(', ');
            if (key === 'duration_seconds') value = Math.floor(value / 60) + ':' + String(value % 60).padStart(2, '0');
            value = String(value);
            return '(' + (value.length > 40 ? value.substring(0, 40) + '…' : value) + ')';
        },

        listItems(section) {
            if (!section.list || !this.data) return [];
            const rows = this.data[section.list] || [];
            switch (section.list) {
                case 'credits':
                    return rows.map(c => ({
                        value: c.key,
                        label: c.name + (c.ipi_number ? ' [IPI ' + c.ipi_number + ']' : '')
                            + ' (' + (this.roleLabels[c.role] || c.role) + (c.instrument ? ', ' + c.instrument : '') + ')',
                    }));
                case 'releases':
                    return rows.map(r => ({ value: r.id, label: r.title + (r.track_number ? ' (#' + r.track_number + ')' : '') }));
                case 'contracts':
                    return rows.map(c => ({ value: c.id, label: c.title }));
                default:
                    return rows.map(o => ({ value: o.id, label: o.name }));
            }
        },

        // What a chapter offers to pick (empty fields cannot be copied) ...
        available(section) {
            if (section.list) return this.listItems(section).map(item => item.value);
            return section.fields.map(f => f.key).filter(key => this.hasValue(key));
        },

        // ... and what of it is picked
        chosen(section) {
            if (section.list) return this.selected[section.list].filter(value => this.available(section).includes(value));
            return this.available(section).filter(key => this.fields[key]);
        },

        sectionState(section) {
            const available = this.available(section).length;
            const chosen = this.chosen(section).length;
            if (!available || !chosen) return 'none';
            return chosen === available ? 'all' : 'some';
        },

        toggleSection(section, on) {
            if (section.list) {
                this.selected[section.list] = on ? this.available(section) : [];
            } else {
                this.available(section).forEach(key => { this.fields[key] = on; });
            }
        },

        allState() {
            const states = this.sections.filter(s => this.available(s).length).map(s => this.sectionState(s));
            if (!states.length || states.every(s => s === 'none')) return 'none';
            return states.every(s => s === 'all') ? 'all' : 'some';
        },

        toggleAll(on) {
            this.sections.forEach(section => this.toggleSection(section, on));
        },

        toggleItem(list, value, checked) {
            if (checked) {
                if (!this.selected[list].includes(value)) this.selected[list].push(value);
            } else {
                this.selected[list] = this.selected[list].filter(v => v !== value);
            }
        },

        selectedFields() {
            return FIELD_KEYS.filter(key => this.fields[key] && this.hasValue(key));
        },

        selectsLists() {
            return (this.fields.alternative_titles && this.hasValue('alternative_titles'))
                || LISTS.some(list => this.selected[list].length);
        },

        selectedListEntries() {
            return LISTS.flatMap(list => this.selected[list].map(value => ({ name: list + '[]', value })));
        },

        summary() {
            if (!this.data) return '';
            return this.sections.flatMap(section => {
                if (section.list) {
                    const n = this.chosen(section).length;
                    return n ? [section.label + ' (' + n + ')'] : [];
                }
                return section.fields.filter(f => this.chosen(section).includes(f.key)).map(f => f.label);
            }).join(', ');
        },

        // The format the paste button in the track form reads
        clipboard() {
            const result = {};
            this.selectedFields().forEach(key => { result[key] = this.data[key]; });
            if (this.fields.alternative_titles) result.alternative_titles = this.data.alternative_titles;
            ['bands', 'labels', 'publishers', 'releases', 'projects', 'contracts'].forEach(list => {
                result[list] = (this.data[list] || []).filter(row => this.selected[list].includes(row.id));
            });
            result.credits = (this.data.credits || []).filter(c => this.selected.credits.includes(c.key));
            return result;
        },

        writeClipboard() {
            localStorage.setItem('tyl_track_metadata', JSON.stringify(this.clipboard()));
            this.copied = true;
        },

        copyOnly() {
            this.writeClipboard();
            setTimeout(() => { this.showModal = false; this.copied = false; }, 1200);
        },

        copyAndContinue() {
            this.writeClipboard();
            this.step = 2;
            if (!this.results.length) this.loadTargets();
        },

        // --- Step 2: where to paste ---------------------------------------------

        async loadTargets() {
            const ticket = ++this._searchTicket;
            const params = new URLSearchParams({ q: this.search, exclude: config.trackId });
            if (this.filterBand) params.set('band', this.filterBand);
            if (this.filterRelease) params.set('release', this.filterRelease);

            this.loading = true;
            try {
                const res = await fetch(config.searchUrl + '?' + params, { headers: { 'Accept': 'application/json' } });
                const rows = await res.json();
                if (ticket === this._searchTicket) this.results = rows;
            } finally {
                // A slower, older request must not overwrite a newer result
                if (ticket === this._searchTicket) this.loading = false;
            }
        },

        resultsLabel() {
            if (this.loading) return 'Suche …';
            if (!this.results.length) return 'Keine Treffer';
            return (this.results.length >= 50 ? 'Erste 50 Treffer (Suche eingrenzen)' : this.results.length + ' Treffer') + ' – alle auswählen';
        },

        isTarget(id) {
            return this.targets.some(t => t.id === id);
        },

        toggleTarget(track, on) {
            if (on && !this.isTarget(track.id)) {
                this.targets.push({ id: track.id, title: track.title });
            } else if (!on) {
                this.targets = this.targets.filter(t => t.id !== track.id);
            }
        },

        toggleAllResults(on) {
            this.results.forEach(track => this.toggleTarget(track, on));
        },

        overwritesTitle() {
            const parts = [this.fields.title && this.hasValue('title') && 'Titel', this.fields.version && this.hasValue('version') && 'Version'].filter(Boolean);
            return parts.join(' und ');
        },

        confirmText() {
            let text = 'Metadaten in ' + this.targets.length + (this.targets.length === 1 ? ' Track' : ' Tracks') + ' einfügen?';
            if (this.mode === 'replace' && this.selectsLists()) text += '\n\nDie ausgewählten Listen werden ersetzt.';
            if (this.overwritesTitle()) text += '\n\n' + this.overwritesTitle() + ' wird überschrieben.';
            return text;
        },
    };
}
</script>
@endpush
