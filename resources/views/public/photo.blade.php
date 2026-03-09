@extends('public.layouts.gallery')

@section('title', $photo->display_title)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-6">
        <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $photo->display_title }}" class="max-w-full max-h-[80vh] object-contain rounded-lg mx-auto shadow-2xl">
    </div>

    <div class="bg-gray-900 rounded-xl p-6 border border-gray-800">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-white">{{ $photo->display_title }}</h1>
            <a href="{{ url('dl/' . $photo->folder->full_slug_path . '/' . $photo->public_slug) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm rounded-lg transition-colors" title="Herunterladen">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17v3a2 2 0 002 2h14a2 2 0 002-2v-3"/></svg>
                Download
            </a>
        </div>

        @if($photo->story)
            <p class="text-gray-300 mt-3 leading-relaxed">{{ $photo->story }}</p>
        @endif

        <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm">
            @if($photo->photographer)
                <div>
                    <span class="text-gray-500">Fotograf:in:</span>
                    <span class="text-gray-300">{{ $photo->photographer }}</span>
                </div>
            @endif
            @if($photo->graphic_artist)
                <div>
                    <span class="text-gray-500">Grafic Artist:</span>
                    <span class="text-gray-300">{{ $photo->graphic_artist }}</span>
                </div>
            @endif
            @if($photo->location)
                <div>
                    <span class="text-gray-500">Ort:</span>
                    <span class="text-gray-300">{{ $photo->location }}</span>
                </div>
            @endif
            @if($photo->photo_date)
                <div>
                    <span class="text-gray-500">Datum:</span>
                    <span class="text-gray-300">{{ $photo->photo_date->format('d.m.Y') }}</span>
                </div>
            @endif
        </div>

        @if($photo->info)
            <p class="text-gray-500 text-xs mt-4">{{ $photo->info }}</p>
        @endif
    </div>
</div>
@endsection
