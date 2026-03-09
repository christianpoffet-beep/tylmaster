@extends('admin.layouts.app')

@section('title', 'Submission: ' . ($submission->project_name ?? $submission->track_title ?? 'Details'))

@section('content')
<div class="max-w-4xl">

    {{-- Header with status and actions --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $submission->project_name ?? $submission->track_title ?? 'Ohne Titel' }}</h2>
                <p class="text-sm text-gray-500 mt-1">von {{ $submission->artist_name ?? '-' }} &middot; Eingegangen am {{ $submission->created_at->format('d.m.Y H:i') }}</p>
                <div class="mt-2 flex items-center gap-2">
                    @switch($submission->status)
                        @case('new')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">Neu</span>
                            @break
                        @case('reviewed')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">Geprüft</span>
                            @break
                        @case('accepted')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Akzeptiert</span>
                            @break
                        @case('rejected')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Abgelehnt</span>
                            @break
                    @endswitch
                    @if($submission->payment_status === 'paid')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Bezahlt</span>
                    @elseif($submission->payment_status === 'pending')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">Zahlung ausstehend</span>
                    @endif
                </div>
            </div>

            <div class="flex gap-2">
                @if(!$submission->contact_id || !$submission->release_id || !$submission->contract_id)
                    <form method="POST" action="{{ route('admin.submissions.import', $submission) }}" onsubmit="return confirm('Submission importieren? Kontakt, Release, Tracks und Vertrag werden erstellt.')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Importieren
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.submissions.destroy', $submission) }}" onsubmit="return confirm('Submission wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">Löschen</button>
                </form>
            </div>
        </div>

        @if($submission->contact_id || $submission->release_id || $submission->contract_id)
            <div class="mt-4 pt-4 border-t border-gray-200 flex flex-wrap gap-3">
                @if($submission->contact)
                    <a href="{{ route('admin.contacts.show', $submission->contact) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 text-sm rounded-lg hover:bg-blue-100">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Kontakt: {{ $submission->contact->full_name }}
                    </a>
                @endif
                @if($submission->release)
                    <a href="{{ route('admin.releases.show', $submission->release) }}" class="inline-flex items-center px-3 py-1.5 bg-purple-50 text-purple-700 text-sm rounded-lg hover:bg-purple-100">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                        Release: {{ $submission->release->title }}
                    </a>
                @endif
                @if($submission->contract)
                    <a href="{{ route('admin.contracts.show', $submission->contract) }}" class="inline-flex items-center px-3 py-1.5 bg-amber-50 text-amber-700 text-sm rounded-lg hover:bg-amber-100">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Vertrag: {{ $submission->contract->title }}
                    </a>
                @endif
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Contact Details --}}
        <x-admin.collapsible-card title="Kontaktdaten">
            <dl class="space-y-3">
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Name</dt><dd class="text-sm text-gray-900">{{ $submission->first_name }} {{ $submission->last_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Künstlername</dt><dd class="text-sm text-gray-900">{{ $submission->artist_name ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">E-Mail</dt><dd class="text-sm text-gray-900">@if($submission->email)<a href="mailto:{{ $submission->email }}" class="text-blue-600 hover:text-blue-800">{{ $submission->email }}</a>@else - @endif</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Telefon</dt><dd class="text-sm text-gray-900">{{ $submission->phone ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Adresse</dt><dd class="text-sm text-gray-900 text-right">@if($submission->street){{ $submission->street }}<br>{{ $submission->zip }} {{ $submission->city }}<br>{{ $submission->country }}@else - @endif</dd></div>
            </dl>
        </x-admin.collapsible-card>

        {{-- Banking Details --}}
        <x-admin.collapsible-card title="Bankverbindung">
            <dl class="space-y-3">
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Kontoinhaber</dt><dd class="text-sm text-gray-900">{{ $submission->account_holder ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">IBAN</dt><dd class="text-sm text-gray-900 font-mono">{{ $submission->iban ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Bank</dt><dd class="text-sm text-gray-900">{{ $submission->bank_name ?? '-' }}</dd></div>
            </dl>
        </x-admin.collapsible-card>

        {{-- Release Details --}}
        <x-admin.collapsible-card title="Release-Details">
            <dl class="space-y-3">
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Projektname</dt><dd class="text-sm text-gray-900">{{ $submission->project_name ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Genre / Subgenre</dt><dd class="text-sm text-gray-900">{{ $submission->genre ?? '-' }} {{ $submission->subgenre ? '/ '.$submission->subgenre : '' }}</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Explicit</dt><dd class="text-sm text-gray-900">{{ $submission->explicit ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Release-Datum</dt><dd class="text-sm text-gray-900">{{ $submission->release_date?->format('d.m.Y') ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">UPC</dt><dd class="text-sm text-gray-900 font-mono">{{ $submission->upc ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Jahr Komposition</dt><dd class="text-sm text-gray-900">{{ $submission->year_composition ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Jahr Aufnahme</dt><dd class="text-sm text-gray-900">{{ $submission->year_recording ?? '-' }}</dd></div>
            </dl>
            @if($submission->other_credits)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <dt class="text-sm text-gray-500 mb-1">Weitere Credits</dt>
                    <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $submission->other_credits }}</dd>
                </div>
            @endif
        </x-admin.collapsible-card>

        {{-- Media & Bio --}}
        <x-admin.collapsible-card title="Media & Bio">
            <dl class="space-y-3">
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Website</dt><dd class="text-sm text-gray-900">@if($submission->website)<a href="{{ $submission->website }}" target="_blank" class="text-blue-600 hover:text-blue-800">{{ $submission->website }}</a>@else - @endif</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Spotify</dt><dd class="text-sm text-gray-900">@if($submission->spotify_link)<a href="{{ $submission->spotify_link }}" target="_blank" class="text-blue-600 hover:text-blue-800">Spotify-Profil</a>@else - @endif</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Instagram</dt><dd class="text-sm text-gray-900">{{ $submission->instagram ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-sm text-gray-500">Weitere Socials</dt><dd class="text-sm text-gray-900">{{ $submission->social_other ?? '-' }}</dd></div>
            </dl>
            @if($submission->bio_short)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <dt class="text-sm text-gray-500 mb-1">Kurz-Bio</dt>
                    <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $submission->bio_short }}</dd>
                </div>
            @endif
            @if($submission->bio_long)
                <div class="mt-3">
                    <dt class="text-sm text-gray-500 mb-1">Ausführliche Bio</dt>
                    <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $submission->bio_long }}</dd>
                </div>
            @endif
        </x-admin.collapsible-card>
    </div>

    {{-- Songs --}}
    @if($submission->songs_data && count($submission->songs_data) > 0)
        <x-admin.collapsible-card title="Songs" :count="count($submission->songs_data)" class="mt-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Titel</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">ISRC</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Featuring</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Songwriter</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($submission->songs_data as $i => $song)
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ $song['title'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-500 font-mono">{{ $song['isrc'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-500">{{ $song['featuring'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-500">{{ $song['songwriter'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-500">{{ $song['producer'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Contract Details --}}
    <x-admin.collapsible-card title="Vertragsdetails" class="mt-6">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
            <div class="flex justify-between"><dt class="text-sm text-gray-500">Ausgeschlossene Länder</dt><dd class="text-sm text-gray-900">{{ $submission->contract_excluded_countries ?? '-' }}</dd></div>
            <div class="flex justify-between"><dt class="text-sm text-gray-500">Vertragsende</dt><dd class="text-sm text-gray-900">{{ $submission->contract_end_date?->format('d.m.Y') ?? '-' }}</dd></div>
            <div class="flex justify-between"><dt class="text-sm text-gray-500">Vorschuss-Interesse</dt><dd class="text-sm text-gray-900">{{ $submission->contract_advance_interest ?? '-' }}</dd></div>
            <div class="flex justify-between"><dt class="text-sm text-gray-500">Digitale Signatur</dt><dd class="text-sm text-gray-900">{{ $submission->digital_signature ?? '-' }}</dd></div>
            <div class="flex justify-between"><dt class="text-sm text-gray-500">Unterzeichnet am</dt><dd class="text-sm text-gray-900">{{ $submission->contract_sign_date?->format('d.m.Y') ?? '-' }}</dd></div>
        </dl>
    </x-admin.collapsible-card>

    {{-- Payment --}}
    <x-admin.collapsible-card title="Zahlung" class="mt-6">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
            <div class="flex justify-between"><dt class="text-sm text-gray-500">Berechneter Preis</dt><dd class="text-sm text-gray-900 font-medium">{{ $submission->calculated_price ? 'CHF '.number_format($submission->calculated_price, 2, '.', "'") : '-' }}</dd></div>
            <div class="flex justify-between"><dt class="text-sm text-gray-500">Anzahl Songs</dt><dd class="text-sm text-gray-900">{{ $submission->song_count ?? '-' }}</dd></div>
            <div class="flex justify-between"><dt class="text-sm text-gray-500">Zahlungsstatus</dt><dd class="text-sm text-gray-900">@if($submission->payment_status === 'paid')<span class="text-green-600 font-medium">Bezahlt</span>@elseif($submission->payment_status === 'pending')<span class="text-yellow-600 font-medium">Ausstehend</span>@else{{ $submission->payment_status ?? '-' }}@endif</dd></div>
            <div class="flex justify-between"><dt class="text-sm text-gray-500">Zugangscode</dt><dd class="text-sm text-gray-900 font-mono">{{ $submission->access_code ?? '-' }}</dd></div>
        </dl>
    </x-admin.collapsible-card>

    {{-- File & Cover --}}
    @if($submission->file_path || $submission->cover_image_path)
        <x-admin.collapsible-card title="Dateien" class="mt-6">
            <div class="flex flex-wrap gap-4">
                @if($submission->file_path)
                    <a href="{{ Storage::url($submission->file_path) }}" download class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Audio-Datei
                    </a>
                @endif
                @if($submission->cover_image_path)
                    <a href="{{ Storage::url($submission->cover_image_path) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Cover-Bild
                    </a>
                @endif
            </div>
        </x-admin.collapsible-card>
    @endif

    {{-- Message --}}
    @if($submission->message)
        <x-admin.collapsible-card title="Nachricht" class="mt-6">
            <p class="text-sm text-gray-900 whitespace-pre-line">{{ $submission->message }}</p>
        </x-admin.collapsible-card>
    @endif

    {{-- Status Update --}}
    <x-admin.collapsible-card title="Status ändern" class="mt-6">
        <form method="POST" action="{{ route('admin.submissions.updateStatus', $submission) }}" class="flex gap-2">
            @csrf
            @method('PATCH')
            <select name="status" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach(['new' => 'Neu', 'reviewed' => 'Geprüft', 'accepted' => 'Akzeptiert', 'rejected' => 'Abgelehnt'] as $value => $label)
                    <option value="{{ $value }}" {{ $submission->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Aktualisieren</button>
        </form>
    </x-admin.collapsible-card>

    <div class="mt-4">
        <a href="{{ route('admin.submissions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Zurück zur Übersicht</a>
    </div>
</div>
@endsection
