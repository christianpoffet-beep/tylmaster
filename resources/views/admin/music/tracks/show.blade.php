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
            <div class="flex gap-2">
                <a href="{{ route('admin.tracks.edit', $track) }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">Bearbeiten</a>
                <form method="POST" action="{{ route('admin.tracks.destroy', $track) }}" onsubmit="return confirm('Track wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white text-sm rounded-lg hover:bg-red-700 dark:hover:bg-red-600">Löschen</button>
                </form>
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

    {{-- Technical audio metadata --}}
    @if($track->audio_file)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Technische Details</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                @if($track->audio_format)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Format</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ strtoupper($track->audio_format) }}</dd>
                    </div>
                @endif
                @if($track->bitrate)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Bitrate</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $track->bitrate }} kbps</dd>
                    </div>
                @endif
                @if($track->sample_rate)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sample Rate</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ number_format($track->sample_rate) }} Hz</dd>
                    </div>
                @endif
                @if($track->channels)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Channels</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $track->channels == 1 ? 'Mono' : ($track->channels == 2 ? 'Stereo' : $track->channels . ' Channels') }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Audio Player --}}
        <x-admin.collapsible-card title="Audio Player" class="mt-6">
            <audio controls class="w-full">
                <source src="{{ Storage::url($track->audio_file) }}">
                Ihr Browser unterstützt das Audio-Element nicht.
            </audio>
        </x-admin.collapsible-card>
    @endif

    {{-- Releases --}}
    @if($track->releases->count())
        <x-admin.collapsible-card title="Produkte" :count="$track->releases->count()" class="mt-6">
            <div class="space-y-2">
                @foreach($track->releases as $release)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <a href="{{ route('admin.releases.show', $release) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">{{ $release->title }}</a>
                        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                            @if($release->pivot->track_number)
                                <span>Track {{ $release->pivot->track_number }}</span>
                            @endif
                            @if($release->pivot->disc_number && $release->pivot->disc_number > 1)
                                <span>Disc {{ $release->pivot->disc_number }}</span>
                            @endif
                            @if($release->pivot->role && $release->pivot->role !== 'main')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $release->pivot->role)) }}</span>
                            @endif
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
