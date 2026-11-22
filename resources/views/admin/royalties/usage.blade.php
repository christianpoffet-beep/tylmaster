@extends('admin.layouts.app')

@section('title', 'Nutzung – ' . ($isTrack ? $subject->display_title : $subject->title))

@php
    $int = fn ($v) => number_format((int) $v, 0, '.', "'");
    $eur = fn ($v) => number_format((float) $v, 2, '.', "'");
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
    $select = 'rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
    $filters = $usage->filters();
    $base = $isTrack ? ['track' => $subject->id] : ['release' => $subject->id];
    $maxMonth = max(1, (int) $months->max('quantity'));
    // One hue for every bar: blue-600 on light, blue-500 on dark (both checked against their surface)
    $bar = 'bg-blue-600 dark:bg-blue-500';
    $monthNames = [1 => 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
    $stats = [
        ['Streams', $int($totals['stream'])],
        ['Downloads', $int($totals['download'])],
        ['Videos', $int($totals['video'])],
        ['Gutschrift Rebeat', 'EUR ' . $eur($totals['amount'])],
    ];
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Nutzung: {{ $isTrack ? $subject->display_title : $subject->title }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ $isTrack ? ($subject->band_names ?: 'Track') . ($subject->isrc ? ' · ' . $subject->isrc_formatted : '') : 'Produkt – alle Tracks und Album-Downloads' }}
            · gemeldet von Rebeat, Gutschrift nach dessen Gebühr
        </p>
    </div>
    <a href="{{ $isTrack ? route('admin.tracks.show', $subject) : route('admin.releases.show', $subject) }}"
       class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Zurück zum {{ $isTrack ? 'Track' : 'Produkt' }}</a>
</div>

{{-- Filters: one row, scoping everything below --}}
<form method="GET" class="flex flex-wrap items-end gap-2 mb-6">
    @foreach($base as $key => $value) <input type="hidden" name="{{ $key }}" value="{{ $value }}"> @endforeach
    @php
        $filterSelects = [
            'year' => ['Jahr', collect($options['year'])->mapWithKeys(fn ($y) => [$y => $y])->all()],
            'kind' => ['Art', \App\Services\Royalties\RoyaltyUsage::KINDS],
            'country' => ['Land', $options['country']],
            'portal' => ['Dienst (DSP)', $options['portal']],
            'stream_type' => ['Abotyp', $options['stream_type']],
        ];
    @endphp
    @foreach($filterSelects as $name => [$label, $choices])
        <div>
            <label for="f-{{ $name }}" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $label }}</label>
            <select name="{{ $name }}" id="f-{{ $name }}" onchange="this.form.submit()" class="{{ $select }} max-w-[14rem]">
                <option value="">alle</option>
                @foreach($choices as $value => $text)
                    <option value="{{ $value }}" @selected((string) ($filters[$name] ?? '') === (string) $value)>{{ $text }}</option>
                @endforeach
            </select>
        </div>
    @endforeach
    @if($filters)
        <a href="{{ route('admin.royalties.usage', $base) }}" class="pb-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">Filter zurücksetzen</a>
    @endif
    <noscript><button type="submit" class="px-3 py-2 text-sm rounded-lg border">Anwenden</button></noscript>
</form>

@if($totals['lines'] === 0)
    <div class="{{ $card }} p-6 text-sm text-gray-500 dark:text-gray-400">
        {{ $filters ? 'Für diese Auswahl gibt es keine Nutzung.' : 'Rebeat hat für diesen ' . ($isTrack ? 'Track' : 'Produkt') . ' noch keine Nutzung gemeldet (oder sie ist im Rebeat-Matcher noch nicht zugeordnet).' }}
    </div>
@else
    {{-- Key figures --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach($stats as [$label, $value])
            <div class="{{ $card }} p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Per month: one series, columns on one baseline, readout on hover/focus --}}
    <div class="{{ $card }} p-5 mb-6" x-data="{ tip: null }">
        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Nutzungen pro Monat</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 min-h-[1rem]">
                <template x-if="tip"><span><strong class="text-gray-900 dark:text-gray-100" x-text="tip.q"></strong> Nutzungen · EUR <span x-text="tip.eur"></span> · <span x-text="tip.label"></span></span></template>
                <template x-if="!tip"><span>Für Werte auf eine Säule zeigen</span></template>
            </p>
        </div>
        <div class="flex">
            <div class="flex flex-col justify-between text-[10px] text-gray-400 tabular-nums pr-2 h-40 text-right">
                <span>{{ $int($maxMonth) }}</span><span>{{ $int($maxMonth / 2) }}</span><span>0</span>
            </div>
            <div class="flex-1 min-w-0 overflow-x-auto">
                <div class="relative h-40 flex items-end gap-[2px] border-b border-gray-300 dark:border-gray-600" style="min-width: {{ $months->count() * 8 }}px">
                    <div class="absolute inset-x-0 top-0 border-t border-gray-100 dark:border-gray-700/60"></div>
                    <div class="absolute inset-x-0 top-1/2 border-t border-gray-100 dark:border-gray-700/60"></div>
                    @foreach($months as $m)
                        @php $label = $monthNames[$m->month->month] . ' ' . $m->month->year; @endphp
                        <div class="relative flex-1 h-full flex items-end justify-center cursor-default outline-none group" tabindex="0"
                             aria-label="{{ $label }}: {{ $int($m->quantity) }} Nutzungen, EUR {{ $eur($m->amount) }}"
                             @mouseenter="tip = { label: @js($label), q: @js($int($m->quantity)), eur: @js($eur($m->amount)) }" @mouseleave="tip = null"
                             @focus="tip = { label: @js($label), q: @js($int($m->quantity)), eur: @js($eur($m->amount)) }" @blur="tip = null">
                            <div class="w-full max-w-[24px] rounded-t-[4px] {{ $bar }} group-hover:opacity-80 group-focus:opacity-80"
                                 style="height: {{ $m->quantity > 0 ? max(1, round(100 * $m->quantity / $maxMonth, 2)) : 0 }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="flex gap-[2px] mt-1 text-[10px] text-gray-400" style="min-width: {{ $months->count() * 8 }}px">
                    @foreach($months as $m)
                        <div class="flex-1 text-center whitespace-nowrap overflow-visible">{{ $m->month->month === 1 || $loop->first ? $m->month->year : '' }}</div>
                    @endforeach
                </div>
            </div>
        </div>
        <details class="mt-3">
            <summary class="text-xs text-blue-600 dark:text-blue-400 cursor-pointer">Als Tabelle</summary>
            <table class="mt-2 w-full max-w-md text-xs">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($months->reverse() as $m)
                        <tr>
                            <td class="py-1 text-gray-700 dark:text-gray-300">{{ $monthNames[$m->month->month] }} {{ $m->month->year }}</td>
                            <td class="py-1 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ $int($m->quantity) }}</td>
                            <td class="py-1 text-right tabular-nums text-gray-500 dark:text-gray-400">EUR {{ $eur($m->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </details>
    </div>

    {{-- Where, on which service, on which subscription: ranked bars, one hue --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        @foreach([['Länder', $countries, 'country'], ['Dienste (DSP)', $portals, 'portal'], ['Abotypen', $streamTypes, 'stream_type']] as [$title, $rows, $column])
            @php $max = max(1, (int) $rows->max('quantity')); @endphp
            <div class="{{ $card }} p-5">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">{{ $title }}</h3>
                <div class="space-y-2">
                    @foreach($rows as $row)
                        <div title="{{ $row->name }}: {{ $int($row->quantity) }} Nutzungen, EUR {{ $eur($row->amount) }}">
                            <div class="flex items-baseline justify-between gap-2 text-xs">
                                @if($row->value !== null && empty($filters[$column]))
                                    <a href="{{ route('admin.royalties.usage', $base + $filters + [$column => $row->value]) }}" class="truncate text-gray-700 dark:text-gray-300 hover:underline">{{ $row->name }}</a>
                                @else
                                    <span class="truncate text-gray-700 dark:text-gray-300">{{ $row->name }}</span>
                                @endif
                                <span class="tabular-nums text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ $int($row->quantity) }} <span class="text-gray-400">· {{ $eur($row->amount) }}</span></span>
                            </div>
                            <div class="mt-0.5 h-2">
                                <div class="h-2 rounded-r-[4px] {{ $bar }}" style="width: {{ $row->quantity > 0 ? max(1, round(100 * $row->quantity / $max, 2)) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="text-[11px] text-gray-400 mt-3">Nutzungen · Gutschrift in EUR. Anklicken filtert die ganze Seite.</p>
            </div>
        @endforeach
    </div>

    {{-- The lines themselves --}}
    <div class="{{ $card }} overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Einzelne Meldungen ({{ $int($lines->total()) }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-xs text-gray-500 dark:text-gray-400 text-left">
                        <th class="px-5 py-2 font-medium">Monat</th>
                        @unless($isTrack) <th class="px-3 py-2 font-medium">Titel</th> @endunless
                        <th class="px-3 py-2 font-medium">Land</th>
                        <th class="px-3 py-2 font-medium">Dienst</th>
                        <th class="px-3 py-2 font-medium">Abotyp</th>
                        <th class="px-3 py-2 font-medium">Art</th>
                        <th class="px-3 py-2 font-medium text-right">Anzahl</th>
                        <th class="px-5 py-2 font-medium text-right">EUR</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($lines as $line)
                        <tr>
                            <td class="px-5 py-1.5 text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $line->period->format('m.Y') }}</td>
                            @unless($isTrack) <td class="px-3 py-1.5 text-gray-700 dark:text-gray-300">{{ $line->title }}</td> @endunless
                            <td class="px-3 py-1.5 text-gray-700 dark:text-gray-300">{{ \App\Services\Royalties\RoyaltyUsage::label('country', $line->country) }}</td>
                            <td class="px-3 py-1.5 text-gray-700 dark:text-gray-300">{{ $line->portal ?? $line->shop }}</td>
                            <td class="px-3 py-1.5 text-gray-500 dark:text-gray-400">{{ $line->stream_type ?? '—' }}</td>
                            <td class="px-3 py-1.5 text-gray-500 dark:text-gray-400">{{ \App\Services\Royalties\RoyaltyUsage::KINDS[\App\Services\Royalties\RoyaltyUsage::kind($line->sales_type, $line->sales_category)] }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ $int($line->quantity) }}</td>
                            <td class="px-5 py-1.5 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ number_format((float) $line->amount, 4, '.', "'") }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($lines->hasPages())
            <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700">{{ $lines->links() }}</div>
        @endif
    </div>
@endif
@endsection
