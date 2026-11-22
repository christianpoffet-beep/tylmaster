@extends('admin.layouts.app')

@section('title', 'Verträge für die Abrechnung')

@php
    $eur = fn ($value) => number_format((float) $value, 2, '.', "'");
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
    $input = 'rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
    $statusLabels = ['draft' => 'Entwurf', 'active' => 'Aktiv', 'expired' => 'Ausgelaufen', 'terminated' => 'Gekündigt'];
    $badges = [
        \App\Services\Royalties\RoyaltyCoverage::OK => ['abgedeckt', 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'],
        \App\Services\Royalties\RoyaltyCoverage::OTHER_LABEL => ['100 % ans Label', 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'],
        \App\Services\Royalties\RoyaltyCoverage::DRAFT => ['nur Entwurf', 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'],
        \App\Services\Royalties\RoyaltyCoverage::NO_SPLIT => ['kein Streaming-Split', 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'],
        \App\Services\Royalties\RoyaltyCoverage::MISSING => ['Vertrag fehlt', 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'],
    ];
    $rows = $products->concat($looseTracks);
    $summary = collect($badges)->map(fn ($badge, $status) => [
        'label' => $badge[0], 'class' => $badge[1],
        'count' => $rows->where('status', $status)->count(),
        'amount' => $rows->where('status', $status)->sum('amount'),
    ])->filter(fn ($s) => $s['count'] > 0);
    $splitText = function ($contract) {
        $split = $contract->splitFor('streaming');
        if ($split === null) return null;
        return collect($split)->map(fn ($s) => rtrim(rtrim(number_format($s['percent'], 2, '.', ''), '0'), '.') . ' % '
            . ($s['party']->organization?->primary_name ?? $s['party']->contact?->full_name ?? '?'))->join(' · ');
    };
    $createUrl = fn (array $params) => route('admin.contracts.create', $params + ($template ? ['template' => $template->id] : []));
@endphp

@section('content')
@include('admin.royalties._tabs')

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Verträge für die Abrechnung</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pro Produkt: die Nutzung bei Rebeat (seit dem Stichtag, in EUR) und der Vertrag, der sie aufteilt. Die grössten Beträge zuerst.</p>
    </div>
    <a href="{{ route('admin.royalties.index') }}" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Rebeat-Matcher</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="{{ $card }} p-5 space-y-4">
        @php $ownIds = $own->pluck('id')->all(); @endphp
        <form method="POST" action="{{ route('admin.royalties.ownLabel') }}" x-data="{ filter: '' }">
            @csrf
            <div class="flex items-center justify-between gap-2 mb-1">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 inline-flex items-center gap-1.5">
                    Eigene Labels ({{ count($ownIds) }})
                    <x-admin.info-tip>
                        The yelling light und die Labels, die ihm gehören. Ihr Anteil in einem Vertrag bleibt Ertrag und wird niemandem gutgeschrieben.
                        Ihre Produkte brauchen einen Vertrag mit den Künstler:innen. Produkte <em>anderer</em> Labels ohne Vertrag gehen dagegen zu 100 % an das Label.
                    </x-admin.info-tip>
                </span>
                <input type="search" x-model="filter" placeholder="Label suchen" class="w-36 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs py-1 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="max-h-40 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($labels->sortBy(fn ($l) => [in_array($l->id, $ownIds, true) ? 0 : 1, mb_strtolower($l->primary_name)]) as $label)
                    <label class="flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                           x-show="!filter || @js(mb_strtolower($label->all_names)).includes(filter.toLowerCase())">
                        <input type="checkbox" name="organization_ids[]" value="{{ $label->id }}" @checked(in_array($label->id, $ownIds, true))
                               class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        <span>{{ $label->primary_name }}</span>
                        <span class="text-xs text-gray-400">#{{ $label->id }}</span>
                    </label>
                @endforeach
            </div>
            @error('organization_ids') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            <button type="submit" class="mt-2 px-3 py-1.5 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">Eigene Labels speichern</button>
        </form>

        <form method="GET" class="flex flex-wrap items-end gap-2">
            <div class="flex-1 min-w-[12rem]">
                <label for="template" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Vorlage für neue Verträge</label>
                <select name="template" id="template" onchange="this.form.submit()" class="w-full {{ $input }}">
                    @foreach($templates as $option)
                        <option value="{{ $option->id }}" @selected($template?->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>
            <span class="inline-flex items-center gap-1.5 pb-2">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="all" value="1" @checked($all) onchange="this.form.submit()" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    auch Produkte ohne Rebeat-Nutzung
                </label>
                <x-admin.info-tip>
                    Normalerweise zeigt die Liste nur Produkte, für die Rebeat seit dem Stichtag Nutzung gemeldet hat – nur sie kommen in die nächste Abrechnung.
                    Mit Häkchen erscheint der ganze Katalog, auch Produkte ohne Streams und Downloads, etwa um Verträge für neue Veröffentlichungen schon im Voraus zu erfassen.
                </x-admin.info-tip>
            </span>
        </form>
    </div>

    <div class="{{ $card }} p-5">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Stand</h3>
        @if($summary->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">Noch keine Nutzungsdaten – zuerst im Rebeat-Matcher einen Export einlesen.</p>
        @else
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($summary as $row)
                        <tr>
                            <td class="py-1.5"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $row['class'] }}">{{ $row['label'] }}</span></td>
                            <td class="py-1.5 text-right tabular-nums text-gray-500 dark:text-gray-400">{{ $row['count'] }}</td>
                            <td class="py-1.5 text-right tabular-nums text-gray-900 dark:text-gray-100">EUR {{ $eur($row['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        @if($openAmount > 0.005)
            <p class="text-xs text-amber-600 dark:text-amber-400 mt-3">EUR {{ $eur($openAmount) }} sind noch keinem Track zugeordnet – <a href="{{ route('admin.royalties.index') }}" class="underline">im Rebeat-Matcher zuordnen</a>.</p>
        @endif
    </div>
</div>

<div class="{{ $card }} overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Produkte ({{ $products->count() }})</h3>
    </div>
    @if($products->isEmpty())
        <p class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">Keine Produkte mit Nutzung.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-xs text-gray-500 dark:text-gray-400 text-left">
                        <th class="px-5 py-2 font-medium">Produkt</th>
                        <th class="px-3 py-2 font-medium text-right">EUR</th>
                        <th class="px-3 py-2 font-medium">Vertrag · Streaming-Split</th>
                        <th class="px-5 py-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($products as $row)
                        @include('admin.royalties._contract-row', [
                            'title' => $row->release->title,
                            'url' => route('admin.releases.show', $row->release),
                            'subtitle' => trim($row->bands . ($row->label ? ' · ℗ ' . $row->label->primary_name : '') . ' · ' . $row->release->tracks->count() . ' Tracks', ' ·'),
                            'createUrl' => $createUrl(['release' => $row->release->id]),
                        ])
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@if($looseTracks->isNotEmpty())
    <div class="{{ $card }} overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Tracks ohne Produkt ({{ $looseTracks->count() }})</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Diese Tracks haben Nutzung, gehören aber zu keinem Produkt. Am besten dem Produkt zuordnen – oder einen eigenen Vertrag erfassen.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($looseTracks as $row)
                        @include('admin.royalties._contract-row', [
                            'title' => $row->track->display_title,
                            'url' => route('admin.tracks.show', $row->track),
                            'subtitle' => trim($row->track->band_names . ($row->label ? ' · ℗ ' . $row->label->primary_name : '') . ($row->track->isrc ? ' · ' . $row->track->isrc_formatted : ''), ' ·'),
                            'createUrl' => $createUrl(['track' => $row->track->id]),
                        ])
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
