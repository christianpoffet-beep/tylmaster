@extends('admin.layouts.app')

@section('title', $artwork->title)

@section('content')
<div class="max-w-4xl">
    <div class="mb-4">
        <a href="{{ route('admin.artworks.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Zurück zur Übersicht</a>
    </div>

    @if(session('warning'))
    <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
        {{ session('warning') }}
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $artwork->title }}</h2>
                @if($artwork->projects->count())
                    <p class="text-sm text-gray-500 mt-1">
                        Projekte:
                        @foreach($artwork->projects as $project)
                            <a href="{{ route('admin.projects.show', $project) }}" class="text-blue-600 hover:text-blue-800">{{ $project->name }}</a>{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </p>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.artworks.edit', $artwork) }}" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Bearbeiten</a>
                <form method="POST" action="{{ route('admin.artworks.destroy', $artwork) }}" onsubmit="return confirm('Artwork wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">Löschen</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Artwork-Bild --}}
    @if($artwork->artwork_path)
    <x-admin.collapsible-card title="Artwork" class="mt-6">
        <div class="rounded-lg overflow-hidden bg-gray-100 inline-block">
            @if(in_array($artwork->artwork_mime_type, ['image/jpeg', 'image/jpg']))
                <img src="{{ $artwork->artwork_url }}" alt="{{ $artwork->title }}" class="max-w-md max-h-96 object-contain">
            @else
                <div class="w-64 h-64 flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <p class="text-lg font-medium">{{ strtoupper(pathinfo($artwork->artwork_original_name, PATHINFO_EXTENSION)) }}</p>
                        <p class="text-xs mt-1">{{ $artwork->artwork_original_name }}</p>
                    </div>
                </div>
            @endif
        </div>
        <div class="mt-2 text-xs text-gray-400">
            {{ $artwork->artwork_original_name }} &middot; {{ number_format(($artwork->artwork_file_size ?? 0) / 1024 / 1024, 1) }} MB
        </div>
    </x-admin.collapsible-card>
    @endif

    {{-- Credits --}}
    @php
        $creditRoles = [
            'photographer' => 'Fotograf:in',
            'artwork_by' => 'Artwork by',
            'logo_by' => 'Logo by',
            'design_by' => 'Design by',
        ];
        $groupedCredits = $artwork->credits->groupBy('role');
        $hasCredits = $groupedCredits->isNotEmpty() || $artwork->yoc;
    @endphp
    <x-admin.collapsible-card title="Credits" class="mt-6">
        @if($hasCredits)
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            @foreach($creditRoles as $role => $roleLabel)
                @if($groupedCredits->has($role))
                <div>
                    <dt class="text-gray-500">{{ $roleLabel }}</dt>
                    <dd class="text-gray-900 mt-0.5">
                        @foreach($groupedCredits[$role] as $credit)
                            @if($credit->creditable_type === \App\Models\Contact::class)
                                <a href="{{ route('admin.contacts.show', $credit->creditable_id) }}" class="text-blue-600 hover:text-blue-800">{{ $credit->display_name }}</a>
                            @elseif($credit->creditable_type === \App\Models\Organization::class)
                                <a href="{{ route('admin.organizations.show', $credit->creditable_id) }}" class="text-purple-600 hover:text-purple-800">{{ $credit->display_name }}</a>
                            @endif
                            @if(!$loop->last), @endif
                        @endforeach
                    </dd>
                </div>
                @endif
            @endforeach
            @if($artwork->yoc)
            <div>
                <dt class="text-gray-500">YOC (Year of Creation)</dt>
                <dd class="text-gray-900 mt-0.5">{{ $artwork->yoc }}</dd>
            </div>
            @endif
        </dl>
        @else
            <p class="text-sm text-gray-400">Keine Credits erfasst.</p>
        @endif
    </x-admin.collapsible-card>

    {{-- Logos --}}
    <x-admin.collapsible-card title="Logos" :count="$artwork->logos->count()" class="mt-6">
        @if($artwork->logos->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($artwork->logos as $logo)
            <div class="group relative bg-gray-50 rounded-lg overflow-hidden border border-gray-200">
                <div class="aspect-square flex items-center justify-center p-2">
                    <img src="{{ $logo->url }}" alt="{{ $logo->original_name }}" class="max-w-full max-h-full object-contain">
                </div>
                <div class="p-2 border-t border-gray-200">
                    <p class="text-xs text-gray-700 truncate" title="{{ $logo->original_name }}">{{ $logo->original_name }}</p>
                    @if($logo->comment)
                        <p class="text-xs text-gray-400 truncate" title="{{ $logo->comment }}">{{ $logo->comment }}</p>
                    @endif
                </div>
                <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <a href="{{ Storage::url($logo->file_path) }}" download="{{ $logo->original_name }}" class="inline-flex items-center justify-center w-7 h-7 bg-white rounded-full shadow text-gray-500 hover:text-blue-600" title="Download">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @else
            <p class="text-sm text-gray-400">Keine Logos vorhanden.</p>
        @endif
    </x-admin.collapsible-card>
</div>
@endsection
