@extends('admin.layouts.app')

@section('title', $contract->title)

@php
    $typeLabels = $contractTypes->pluck('name', 'slug')->toArray();
    $typeColors = $contractTypes->pluck('color', 'slug')->toArray();
    $statusColors = ['draft' => 'bg-gray-100 text-gray-600', 'active' => 'bg-green-100 text-green-700', 'expired' => 'bg-red-100 text-red-700', 'terminated' => 'bg-orange-100 text-orange-700'];
    $statusLabels = ['draft' => 'Entwurf', 'active' => 'Aktiv', 'expired' => 'Ausgelaufen', 'terminated' => 'Gekündigt'];
@endphp

@section('content')
<div class="flex flex-col lg:flex-row gap-6">
    <div class="flex-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-start mb-1">
                <h2 class="text-xl font-bold text-gray-900">{{ $contract->title }}</h2>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusColors[$contract->status] ?? '' }}">{{ $statusLabels[$contract->status] ?? $contract->status }}</span>
            </div>
            @if($contract->contract_number)
                <p class="text-sm text-gray-500 mb-4">{{ $contract->contract_number }}</p>
            @else
                <div class="mb-4"></div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Typ:</span> <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ml-1 {{ $typeColors[$contract->type] ?? 'bg-gray-100 text-gray-600' }}">{{ $typeLabels[$contract->type] ?? ucfirst($contract->type) }}</span></div>
                <div><span class="text-gray-500">Laufzeit:</span> <span class="text-gray-900 ml-1">{{ $contract->start_date?->format('d.m.Y') ?? '-' }} — {{ $contract->end_date?->format('d.m.Y') ?? '-' }}</span></div>
            </div>

            @if($contract->parties->count())
            <div class="mt-4 pt-4 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Vertragsparteien</h3>
                <div class="space-y-2">
                    @foreach($contract->parties as $party)
                        <div class="flex items-center justify-between py-1.5">
                            <div class="text-sm">
                                @if($party->organization)
                                    <a href="{{ route('admin.organizations.show', $party->organization) }}" class="text-blue-600 hover:text-blue-800">{{ $party->organization->primary_name }}</a>
                                    @if($party->contact)
                                        <span class="text-gray-400 mx-1">&middot;</span>
                                        <a href="{{ route('admin.contacts.show', $party->contact) }}" class="text-blue-600 hover:text-blue-800">{{ $party->contact->full_name }}</a>
                                    @endif
                                @elseif($party->contact)
                                    <a href="{{ route('admin.contacts.show', $party->contact) }}" class="text-blue-600 hover:text-blue-800">{{ $party->contact->full_name }}</a>
                                @endif
                            </div>
                            <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ number_format($party->share, 2) }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($contract->terms)
            <div class="mt-4 pt-4 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">Bedingungen / Notizen</h3>
                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $contract->terms }}</p>
            </div>
            @endif
        </div>

        <div class="mt-4 flex gap-3">
            <a href="{{ route('admin.contracts.edit', $contract) }}" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Bearbeiten</a>
        </div>
    </div>

    <div class="w-full lg:w-80 space-y-4">
        {{-- Projekte --}}
        @if($contract->projects->count())
        <x-admin.collapsible-card title="Projekte" :count="$contract->projects->count()">
            @foreach($contract->projects as $project)
                <a href="{{ route('admin.projects.show', $project) }}" class="block text-sm text-blue-600 hover:text-blue-800 py-1">{{ $project->name }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        {{-- Tracks --}}
        @if($contract->tracks->count())
        <x-admin.collapsible-card title="Tracks" :count="$contract->tracks->count()">
            @foreach($contract->tracks as $track)
                <a href="{{ route('admin.tracks.show', $track) }}" class="block text-sm text-blue-600 hover:text-blue-800 py-1">{{ $track->title }}{{ $track->isrc ? ' (' . $track->isrc . ')' : '' }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        {{-- Releases --}}
        @if($contract->releases->count())
        <x-admin.collapsible-card title="Releases" :count="$contract->releases->count()">
            @foreach($contract->releases as $release)
                <a href="{{ route('admin.releases.show', $release) }}" class="block text-sm text-blue-600 hover:text-blue-800 py-1">{{ $release->title }}{{ $release->upc ? ' (' . $release->upc . ')' : '' }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        {{-- Dokumente --}}
        <x-admin.collapsible-card title="Dokumente" :count="$contract->documents->whereNull('deleted_at')->count()">
            @forelse($contract->documents as $doc)
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
                            <button type="button" class="text-sm text-blue-600 hover:text-blue-800 text-left truncate block"
                                @click="$dispatch('open-doc-viewer', {
                                    url: '{{ route('admin.documents.preview', $doc) }}',
                                    title: '{{ e($doc->title) }}',
                                    mime: '{{ $doc->mime_type }}',
                                    downloadUrl: '{{ route('admin.documents.download', $doc) }}'
                                })">{{ $doc->title }}</button>
                            @if($doc->notes)
                                <p class="text-xs text-gray-400 truncate">{{ $doc->notes }}</p>
                            @endif
                        @else
                            <a href="{{ route('admin.documents.download', $doc) }}" class="text-sm text-blue-600 hover:text-blue-800 truncate block">{{ $doc->title }}</a>
                            @if($doc->notes)
                                <p class="text-xs text-gray-400 truncate">{{ $doc->notes }}</p>
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
