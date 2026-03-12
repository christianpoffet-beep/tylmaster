@extends('admin.layouts.app')

@section('title', 'Dokumente')

@php
    $catLabels = ['contract' => 'Vertrag', 'invoice' => 'Rechnung', 'legal' => 'Rechtliches', 'music' => 'Musik', 'photo' => 'Foto', 'other' => 'Sonstiges'];
    $sourceLabels = ['contact' => 'Kontakt', 'contract' => 'Vertrag', 'task' => 'Aufgabe', 'track' => 'Track', 'project' => 'Projekt', 'artwork' => 'Artwork', 'photo' => 'Foto', 'invoice' => 'Rechnung', 'general' => 'Allgemein'];
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.documents.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Suche in Titel & Notizen..." class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 w-48">
        <select name="category" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Kategorien</option>
            @foreach($catLabels as $v => $l)
                <option value="{{ $v }}" {{ request('category') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <select name="source" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Quellen</option>
            @foreach($sourceLabels as $v => $l)
                <option value="{{ $v }}" {{ request('source') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-50 dark:hover:bg-gray-700/500">Filtern</button>
        @if(request('search') || request('category') || request('source'))
            <a href="{{ route('admin.documents.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:hover:text-gray-200 dark:text-gray-100">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.documents.create') }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 whitespace-nowrap">+ Dokument hochladen</a>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <x-admin.sortable-header column="title">Titel</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Typ</th>
                    <x-admin.sortable-header column="category">Kategorie</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quelle</th>
                    <x-admin.sortable-header column="file_size">Grösse</x-admin.sortable-header>
                    <x-admin.sortable-header column="created_at">Hochgeladen</x-admin.sortable-header>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($documents as $doc)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-100 text-[10px] font-bold text-gray-500 flex-shrink-0">{{ $doc->file_extension }}</span>
                                <div class="min-w-0">
                                    @php
                                        $isPreviewable = $doc->mime_type && (
                                            $doc->mime_type === 'application/pdf' ||
                                            str_starts_with($doc->mime_type, 'image/') ||
                                            str_starts_with($doc->mime_type, 'audio/') ||
                                            str_starts_with($doc->mime_type, 'video/')
                                        );
                                    @endphp
                                    @if($isPreviewable)
                                        <button type="button" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 truncate block text-left"
                                            @click="$dispatch('open-doc-viewer', {
                                                url: '{{ route('admin.documents.preview', $doc) }}',
                                                title: '{{ e($doc->title) }}',
                                                mime: '{{ $doc->mime_type }}',
                                                downloadUrl: '{{ route('admin.documents.download', $doc) }}'
                                            })">{{ $doc->title }}</button>
                                    @else
                                        <a href="{{ route('admin.documents.download', $doc) }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 truncate block">{{ $doc->title }}</a>
                                    @endif
                                    @if($doc->notes)
                                        <p class="text-xs text-gray-500 truncate max-w-xs">{{ $doc->notes }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $doc->mime_type ? Str::afterLast($doc->mime_type, '/') : '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $catLabels[$doc->category] ?? $doc->category }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $doc->source_color }}">{{ $doc->source_label }}</span>
                            @if($doc->documentable)
                                @php
                                    $sourceRoute = match($doc->documentable_type) {
                                        \App\Models\Contact::class => route('admin.contacts.show', $doc->documentable_id),
                                        \App\Models\Contract::class => route('admin.contracts.show', $doc->documentable_id),
                                        \App\Models\Task::class => route('admin.tasks.show', $doc->documentable_id),
                                        \App\Models\Track::class => route('admin.tracks.show', $doc->documentable_id),
                                        \App\Models\Project::class => route('admin.projects.show', $doc->documentable_id),
                                        \App\Models\Artwork::class => route('admin.artworks.show', $doc->documentable_id),
                                        \App\Models\ArtworkLogo::class => $doc->documentable?->artwork_id ? route('admin.artworks.show', $doc->documentable->artwork_id) : null,
                                        \App\Models\Photo::class => route('admin.photos.show', $doc->documentable_id),
                                        \App\Models\Invoice::class => route('admin.invoices.show', $doc->documentable_id),
                                        default => null,
                                    };
                                    $sourceName = match($doc->documentable_type) {
                                        \App\Models\Contact::class => $doc->documentable->full_name ?? '-',
                                        \App\Models\Contract::class => $doc->documentable->title ?? '-',
                                        \App\Models\Task::class => $doc->documentable->title ?? '-',
                                        \App\Models\Track::class => $doc->documentable->title ?? '-',
                                        \App\Models\Project::class => $doc->documentable->name ?? '-',
                                        \App\Models\Artwork::class => $doc->documentable->title ?? '-',
                                        \App\Models\ArtworkLogo::class => $doc->documentable?->artwork?->title ?? '-',
                                        \App\Models\Photo::class => $doc->documentable->display_title ?? '-',
                                        \App\Models\Invoice::class => $doc->documentable->invoice_number ?? '-',
                                        default => null,
                                    };
                                @endphp
                                @if($sourceRoute)
                                    <a href="{{ $sourceRoute }}" class="block text-xs text-gray-500 hover:text-blue-600 truncate max-w-[120px]">{{ $sourceName }}</a>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                            @if($doc->file_size)
                                @if($doc->file_size >= 1048576)
                                    {{ number_format($doc->file_size / 1048576, 1) }} MB
                                @else
                                    {{ number_format($doc->file_size / 1024, 0) }} KB
                                @endif
                            @else - @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $doc->created_at->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @php
                                $previewable = $doc->mime_type && (
                                    $doc->mime_type === 'application/pdf' ||
                                    str_starts_with($doc->mime_type, 'image/') ||
                                    str_starts_with($doc->mime_type, 'audio/') ||
                                    str_starts_with($doc->mime_type, 'video/')
                                );
                            @endphp
                            @if($previewable)
                                <button type="button" title="Vorschau"
                                    class="text-gray-500 hover:text-blue-600"
                                    @click="$dispatch('open-doc-viewer', {
                                        url: '{{ route('admin.documents.preview', $doc) }}',
                                        title: '{{ e($doc->title) }}',
                                        mime: '{{ $doc->mime_type }}',
                                        downloadUrl: '{{ route('admin.documents.download', $doc) }}'
                                    })">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            @endif
                            <a href="{{ route('admin.documents.download', $doc) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 ml-2" title="Download">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                            @if(!in_array($doc->documentable_type, [\App\Models\Contract::class, \App\Models\Artwork::class, \App\Models\ArtworkLogo::class, \App\Models\Photo::class]))
                            <form method="POST" action="{{ route('admin.documents.destroy', $doc) }}" class="inline ml-2" onsubmit="return confirm('Dokument wirklich löschen?')">
                                @csrf @method('DELETE')
                                <button class="text-red-400 hover:text-red-600" title="Löschen">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Keine Dokumente gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $documents->links() }}</div>
@endsection
