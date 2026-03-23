@extends('admin.layouts.app')

@section('title', $release->title)

@section('content')
<div class="max-w-4xl">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex justify-between items-start mb-6">
            <div class="flex gap-4">
                @if($release->cover_image_path)
                    <img src="{{ Storage::url($release->cover_image_path) }}" alt="Cover" class="w-32 h-32 object-cover rounded-lg shadow-sm flex-shrink-0">
                @endif
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $release->title }}</h2>
                    @php
                        $productTypes = is_array($release->product_type) ? $release->product_type : ($release->product_type ? [$release->product_type] : []);
                    @endphp
                    @if(count($productTypes))
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach($productTypes as $pt)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">{{ $pt }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if($release->label)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $release->label }}</p>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.releases.edit', $release) }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">Bearbeiten</a>
                <form method="POST" action="{{ route('admin.releases.destroy', $release) }}" onsubmit="return confirm('Produkt wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white text-sm rounded-lg hover:bg-red-700 dark:hover:bg-red-600">Löschen</button>
                </form>
            </div>
        </div>

        {{-- Metadata --}}
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            @if($release->title_language)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sprache des Titels</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 uppercase">{{ $release->title_language }}</dd>
                </div>
            @endif
            @if($release->main_artist)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Hauptkünstler</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $release->main_artist }}</dd>
                </div>
            @endif
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">EAN / UPC</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $release->ean_upc ?? $release->upc ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Katalognummer</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $release->catalog_number ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Label Name</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $release->label_name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Label</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $release->organizations->where('type', 'label')->first()?->primary_name ?? $release->label ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Release-Datum</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $release->release_date ? $release->release_date->format('d.m.Y') : '-' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Territory</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $release->territory_display ?? '-' }}</dd>
            </div>
            @if($release->release_info)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Release Info</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $release->release_info }}</dd>
                </div>
            @endif
            @if($release->release_info_internal)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Release Info (intern)</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $release->release_info_internal }}</dd>
                </div>
            @endif
            @if($release->description)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Beschreibung</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $release->description }}</dd>
                </div>
            @endif
            @if($release->width || $release->height || $release->depth || $release->weight)
                <div class="sm:col-span-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Grösse / Gewicht</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                        @if($release->width || $release->height || $release->depth)
                            {{ $release->width ?? '-' }} x {{ $release->height ?? '-' }} x {{ $release->depth ?? '-' }} cm
                        @endif
                        @if($release->weight)
                            &middot; {{ $release->weight }} g
                        @endif
                    </dd>
                </div>
            @endif
            @if($release->price)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Preis</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $release->currency ?? 'CHF' }} {{ number_format($release->price, 2) }}</dd>
                </div>
            @endif
            @if($release->stock !== null)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Anzahl an Lager</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $release->stock }}</dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- Artworks --}}
    @if(isset($release->artworks) && $release->artworks->count())
        <x-admin.collapsible-card title="Artworks" :count="$release->artworks->count()" class="mt-6">
            <div class="flex flex-wrap gap-4">
                @foreach($release->artworks as $artwork)
                    <div class="flex items-center gap-3 p-2 rounded-lg border border-gray-200 dark:border-gray-700">
                        @if($artwork->file_path)
                            <img src="{{ Storage::url($artwork->file_path) }}" alt="{{ $artwork->title }}" class="w-16 h-16 object-cover rounded shadow-sm">
                        @endif
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $artwork->title }}</span>
                            @if($artwork->pivot->is_primary)
                                <span class="ml-1 text-yellow-500" title="Haupt-Artwork">&#9733;</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Tracklist --}}
    <x-admin.collapsible-card title="Tracklist" :count="$release->tracks->count()" class="mt-6">
        @if($release->tracks->count())
            @php $hasRoles = $release->tracks->contains(fn($t) => $t->pivot->role && $t->pivot->role !== 'main'); @endphp
            <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">Reihenfolge per Drag & Drop ändern</p>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 w-8"></th>
                            <th class="py-2 pr-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-12">#</th>
                            <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Titel</th>
                            <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ISRC</th>
                            <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dauer</th>
                            @if($hasRoles)
                                <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Rolle</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="tracklist-sortable" class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($release->tracks->sortBy(['pivot.disc_number', 'pivot.track_number']) as $track)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-grab active:cursor-grabbing" data-track-id="{{ $track->id }}">
                                <td class="py-2 px-2 text-gray-300 dark:text-gray-600">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 15h18v-2H3v2zm0 4h18v-2H3v2zm0-8h18V9H3v2zm0-6v2h18V5H3z"/></svg>
                                </td>
                                <td class="py-2 pr-3 text-sm text-gray-400 dark:text-gray-500 font-mono text-right track-number">
                                    {{ $track->pivot->track_number ?? $loop->iteration }}
                                </td>
                                <td class="py-2 px-3 text-sm">
                                    <div class="flex items-center gap-2">
                                        @if($track->audio_file_path)
                                            <button type="button"
                                                    @click="$dispatch('play-track', { title: '{{ addslashes($track->display_title) }}', artist: '{{ addslashes($track->organizations->where("type", "band")->pluck("primary_name")->join(", ")) }}', url: '{{ Storage::url($track->audio_file_path) }}' })"
                                                    class="w-6 h-6 flex items-center justify-center rounded-full bg-blue-600 hover:bg-blue-700 text-white transition flex-shrink-0">
                                                <svg class="w-3 h-3 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                            </button>
                                        @endif
                                        <a href="{{ route('admin.tracks.show', $track) }}" class="font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">
                                            {{ $track->title }}
                                            @if($track->version)
                                                <span class="text-gray-400 dark:text-gray-500 font-normal">({{ $track->version }})</span>
                                            @endif
                                        </a>
                                    </div>
                                </td>
                                <td class="py-2 px-3 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $track->isrc ?? '-' }}</td>
                                <td class="py-2 px-3 text-sm text-gray-500 dark:text-gray-400">{{ $track->formatted_duration ?? '-' }}</td>
                                @if($hasRoles)
                                    <td class="py-2 px-3 text-sm text-gray-500 dark:text-gray-400">
                                        @if($track->pivot->role && $track->pivot->role !== 'main')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ ucfirst($track->pivot->role) }}</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Keine Tracks zugewiesen.</p>
        @endif
    </x-admin.collapsible-card>

    {{-- Band --}}
    @php $bands = $release->organizations->where('type', 'band'); @endphp
    @if($bands->count())
        <x-admin.collapsible-card title="Band" :count="$bands->count()" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($bands as $band)
                    <a href="{{ route('admin.organizations.show', $band) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-800/50">{{ $band->primary_name }}</a>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Credits --}}
    @if($release->contacts->count())
        @php
            $roleLabels = collect(\App\Models\Setting::creditRoles())->flatMap(fn($roles) => $roles)->toArray();
        @endphp
        <x-admin.collapsible-card title="Credits" :count="$release->contacts->count()" class="mt-6">
            <div class="space-y-2">
                @foreach($release->contacts as $contact)
                    <div class="flex items-center justify-between py-1">
                        <a href="{{ route('admin.contacts.show', $contact) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800">{{ $contact->full_name }}</a>
                        <div class="flex items-center gap-1.5">
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

    {{-- Label (Organisation) --}}
    @php $labels = $release->organizations->where('type', 'label'); @endphp
    @if($labels->count())
        <x-admin.collapsible-card title="Label" :count="$labels->count()" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($labels as $labelOrg)
                    <a href="{{ route('admin.organizations.show', $labelOrg) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-800/50">{{ $labelOrg->primary_name }}</a>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Publisher --}}
    @php $publishers = $release->organizations->where('type', 'publishing'); @endphp
    @if($publishers->count())
        <x-admin.collapsible-card title="Publisher" :count="$publishers->count()" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($publishers as $publisher)
                    <a href="{{ route('admin.organizations.show', $publisher) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-200 dark:hover:bg-indigo-800/50">{{ $publisher->primary_name }}</a>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Projekte --}}
    @if($release->projects->count())
        <x-admin.collapsible-card title="Projekte" :count="$release->projects->count()" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($release->projects as $project)
                    <a href="{{ route('admin.projects.show', $project) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200">{{ $project->name }}</a>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Verträge --}}
    @if($release->contracts->count())
        <x-admin.collapsible-card title="Verträge" :count="$release->contracts->count()" class="mt-6">
            <div class="flex flex-wrap gap-2">
                @foreach($release->contracts as $contract)
                    <a href="{{ route('admin.contracts.show', $contract) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200">{{ $contract->title }}</a>
                @endforeach
            </div>
        </x-admin.collapsible-card>
    @endif

    <div class="mt-4">
        <a href="{{ route('admin.releases.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">&larr; Zurück zur Übersicht</a>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('tracklist-sortable');
    if (!el) return;

    Sortable.create(el, {
        animation: 150,
        handle: 'tr',
        ghostClass: 'bg-blue-50 dark:bg-blue-900/20',
        onEnd: function() {
            // Update track numbers visually
            el.querySelectorAll('tr').forEach((row, i) => {
                row.querySelector('.track-number').textContent = i + 1;
            });

            // Save new order via API
            const order = Array.from(el.querySelectorAll('tr')).map(row => row.dataset.trackId);
            fetch('{{ route("admin.releases.reorderTracks", $release) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ order: order }),
            });
        }
    });
});
</script>
@endpush
