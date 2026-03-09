@extends('public.layouts.gallery')

@section('title', $folder->name)

@section('content')
<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $folder->name }}</h1>
            @if($folder->description)
                <p class="text-gray-400 mt-2 max-w-2xl">{{ $folder->description }}</p>
            @endif
        </div>
        @if($folder->photos->count() || $folder->children->where('share_token', '!=', null)->count())
            @if($folder->children->where('share_token', '!=', null)->count())
            <div x-data="{ open: false, selected: [{{ $folder->id }}] }" class="relative">
                <button @click="open = true" type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm rounded-lg transition-colors whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17v3a2 2 0 002 2h14a2 2 0 002-2v-3"/></svg>
                    Herunterladen
                </button>

                {{-- Modal Overlay --}}
                <div x-show="open" x-cloak @click.self="open = false" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
                    <div @click.stop class="bg-gray-900 rounded-xl border border-gray-700 p-6 w-full max-w-sm shadow-2xl">
                        <h3 class="text-lg font-bold text-white mb-1">Download</h3>
                        <p class="text-sm text-gray-400 mb-4">Welche Ordner möchtest du herunterladen?</p>

                        <div class="space-y-2 mb-5">
                            {{-- Main folder --}}
                            @if($folder->photos->count())
                            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-800 cursor-pointer">
                                <input type="checkbox" value="{{ $folder->id }}" x-model.number="selected" class="rounded border-gray-600 bg-gray-800 text-blue-500 focus:ring-blue-500">
                                <span class="text-sm text-gray-300">{{ $folder->name }} <span class="text-gray-600">({{ $folder->photos->count() }} Fotos)</span></span>
                            </label>
                            @endif

                            {{-- Subfolders --}}
                            @foreach($folder->children->where('share_token', '!=', null) as $child)
                            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-800 cursor-pointer">
                                <input type="checkbox" value="{{ $child->id }}" x-model.number="selected" class="rounded border-gray-600 bg-gray-800 text-blue-500 focus:ring-blue-500">
                                <span class="text-sm text-gray-300">{{ $child->name }} <span class="text-gray-600">({{ $child->photos_count }} Fotos)</span></span>
                            </label>
                            @endforeach
                        </div>

                        <div class="flex gap-2">
                            <a :href="'{{ url('gallery/' . $folder->share_token . '/download') }}?folders=' + selected.join(',')"
                               @click="open = false"
                               :class="selected.length === 0 ? 'pointer-events-none opacity-40' : ''"
                               class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17v3a2 2 0 002 2h14a2 2 0 002-2v-3"/></svg>
                                <span x-text="selected.length + ' Ordner laden'"></span>
                            </a>
                            <button @click="open = false" type="button" class="px-4 py-2 text-gray-400 hover:text-white text-sm rounded-lg transition-colors">Abbrechen</button>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <a href="{{ url('gallery/' . $folder->share_token . '/download') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm rounded-lg transition-colors whitespace-nowrap" title="Alle Fotos herunterladen">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17v3a2 2 0 002 2h14a2 2 0 002-2v-3"/></svg>
                Alle herunterladen
            </a>
            @endif
        @endif
    </div>

    {{-- Subfolders --}}
    @if($folder->children->count())
    <div class="mb-8">
        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Ordner</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($folder->children as $child)
                @if($child->share_token)
                <a href="{{ $child->share_url }}" class="flex flex-col items-center p-4 bg-gray-900 rounded-lg hover:bg-gray-800 border border-gray-800 text-center transition-colors">
                    <svg class="w-8 h-8 text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    <span class="text-xs font-medium text-gray-300">{{ $child->name }}</span>
                    <span class="text-xs text-gray-600">{{ $child->photos_count }} Foto{{ $child->photos_count !== 1 ? 's' : '' }}</span>
                </a>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Photos Grid --}}
    @if($folder->photos->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        @foreach($folder->photos as $photo)
            <div class="group">
                <div class="relative aspect-square bg-gray-900 rounded-lg overflow-hidden border border-gray-800">
                    <a href="{{ $photo->public_url }}" target="_blank">
                        <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $photo->display_title }}" class="w-full h-full object-cover group-hover:opacity-80 transition-opacity" loading="lazy">
                    </a>
                    <a href="{{ url('dl/' . $photo->folder->full_slug_path . '/' . $photo->public_slug) }}" class="absolute top-2 right-2 p-1.5 bg-black/60 hover:bg-black/80 text-white rounded-lg opacity-0 group-hover:opacity-100 transition-opacity" title="Herunterladen">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17v3a2 2 0 002 2h14a2 2 0 002-2v-3"/></svg>
                    </a>
                </div>
                @if($photo->title || $photo->photographer || $photo->graphic_artist || $photo->location || $photo->photo_date || $photo->story || $photo->info)
                <div class="mt-2 px-0.5 space-y-0.5">
                    @if($photo->title)
                        <p class="text-xs text-gray-300 font-medium">{{ $photo->title }}</p>
                    @endif
                    @if($photo->photographer)
                        <p class="text-xs text-gray-500"><span class="text-gray-600">Fotograf:in:</span> {{ $photo->photographer }}</p>
                    @endif
                    @if($photo->graphic_artist)
                        <p class="text-xs text-gray-500"><span class="text-gray-600">Grafic Artist:</span> {{ $photo->graphic_artist }}</p>
                    @endif
                    @if($photo->location)
                        <p class="text-xs text-gray-500"><span class="text-gray-600">Ort:</span> {{ $photo->location }}</p>
                    @endif
                    @if($photo->photo_date)
                        <p class="text-xs text-gray-500"><span class="text-gray-600">Datum:</span> {{ $photo->photo_date->format('d.m.Y') }}</p>
                    @endif
                    @if($photo->story)
                        <p class="text-xs text-gray-500 line-clamp-2">{{ $photo->story }}</p>
                    @endif
                    @if($photo->info)
                        <p class="text-xs text-gray-600 line-clamp-1">{{ $photo->info }}</p>
                    @endif
                </div>
                @endif
            </div>
        @endforeach
    </div>
    @else
        <p class="text-gray-500 text-sm">Keine Fotos in diesem Ordner.</p>
    @endif
</div>
@endsection
