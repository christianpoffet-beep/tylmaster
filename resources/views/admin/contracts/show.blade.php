@extends('admin.layouts.app')

@section('title', $contract->title)

@php
    $typeLabels = $contractTypes->pluck('name', 'slug')->toArray();
    $typeColors = $contractTypes->pluck('color', 'slug')->toArray();
    $statusColors = ['draft' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300', 'active' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300', 'expired' => 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300', 'terminated' => 'bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300'];
    $statusLabels = ['draft' => 'Entwurf', 'active' => 'Aktiv', 'expired' => 'Ausgelaufen', 'terminated' => 'Gekündigt'];
@endphp

@section('content')
<div class="flex flex-col lg:flex-row gap-6">
    <div class="flex-1">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex justify-between items-start mb-1">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $contract->title }}</h2>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusColors[$contract->status] ?? '' }}">{{ $statusLabels[$contract->status] ?? $contract->status }}</span>
            </div>
            @if($contract->contract_number)
                <p class="text-sm text-gray-500 mb-4">{{ $contract->contract_number }}</p>
            @else
                <div class="mb-4"></div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500 dark:text-gray-400">Typ:</span> <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ml-1 {{ $typeColors[$contract->type] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">{{ $typeLabels[$contract->type] ?? ucfirst($contract->type) }}</span></div>
                <div><span class="text-gray-500 dark:text-gray-400">Laufzeit:</span> <span class="text-gray-900 ml-1">{{ $contract->start_date?->format('d.m.Y') ?? '-' }} — {{ $contract->end_date?->format('d.m.Y') ?? '-' }}</span></div>
            </div>

            @if($contract->parties->count())
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Vertragsparteien</h3>
                <div class="space-y-2">
                    @foreach($contract->parties as $party)
                        <div class="flex items-center justify-between py-1.5">
                            <div class="text-sm">
                                @if($party->organization)
                                    <a href="{{ route('admin.organizations.show', $party->organization) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $party->organization->primary_name }}</a>
                                    @if($party->contact)
                                        <span class="text-gray-400 mx-1">&middot;</span>
                                        <a href="{{ route('admin.contacts.show', $party->contact) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $party->contact->full_name }}</a>
                                    @endif
                                @elseif($party->contact)
                                    <a href="{{ route('admin.contacts.show', $party->contact) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $party->contact->full_name }}</a>
                                @endif
                            </div>
                            <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ number_format($party->share, 2) }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($contract->has_zession)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Zession (Vorschusszahlung)</h3>
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="font-semibold text-amber-800 dark:text-amber-300">
                            {{ $contract->zession_currency }} {{ number_format($contract->zession_amount, 2, '.', "'") }}
                        </span>
                    </div>
                    @if($contract->zession_notes)
                        <p class="text-xs text-amber-700 dark:text-amber-400 mt-1.5">{{ $contract->zession_notes }}</p>
                    @endif
                </div>
            </div>
            @endif

            @if($contract->territory && count($contract->territory) > 0)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Geltungsbereich</h3>
                @if(in_array('ALL', $contract->territory))
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Weltweit
                    </span>
                @else
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($contract->territory as $code)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ $code }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            @endif

            @if($contract->rights && count($contract->rights) > 0)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Vergütung / Rechte</h3>
                @if($contract->rights_label_a || $contract->rights_label_b)
                    <p class="text-xs text-gray-400 mb-2">{{ $contract->rights_label_a ?? 'Partei 1' }} / {{ $contract->rights_label_b ?? 'Partei 2' }}</p>
                @endif
                <div class="space-y-1.5">
                    @foreach($contract->rights as $right)
                        <div class="flex items-start gap-2 text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300 min-w-0">{{ $right['label'] }}:</span>
                            @if(($right['mode'] ?? 'split') === 'split')
                                <span class="text-gray-600 dark:text-gray-400">{{ $right['split_a'] ?? 0 }}% {{ $contract->rights_label_a ?? 'Partei 1' }} / {{ $right['split_b'] ?? 0 }}% {{ $contract->rights_label_b ?? 'Partei 2' }}</span>
                            @else
                                <span class="text-gray-600 dark:text-gray-400">{{ $right['custom_text'] ?? '' }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($contract->terms)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Bedingungen / Notizen</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $contract->terms }}</p>
            </div>
            @endif
        </div>

        <div class="mt-4 flex gap-3">
            <a href="{{ route('admin.contracts.edit', $contract) }}" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-500">Bearbeiten</a>
            <button type="button" onclick="document.getElementById('pdf-dialog').showModal()" class="px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                PDF generieren
            </button>
        </div>

        {{-- PDF Generation Dialog --}}
        <dialog id="pdf-dialog" class="rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-0 max-w-md w-full backdrop:bg-gray-900/50">
            <form method="POST" action="{{ route('admin.contracts.pdf', $contract) }}">
                @csrf
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Vertrag als PDF</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Wähle, ob das PDF im System archiviert werden soll. Archivierte PDFs können nicht gelöscht werden.</p>

                    <div class="space-y-3">
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                            <input type="radio" name="archive" value="0" checked class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Nur herunterladen</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">PDF wird generiert und direkt heruntergeladen, ohne Speicherung im System.</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                            <input type="radio" name="archive" value="2" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Nur archivieren</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">PDF wird im System gespeichert (nicht löschbar), ohne Download.</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                            <input type="radio" name="archive" value="1" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Archivieren & herunterladen</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">PDF wird im System gespeichert (nicht löschbar) und heruntergeladen.</p>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 rounded-b-xl">
                    <button type="button" onclick="document.getElementById('pdf-dialog').close()" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">Abbrechen</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">PDF generieren</button>
                </div>
            </form>
        </dialog>
    </div>

    <div class="w-full lg:w-80 space-y-4">
        {{-- Projekte --}}
        @if($contract->projects->count())
        <x-admin.collapsible-card title="Projekte" :count="$contract->projects->count()">
            @foreach($contract->projects as $project)
                <a href="{{ route('admin.projects.show', $project) }}" class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-1">{{ $project->name }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        {{-- Tracks --}}
        @if($contract->tracks->count())
        <x-admin.collapsible-card title="Tracks" :count="$contract->tracks->count()">
            @foreach($contract->tracks as $track)
                <a href="{{ route('admin.tracks.show', $track) }}" class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-1">{{ $track->title }}{{ $track->isrc ? ' (' . $track->isrc . ')' : '' }}</a>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        {{-- Releases --}}
        @if($contract->releases->count())
        <x-admin.collapsible-card title="Releases" :count="$contract->releases->count()">
            @foreach($contract->releases as $release)
                <a href="{{ route('admin.releases.show', $release) }}" class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-1">{{ $release->title }}{{ $release->upc ? ' (' . $release->upc . ')' : '' }}</a>
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
