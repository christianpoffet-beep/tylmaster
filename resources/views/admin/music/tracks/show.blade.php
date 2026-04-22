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
            <div class="flex gap-2" x-data="copyModal()" x-init="init()">
                <button type="button" @click="openModal()" class="px-4 py-2 bg-gray-600 dark:bg-gray-500 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-400 transition">
                    Metadaten kopieren
                </button>
                <a href="{{ route('admin.tracks.edit', $track) }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">Bearbeiten</a>
                <form method="POST" action="{{ route('admin.tracks.destroy', $track) }}" onsubmit="return confirm('Track wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white text-sm rounded-lg hover:bg-red-700 dark:hover:bg-red-600">Löschen</button>
                </form>

                {{-- Copy Metadata Modal --}}
                <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showModal = false">
                    <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full max-h-[80vh] overflow-y-auto p-6" @click.stop>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Metadaten kopieren</h3>

                        <div class="space-y-1 text-sm" x-show="data">
                            {{-- Grunddaten --}}
                            <p class="font-semibold text-gray-700 dark:text-gray-300 mt-3 mb-1">Grunddaten</p>
                            <label class="flex items-center gap-2"><input type="checkbox" x-model="fields.title" class="rounded border-gray-300 text-blue-600"> Titel <span class="text-gray-400" x-show="data" x-text="data?.title ? '(' + data.title + ')' : ''"></span></label>
                            <label class="flex items-center gap-2"><input type="checkbox" x-model="fields.version" class="rounded border-gray-300 text-blue-600"> Version <span class="text-gray-400" x-show="data?.version" x-text="'(' + (data?.version || '') + ')'"></span></label>
                            <label class="flex items-center gap-2"><input type="checkbox" x-model="fields.status" class="rounded border-gray-300 text-blue-600"> Status <span class="text-gray-400" x-text="'(' + (data?.status || '-') + ')'"></span></label>
                            <label class="flex items-center gap-2"><input type="checkbox" x-model="fields.genre" class="rounded border-gray-300 text-blue-600"> Genre <span class="text-gray-400" x-text="'(' + (data?.genre || '-') + ')'"></span></label>
                            <label class="flex items-center gap-2"><input type="checkbox" x-model="fields.duration" class="rounded border-gray-300 text-blue-600"> Dauer</label>

                            {{-- Band --}}
                            <p class="font-semibold text-gray-700 dark:text-gray-300 mt-4 mb-1">Band</p>
                            <template x-if="data?.bands?.length > 0">
                                <div class="space-y-1 ml-1">
                                    <template x-for="(b, i) in data.bands" :key="'band-'+i">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 band-cb" :value="b.id" @change="toggleItem('selectedBands', b.id, $event.target.checked)">
                                            <span x-text="b.name"></span>
                                        </label>
                                    </template>
                                </div>
                            </template>
                            <p x-show="!data?.bands?.length" class="text-gray-400 ml-1">Keine</p>

                            {{-- Credits --}}
                            <p class="font-semibold text-gray-700 dark:text-gray-300 mt-4 mb-1">Credits</p>
                            <template x-if="data?.credits?.length > 0">
                                <div class="space-y-1 ml-1">
                                    <template x-for="(c, i) in data.credits" :key="'credit-'+i">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 credit-cb" :value="i" @change="toggleItem('selectedCredits', i, $event.target.checked)">
                                            <span x-text="c.name + (c.ipi_number ? ' [IPI ' + c.ipi_number + ']' : '') + ' (' + c.role + (c.instrument ? ', ' + c.instrument : '') + ')'"></span>
                                        </label>
                                    </template>
                                </div>
                            </template>
                            <p x-show="!data?.credits?.length" class="text-gray-400 ml-1">Keine</p>

                            {{-- Label --}}
                            <p class="font-semibold text-gray-700 dark:text-gray-300 mt-4 mb-1">Label</p>
                            <template x-if="data?.labels?.length > 0">
                                <div class="space-y-1 ml-1">
                                    <template x-for="(l, i) in data.labels" :key="'label-'+i">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 label-cb" :value="l.id" @change="toggleItem('selectedLabels', l.id, $event.target.checked)">
                                            <span x-text="l.name"></span>
                                        </label>
                                    </template>
                                </div>
                            </template>
                            <p x-show="!data?.labels?.length" class="text-gray-400 ml-1">Keine</p>

                            {{-- Publisher --}}
                            <p class="font-semibold text-gray-700 dark:text-gray-300 mt-4 mb-1">Publisher</p>
                            <template x-if="data?.publishers?.length > 0">
                                <div class="space-y-1 ml-1">
                                    <template x-for="(p, i) in data.publishers" :key="'pub-'+i">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 pub-cb" :value="p.id" @change="toggleItem('selectedPublishers', p.id, $event.target.checked)">
                                            <span x-text="p.name"></span>
                                        </label>
                                    </template>
                                </div>
                            </template>
                            <p x-show="!data?.publishers?.length" class="text-gray-400 ml-1">Keine</p>

                            {{-- Produkte --}}
                            <p class="font-semibold text-gray-700 dark:text-gray-300 mt-4 mb-1">Produkte</p>
                            <template x-if="data?.releases?.length > 0">
                                <div class="space-y-1 ml-1">
                                    <template x-for="(r, i) in data.releases" :key="'rel-'+i">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 rel-cb" :value="r.id" @change="toggleItem('selectedReleases', r.id, $event.target.checked)">
                                            <span x-text="r.title + (r.track_number ? ' (#' + r.track_number + ')' : '')"></span>
                                        </label>
                                    </template>
                                </div>
                            </template>
                            <p x-show="!data?.releases?.length" class="text-gray-400 ml-1">Keine</p>

                            {{-- Projekte --}}
                            <p class="font-semibold text-gray-700 dark:text-gray-300 mt-4 mb-1">Projekte</p>
                            <template x-if="data?.projects?.length > 0">
                                <div class="space-y-1 ml-1">
                                    <template x-for="(p, i) in data.projects" :key="'proj-'+i">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 proj-cb" :value="p.id" @change="toggleItem('selectedProjects', p.id, $event.target.checked)">
                                            <span x-text="p.name"></span>
                                        </label>
                                    </template>
                                </div>
                            </template>
                            <p x-show="!data?.projects?.length" class="text-gray-400 ml-1">Keine</p>

                            {{-- Verträge --}}
                            <p class="font-semibold text-gray-700 dark:text-gray-300 mt-4 mb-1">Verträge</p>
                            <template x-if="data?.contracts?.length > 0">
                                <div class="space-y-1 ml-1">
                                    <template x-for="(c, i) in data.contracts" :key="'con-'+i">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 con-cb" :value="c.id" @change="toggleItem('selectedContracts', c.id, $event.target.checked)">
                                            <span x-text="c.title"></span>
                                        </label>
                                    </template>
                                </div>
                            </template>
                            <p x-show="!data?.contracts?.length" class="text-gray-400 ml-1">Keine</p>

                            {{-- Notizen --}}
                            <p class="font-semibold text-gray-700 dark:text-gray-300 mt-4 mb-1">Notizen</p>
                            <label class="flex items-center gap-2"><input type="checkbox" x-model="fields.description" class="rounded border-gray-300 text-blue-600"> Beschreibung <span class="text-gray-400" x-text="data?.description ? '(' + data.description.substring(0, 40) + (data.description.length > 40 ? '...' : '') + ')' : '(leer)'"></span></label>

                            {{-- Musik-Details --}}
                            <p class="font-semibold text-gray-700 dark:text-gray-300 mt-4 mb-1">Musik-Details</p>
                            <label class="flex items-center gap-2"><input type="checkbox" x-model="fields.bpm" class="rounded border-gray-300 text-blue-600"> BPM <span class="text-gray-400" x-text="'(' + (data?.bpm || '-') + ')'"></span></label>
                            <label class="flex items-center gap-2"><input type="checkbox" x-model="fields.musical_key" class="rounded border-gray-300 text-blue-600"> Tonart <span class="text-gray-400" x-text="'(' + (data?.musical_key || '-') + ')'"></span></label>
                            <label class="flex items-center gap-2"><input type="checkbox" x-model="fields.language" class="rounded border-gray-300 text-blue-600"> Sprache <span class="text-gray-400" x-text="'(' + (data?.language || '-') + ')'"></span></label>
                        </div>

                        {{-- Loading --}}
                        <div x-show="!data" class="py-8 text-center text-gray-400">Laden...</div>

                        {{-- Actions --}}
                        <div class="flex gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" @click="doCopy()" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">Kopieren</button>
                            <button type="button" @click="showModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">Abbrechen</button>
                            <span x-show="copied" x-transition class="text-sm text-green-600 dark:text-green-400 self-center ml-auto">Kopiert!</span>
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
        </dl>
    </div>

    {{-- Audio --}}
    @if($track->audio_file_path)
        <div class="mt-6 flex items-center gap-3" x-data>
            <button type="button"
                    @click="$dispatch('play-track', { title: '{{ addslashes($track->display_title) }}', artist: '{{ addslashes($track->organizations->where("type", "band")->pluck("primary_name")->join(", ")) }}', url: '{{ Storage::disk('public')->url($track->audio_file_path) }}' })"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-600 hover:bg-blue-700 text-white transition flex-shrink-0">
                <svg class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </button>
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ basename($track->audio_file_path) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ strtoupper($track->audio_format ?? pathinfo($track->audio_file_path, PATHINFO_EXTENSION)) }}
                    @if($track->bitrate) &middot; {{ $track->bitrate }} kbps @endif
                    @if($track->sample_rate) &middot; {{ number_format($track->sample_rate) }} Hz @endif
                    @if($track->channels) &middot; {{ $track->channels == 1 ? 'Mono' : 'Stereo' }} @endif
                </p>
            </div>
        </div>
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
                                    if ($release->label_name) $meta->push($release->label_name);
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
function copyModal() {
    return {
        showModal: false,
        data: null,
        copied: false,
        fields: {
            title: true, version: true, status: true, genre: true, duration: false,
            description: true, bpm: false, musical_key: false, language: true,
        },
        selectedBands: [],
        selectedLabels: [],
        selectedPublishers: [],
        selectedCredits: [],
        selectedReleases: [],
        selectedProjects: [],
        selectedContracts: [],

        init() {},

        async openModal() {
            this.showModal = true;
            this.copied = false;
            if (!this.data) {
                const res = await fetch('{{ route("admin.tracks.copyMetadata", $track) }}');
                this.data = await res.json();
                // Default: all relational items selected
                this.selectedBands = (this.data.bands || []).map(b => b.id);
                this.selectedLabels = (this.data.labels || []).map(l => l.id);
                this.selectedPublishers = (this.data.publishers || []).map(p => p.id);
                this.selectedCredits = (this.data.credits || []).map((_, i) => i);
                this.selectedReleases = (this.data.releases || []).map(r => r.id);
                this.selectedProjects = (this.data.projects || []).map(p => p.id);
                this.selectedContracts = (this.data.contracts || []).map(c => c.id);
            }
        },

        toggleItem(list, val, checked) {
            if (checked) {
                if (!this[list].includes(val)) this[list].push(val);
            } else {
                this[list] = this[list].filter(v => v !== val);
            }
        },

        doCopy() {
            const result = {};
            if (this.fields.title) result.title = this.data.title;
            if (this.fields.version) result.version = this.data.version;
            if (this.fields.status) result.status = this.data.status;
            if (this.fields.genre) result.genre = this.data.genre;
            if (this.fields.duration) result.duration_seconds = this.data.duration_seconds;
            if (this.fields.language) result.language = this.data.language;
            if (this.fields.bpm) result.bpm = this.data.bpm;
            if (this.fields.musical_key) result.musical_key = this.data.musical_key;
            if (this.fields.description) result.description = this.data.description;

            result.bands = (this.data.bands || []).filter(b => this.selectedBands.includes(b.id));
            result.labels = (this.data.labels || []).filter(l => this.selectedLabels.includes(l.id));
            result.publishers = (this.data.publishers || []).filter(p => this.selectedPublishers.includes(p.id));
            result.credits = (this.data.credits || []).filter((_, i) => this.selectedCredits.includes(i));
            result.releases = (this.data.releases || []).filter(r => this.selectedReleases.includes(r.id));
            result.projects = (this.data.projects || []).filter(p => this.selectedProjects.includes(p.id));
            result.contracts = (this.data.contracts || []).filter(c => this.selectedContracts.includes(c.id));

            localStorage.setItem('tyl_track_metadata', JSON.stringify(result));
            this.copied = true;
            setTimeout(() => { this.showModal = false; this.copied = false; }, 1200);
        }
    };
}
</script>
@endpush
