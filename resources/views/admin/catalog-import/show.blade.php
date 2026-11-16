@extends('admin.layouts.app')

@section('title', 'Katalog-Import')

@php
    $badge = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap';
    $isNew = $badge . ' bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300';
    $isThere = $badge . ' bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300';
    $statusBadge = [
        'draft' => [$badge . ' bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300', 'Draft'],
        'released' => [$badge . ' bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300', 'Released'],
        'archived' => [$badge . ' bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300', 'Archived'],
    ];
    $th = 'px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase';
    $td = 'px-3 py-2 text-sm text-gray-700 dark:text-gray-300';
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
    $duration = fn ($s) => $s === null ? '-' : sprintf('%d:%02d', intdiv($s, 60), $s % 60);
@endphp

@section('content')
<div class="max-w-6xl space-y-4">

    <div class="{{ $card }} p-6">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Einmaliger Import der Produkte und Tracks aus der SWISSPERFORM-Liste, samt Bands und Labels.
            Die Vorschau prüft die Datei gegen die Daten, die schon da sind. Vorhandenes wird nicht verändert, nur verknüpft:
            Produkte erkennt der Import an der EAN (sonst Katalognummer, sonst Titel), Tracks an der ISRC, Bands und Labels am Namen.
            Der Import speichert alles oder nichts.
        </p>
        <p class="mt-2 text-sm font-medium text-amber-700 dark:text-amber-300">Vor dem Import im Hosting-Panel ein Backup der Datenbank machen.</p>
    </div>

    @if($result = session('importResult'))
        <div class="{{ $card }} p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Import abgeschlossen</h2>
            <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                <li>{{ $result['products'] }} Produkte, {{ $result['tracks'] }} Tracks und {{ $result['organizations'] }} Bands / Labels neu angelegt</li>
                <li>{{ $result['links'] }} Tracks in Tracklisten eingetragen</li>
            </ul>
            <div class="mt-3 flex gap-4 text-sm">
                <a href="{{ route('admin.releases.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Zu den Produkten</a>
                <a href="{{ route('admin.tracks.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Zu den Tracks</a>
            </div>
        </div>
    @endif

    @if($fileError)
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg text-sm">
            Die hochgeladene Datei lässt sich nicht lesen: {{ $fileError }}
        </div>
    @endif

    @if(!$plan)
        <form method="POST" action="{{ route('admin.catalog-import.upload') }}" enctype="multipart/form-data" class="{{ $card }} p-6">
            @csrf
            <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Importdatei (.json)</label>
            <input type="file" name="file" id="file" accept=".json,application/json" required
                   class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-gray-100 dark:file:bg-gray-700 file:text-gray-700 dark:file:text-gray-200">
            @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            <button type="submit" class="mt-4 px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">Hochladen und Vorschau zeigen</button>
        </form>
    @endif

    @if($fileError || $plan)
        <form method="POST" action="{{ route('admin.catalog-import.discard') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">Datei verwerfen und neu hochladen</button>
        </form>
    @endif

    @if($plan)
        @php $c = $plan['counts']; @endphp

        <div class="{{ $card }} p-6">
            <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Datei: <span class="text-gray-900 dark:text-gray-100">{{ $meta['source'] ?: '-' }}</span>
                @if($meta['created']) · erstellt {{ $meta['created'] }} @endif
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach(['Produkte' => 'products', 'Tracks' => 'tracks', 'Bands' => 'bands', 'Labels' => 'labels'] as $label => $key)
                    <div>
                        <div class="text-xs uppercase text-gray-500 dark:text-gray-400">{{ $label }}</div>
                        <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $c[$key . '_new'] }} <span class="text-sm font-normal text-gray-500 dark:text-gray-400">neu</span></div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $c[$key . '_existing'] }} schon vorhanden</div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($plan['errors'])
            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3">
                <h2 class="text-sm font-semibold text-red-700 dark:text-red-300 mb-1">{{ count($plan['errors']) }} Fehler - der Import ist gesperrt</h2>
                <ul class="list-disc pl-5 text-sm text-red-700 dark:text-red-300 space-y-0.5">
                    @foreach($plan['errors'] as $message) <li>{{ $message }}</li> @endforeach
                </ul>
            </div>
        @endif

        @if($plan['warnings'])
            <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3">
                <h2 class="text-sm font-semibold text-amber-700 dark:text-amber-300 mb-1">{{ count($plan['warnings']) }} Hinweise</h2>
                <ul class="list-disc pl-5 text-sm text-amber-700 dark:text-amber-300 space-y-0.5">
                    @foreach($plan['warnings'] as $message) <li>{{ $message }}</li> @endforeach
                </ul>
            </div>
        @endif

        <div class="{{ $card }} p-6">
            <form method="POST" action="{{ route('admin.catalog-import.run') }}"
                  onsubmit="if (!confirm('Katalog jetzt importieren? Vorher ein Datenbank-Backup machen.')) return false; this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Import läuft …';">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <button type="submit" @disabled($plan['errors'])
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Import ausführen
                </button>
                <span class="ml-3 text-sm text-gray-500 dark:text-gray-400">
                    {{ $c['products_new'] }} Produkte und {{ $c['tracks_new'] }} Tracks anlegen
                </span>
            </form>
        </div>

        {{-- Bands & Labels --}}
        <details class="{{ $card }}">
            <summary class="p-4 cursor-pointer text-sm font-semibold text-gray-900 dark:text-gray-100">Bands &amp; Labels ({{ count($plan['organizations']) }})</summary>
            <div class="overflow-x-auto border-t border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr><th class="{{ $th }}">Typ</th><th class="{{ $th }}">Name</th><th class="{{ $th }}">Kataloge</th><th class="{{ $th }}"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach(collect($plan['organizations'])->sortBy([['type', 'desc'], fn ($a, $b) => strcasecmp($a['name'], $b['name'])]) as $org)
                            <tr>
                                <td class="{{ $td }}">{{ $org['type'] === 'band' ? 'Band' : 'Label' }}</td>
                                <td class="{{ $td }} font-medium text-gray-900 dark:text-gray-100">{{ $org['name'] }}</td>
                                <td class="{{ $td }}">{{ $org['existing'] ? implode(', ', (array) $org['existing']->catalogs) : implode(', ', $org['catalogs']) }}</td>
                                <td class="{{ $td }}">
                                    @if($org['existing'])
                                        <a href="{{ route('admin.organizations.show', $org['existing']) }}" class="{{ $isThere }}" target="_blank">vorhanden #{{ $org['existing']->id }}</a>
                                    @else
                                        <span class="{{ $isNew }}">neu</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>

        {{-- Products with their tracklists --}}
        <div class="space-y-2">
            @foreach($plan['products'] as $p)
                <details class="{{ $card }}">
                    <summary class="p-4 cursor-pointer flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                        <span class="font-mono text-gray-500 dark:text-gray-400 w-16">{{ $p['catalog_number'] ?? '-' }}</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $p['title'] }}</span>
                        <span class="text-gray-600 dark:text-gray-300">{{ implode(', ', $p['bands']) }}</span>
                        <span class="text-gray-500 dark:text-gray-400">{{ $p['label'] ?? 'ohne Label' }}</span>
                        <span class="text-gray-500 dark:text-gray-400">{{ $p['release_date'] ? \Illuminate\Support\Carbon::parse($p['release_date'])->format('d.m.Y') : 'ohne Datum' }}</span>
                        <span class="text-gray-500 dark:text-gray-400 font-mono">{{ $p['ean_upc'] ?? 'ohne EAN' }}</span>
                        <span class="text-gray-500 dark:text-gray-400">{{ implode(', ', $p['product_type']) }}</span>
                        <span class="text-gray-500 dark:text-gray-400">{{ count($p['tracks']) }} Tracks</span>
                        @if($p['existing'])
                            <a href="{{ route('admin.releases.show', $p['existing']) }}" class="{{ $isThere }}" target="_blank">vorhanden #{{ $p['existing']->id }} ({{ $p['matched_by'] }})</a>
                        @else
                            <span class="{{ $isNew }}">neu</span>
                        @endif
                    </summary>
                    <div class="overflow-x-auto border-t border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="{{ $th }}">#</th>
                                    <th class="{{ $th }}">Titel</th>
                                    @if(count($p['bands']) > 1) <th class="{{ $th }}">Band</th> @endif
                                    <th class="{{ $th }}">ISRC</th>
                                    <th class="{{ $th }}">Dauer</th>
                                    <th class="{{ $th }}">Genre</th>
                                    <th class="{{ $th }}">Aufnahme</th>
                                    <th class="{{ $th }}">Status</th>
                                    <th class="{{ $th }}"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($p['tracks'] as $t)
                                    <tr>
                                        <td class="{{ $td }} text-gray-500 dark:text-gray-400">{{ $t['number'] }}</td>
                                        <td class="{{ $td }} text-gray-900 dark:text-gray-100">
                                            {{ $t['title'] }}@if($t['version']) <span class="text-gray-500 dark:text-gray-400">({{ $t['version'] }})</span>@endif
                                        </td>
                                        @if(count($p['bands']) > 1) <td class="{{ $td }}">{{ $t['band'] }}</td> @endif
                                        <td class="{{ $td }} font-mono whitespace-nowrap">{{ \App\Models\Track::formatIsrc($t['isrc']) ?? '-' }}</td>
                                        <td class="{{ $td }}">{{ $duration($t['duration_seconds']) }}</td>
                                        <td class="{{ $td }}">{{ $t['genre'] ?? '-' }}</td>
                                        <td class="{{ $td }}">{{ $t['recording_years'] ?? '-' }}</td>
                                        <td class="{{ $td }}"><span class="{{ $statusBadge[$t['status']][0] ?? $badge }}">{{ $statusBadge[$t['status']][1] ?? $t['status'] }}</span></td>
                                        <td class="{{ $td }}">
                                            @if($t['existing'])
                                                <a href="{{ route('admin.tracks.show', $t['existing']) }}" class="{{ $isThere }}" target="_blank">vorhanden #{{ $t['existing']->id }}{{ $t['linked'] ? ', schon in der Tracklist' : '' }}</a>
                                            @else
                                                <span class="{{ $isNew }}">neu</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endforeach
        </div>
    @endif

</div>
@endsection
