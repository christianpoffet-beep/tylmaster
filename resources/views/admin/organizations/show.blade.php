@extends('admin.layouts.app')

@section('title', $organization->primary_name)

@php
    $orgTypes = \App\Models\OrganizationType::orderBy('sort_order')->get();
    $typeLabels = $orgTypes->pluck('name', 'slug')->toArray();
    $typeColors = $orgTypes->pluck('color', 'slug')->toArray();
@endphp

@section('content')
<div class="flex flex-col lg:flex-row gap-6">
    <div class="flex-1">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-4">
                    @if($organization->avatar_path)
                        <img src="{{ Storage::url($organization->avatar_path) }}" alt="{{ $organization->primary_name }}" class="w-16 h-16 rounded-full object-cover border border-gray-200 dark:border-gray-700">
                    @else
                        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 text-xl font-bold border border-gray-200 dark:border-gray-700">
                            {{ strtoupper(substr($organization->primary_name, 0, 2)) }}
                        </div>
                    @endif
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $organization->primary_name }}
                            <span class="text-sm font-normal text-gray-400 ml-2">{{ $organization->ref_nr }}</span>
                        </h2>
                        @if(count($organization->names) > 1)
                            <p class="text-sm text-gray-500 mt-0.5">Alias: {{ implode(', ', array_slice($organization->names, 1)) }}</p>
                        @endif
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $typeColors[$organization->type] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">{{ $typeLabels[$organization->type] ?? $organization->type }}</span>
            </div>

            <div class="space-y-3 text-sm">
                {{-- Kontaktdaten --}}
                @if($organization->email)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">E-Mail:</span>
                    <a href="mailto:{{ $organization->email }}" class="text-blue-600 dark:text-blue-400 ml-1">{{ $organization->email }}</a>
                </div>
                @endif

                @if($organization->phone)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Telefon:</span>
                    <span class="text-gray-900 dark:text-gray-100 ml-1">{{ $organization->phone }}</span>
                </div>
                @endif

                {{-- Adresse --}}
                @if($organization->street || $organization->city)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Adresse:</span>
                    <span class="text-gray-900 dark:text-gray-100 ml-1">
                        {{ $organization->street }}{{ $organization->street && $organization->city ? ', ' : '' }}
                        {{ $organization->zip }} {{ $organization->city }}
                        {{ $organization->country ? '(' . $organization->country . ')' : '' }}
                    </span>
                </div>
                @endif

                {{-- UID/MWST --}}
                @if($organization->vat_number)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">UID/MWST-Nr.:</span>
                    <span class="text-gray-900 dark:text-gray-100 ml-1 font-mono">{{ $organization->vat_number }}</span>
                </div>
                @endif
            </div>

            {{-- Bankverbindung --}}
            @if($organization->iban || $organization->bank_name)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Bankverbindung</h3>
                <div class="space-y-1 text-sm">
                    @if($organization->iban)
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">IBAN:</span>
                        <span class="text-gray-900 dark:text-gray-100 ml-1 font-mono">{{ $organization->iban }}</span>
                    </div>
                    @endif
                    @if($organization->bank_name)
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Bank:</span>
                        <span class="text-gray-900 dark:text-gray-100 ml-1">{{ $organization->bank_name }}</span>
                    </div>
                    @endif
                    @if($organization->bic)
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">BIC/SWIFT:</span>
                        <span class="text-gray-900 dark:text-gray-100 ml-1 font-mono">{{ $organization->bic }}</span>
                    </div>
                    @endif
                    @if($organization->bank_zip || $organization->bank_city || $organization->bank_country)
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Bankadresse:</span>
                        <span class="text-gray-900 dark:text-gray-100 ml-1">
                            {{ $organization->bank_zip }} {{ $organization->bank_city }}
                            {{ $organization->bank_country ? '(' . $organization->bank_country . ')' : '' }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            @if($organization->websites && count($organization->websites))
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Websites</h3>
                <div class="space-y-1">
                    @foreach($organization->websites as $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $url }}</a>
                    @endforeach
                </div>
            </div>
            @endif

            @if($organization->genres->count())
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Genres</h3>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($organization->genres as $genre)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">{{ $genre->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($organization->biography)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Biografie</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $organization->biography }}</p>
            </div>
            @endif

            @if($organization->contacts->count())
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Kontakte</h3>
                <div class="space-y-1">
                    @foreach($organization->contacts as $contact)
                        <a href="{{ route('admin.contacts.show', $contact) }}" class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $contact->full_name }}</a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="mt-4 flex gap-3">
            <a href="{{ route('admin.organizations.edit', $organization) }}" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-50 dark:hover:bg-gray-700/500">Bearbeiten</a>
            <form method="POST" action="{{ route('admin.organizations.destroy', $organization) }}" onsubmit="return confirm('Organisation wirklich löschen?')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white text-sm rounded-lg hover:bg-red-700 dark:hover:bg-red-600">Löschen</button>
            </form>
        </div>
    </div>

    <div class="w-full lg:w-80 space-y-4">
        {{-- Projekte --}}
        @if($organization->projects->count())
        <x-admin.collapsible-card title="Projekte" :count="$organization->projects->count()">
            @foreach($organization->projects as $project)
                <a href="{{ route('admin.projects.show', $project) }}" class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-1">{{ $project->name }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        {{-- Tracks --}}
        @if($organization->tracks->count())
        <x-admin.collapsible-card title="Tracks" :count="$organization->tracks->count()">
            @foreach($organization->tracks as $track)
                <a href="{{ route('admin.tracks.show', $track) }}" class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-1">{{ $track->title }}{{ $track->isrc ? ' (' . $track->isrc . ')' : '' }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        {{-- Releases --}}
        @if($organization->releases->count())
        <x-admin.collapsible-card title="Releases" :count="$organization->releases->count()">
            @foreach($organization->releases as $release)
                <a href="{{ route('admin.releases.show', $release) }}" class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-1">{{ $release->title }}{{ $release->upc ? ' (' . $release->upc . ')' : '' }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        {{-- Verträge --}}
        @if($organization->contracts->count())
        <x-admin.collapsible-card title="Verträge" :count="$organization->contracts->count()">
            @foreach($organization->contracts as $contract)
                <a href="{{ route('admin.contracts.show', $contract) }}" class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-1">{{ $contract->title }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        {{-- Dokumente --}}
        <x-admin.collapsible-card title="Dokumente" :count="$organization->documents->whereNull('deleted_at')->count()">
            @forelse($organization->documents as $doc)
                @php
                    $docPreviewable = !$doc->trashed() && $doc->mime_type && (
                        $doc->mime_type === 'application/pdf' ||
                        str_starts_with($doc->mime_type, 'image/') ||
                        str_starts_with($doc->mime_type, 'audio/') ||
                        str_starts_with($doc->mime_type, 'video/')
                    );
                @endphp
                <div class="flex items-center justify-between py-1.5">
                    <div class="min-w-0">
                        @if($doc->trashed())
                            <span class="text-sm text-red-400 line-through truncate block">{{ $doc->title }}</span>
                            @if($doc->notes)
                                <p class="text-xs text-red-300 line-through truncate">{{ $doc->notes }}</p>
                            @endif
                            <p class="text-xs text-red-400">Gelöscht am {{ $doc->deleted_at->format('d.m.Y H:i') }}</p>
                        @elseif($docPreviewable)
                            <button type="button" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-left truncate block"
                                @click="$dispatch('open-doc-viewer', {
                                    url: '{{ route('admin.documents.preview', $doc) }}',
                                    title: '{{ e($doc->title) }}',
                                    mime: '{{ $doc->mime_type }}',
                                    downloadUrl: '{{ route('admin.documents.download', $doc) }}'
                                })">{{ $doc->title }}</button>
                            @if($doc->notes)
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $doc->notes }}</p>
                            @endif
                        @else
                            <a href="{{ route('admin.documents.download', $doc) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 truncate block">{{ $doc->title }}</a>
                            @if($doc->notes)
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $doc->notes }}</p>
                            @endif
                        @endif
                    </div>
                    @if(!$doc->trashed())
                    <div class="flex items-center gap-1 ml-2 flex-shrink-0">
                        @if($docPreviewable)
                            <button type="button" title="Vorschau" class="text-gray-400 hover:text-blue-600"
                                @click="$dispatch('open-doc-viewer', {
                                    url: '{{ route('admin.documents.preview', $doc) }}',
                                    title: '{{ e($doc->title) }}',
                                    mime: '{{ $doc->mime_type }}',
                                    downloadUrl: '{{ route('admin.documents.download', $doc) }}'
                                })">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        @endif
                        <a href="{{ route('admin.documents.download', $doc) }}" title="Download" class="text-gray-400 hover:text-blue-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                    </div>
                    @endif
                </div>
            @empty
                <p class="text-xs text-gray-400">Keine Dokumente</p>
            @endforelse
        </x-admin.collapsible-card>
    </div>
</div>
@endsection
