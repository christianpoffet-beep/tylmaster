@extends('admin.layouts.app')

@section('title', $document->title)

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">{{ $document->title }}</h2>

        <div class="space-y-3 text-sm">
            <div class="flex items-center">
                <span class="w-32 text-gray-500">Kategorie:</span>
                @php $catLabels = ['contract' => 'Vertrag', 'invoice' => 'Rechnung', 'legal' => 'Rechtliches', 'music' => 'Musik', 'other' => 'Sonstiges']; @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">{{ $catLabels[$document->category] ?? $document->category }}</span>
            </div>
            <div class="flex items-center">
                <span class="w-32 text-gray-500">Dateigrösse:</span>
                <span class="text-gray-900">{{ $document->file_size ? number_format($document->file_size / 1024, 0) . ' KB' : '-' }}</span>
            </div>
            <div class="flex items-center">
                <span class="w-32 text-gray-500">Dateityp:</span>
                <span class="text-gray-900">{{ $document->mime_type ?? '-' }}</span>
            </div>
            <div class="flex items-center">
                <span class="w-32 text-gray-500">Dateiname:</span>
                <span class="text-gray-900">{{ $document->original_filename ?? '-' }}</span>
            </div>
            <div class="flex items-center">
                <span class="w-32 text-gray-500">Hochgeladen am:</span>
                <span class="text-gray-900">{{ $document->created_at->format('d.m.Y H:i') }}</span>
            </div>
            <div class="flex items-center">
                <span class="w-32 text-gray-500">Aktualisiert am:</span>
                <span class="text-gray-900">{{ $document->updated_at->format('d.m.Y H:i') }}</span>
            </div>
        </div>
    </div>

    @php
        $docPreviewable = $document->mime_type && (
            $document->mime_type === 'application/pdf' ||
            str_starts_with($document->mime_type, 'image/') ||
            str_starts_with($document->mime_type, 'audio/') ||
            str_starts_with($document->mime_type, 'video/')
        );
    @endphp

    @if($docPreviewable)
    <x-admin.collapsible-card title="Vorschau" class="mt-4">
        <div class="bg-gray-100 rounded-lg p-2">
            @if($document->mime_type === 'application/pdf')
                <iframe src="{{ route('admin.documents.preview', $document) }}" class="w-full rounded-lg" style="height: 70vh;" frameborder="0"></iframe>
            @elseif(str_starts_with($document->mime_type, 'image/'))
                <div class="flex items-center justify-center p-4">
                    <img src="{{ route('admin.documents.preview', $document) }}" alt="{{ $document->title }}" class="max-w-full max-h-[70vh] object-contain rounded-lg">
                </div>
            @elseif(str_starts_with($document->mime_type, 'audio/'))
                <div class="flex flex-col items-center justify-center p-8 gap-4">
                    <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                    </div>
                    <audio src="{{ route('admin.documents.preview', $document) }}" controls class="w-full max-w-md"></audio>
                </div>
            @elseif(str_starts_with($document->mime_type, 'video/'))
                <div class="flex items-center justify-center p-4">
                    <video src="{{ route('admin.documents.preview', $document) }}" controls class="max-w-full max-h-[70vh] rounded-lg"></video>
                </div>
            @endif
        </div>
    </x-admin.collapsible-card>
    @endif

    <div class="mt-4 flex gap-3">
        @if($docPreviewable)
            <button type="button" class="px-5 py-2.5 bg-gray-800 text-white text-sm font-medium rounded-lg hover:bg-gray-700"
                @click="$dispatch('open-doc-viewer', {
                    url: '{{ route('admin.documents.preview', $document) }}',
                    title: '{{ e($document->title) }}',
                    mime: '{{ $document->mime_type }}',
                    downloadUrl: '{{ route('admin.documents.download', $document) }}'
                })">Vollbild-Vorschau</button>
        @endif
        <a href="{{ route('admin.documents.download', $document) }}" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Download</a>
        <a href="{{ route('admin.documents.index') }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50">Zurück zur Liste</a>
        @if($document->documentable_type !== \App\Models\Contract::class)
        <form method="POST" action="{{ route('admin.documents.destroy', $document) }}" onsubmit="return confirm('Dokument wirklich löschen?')">
            @csrf @method('DELETE')
            <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Löschen</button>
        </form>
        @endif
    </div>
</div>
@endsection
