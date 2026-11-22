@extends('admin.layouts.app')

@section('title', 'Rebeat-Import – ' . $import->file_name)

@php
    $eur = fn ($value) => number_format((float) $value, 2, '.', "'");
    $int = fn ($value) => number_format((int) $value, 0, '.', "'");
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
    $input = 'rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
    $matchLabels = [
        \App\Models\RoyaltyLine::MATCH_ISRC => ['ISRC', 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300', 'Gleiche ISRC wie der Track'],
        \App\Models\RoyaltyLine::MATCH_RULE => ['gemerkt', 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300', 'Früher von Hand zugeordnet'],
        \App\Models\RoyaltyLine::MATCH_MANUAL => ['von Hand', 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300', 'Hier von Hand zugeordnet'],
    ];
    $openAmount = $open->sum('amount');
    $fee = (float) $totals->gross - (float) $totals->amount;
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $import->source_label }}-Import</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ $import->file_name }} · Nutzungsmonate {{ $import->period_from?->format('m.Y') }} – {{ $import->period_to?->format('m.Y') }} · eingelesen {{ $import->created_at->format('d.m.Y H:i') }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        @if($import->file_path)
            <a href="{{ route('admin.royalties.file', $import) }}" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Datei</a>
        @endif
        <form method="POST" action="{{ route('admin.royalties.destroy', $import) }}" onsubmit="return confirm('Diesen Import mit allen {{ $int($totals->n) }} Zeilen entfernen?')">
            @csrf @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-red-50 dark:hover:bg-red-900/20">Entfernen</button>
        </form>
        <a href="{{ route('admin.royalties.index') }}" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Zurück</a>
    </div>
</div>

{{-- Status --}}
@if($import->is_confirmed)
    <div class="mb-6 rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <p class="text-sm text-green-800 dark:text-green-200">Bestätigt am {{ $import->confirmed_at->format('d.m.Y') }} – die Zeilen kommen in die nächste Abrechnung.</p>
        <form method="POST" action="{{ route('admin.royalties.confirm', $import) }}">
            @csrf @method('PATCH')
            <button type="submit" class="text-sm text-green-700 dark:text-green-300 hover:underline">Bestätigung aufheben</button>
        </form>
    </div>
@else
    <div class="mb-6 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <p class="text-sm text-amber-800 dark:text-amber-200">
            Prüfe die Zuordnung und bestätige den Import. Erst bestätigte Importe kommen in eine Abrechnung.
            @if($open->isNotEmpty())
                <br>{{ $open->count() }} {{ $open->count() === 1 ? 'Titel hat' : 'Titel haben' }} noch keinen Track ({{ $eur($openAmount) }} EUR).
            @endif
        </p>
        <form method="POST" action="{{ route('admin.royalties.confirm', $import) }}">
            @csrf @method('PATCH')
            <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 whitespace-nowrap">Import bestätigen</button>
        </form>
    </div>
@endif

{{-- Kennzahlen --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="{{ $card }} p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400">Zeilen</p>
        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 tabular-nums">{{ $int($totals->n) }}</p>
        @if($import->rows_duplicate || $import->rows_presettled)
            <p class="text-xs text-gray-400 mt-0.5">
                {{ $import->rows_duplicate ? $int($import->rows_duplicate) . ' waren schon da' : '' }}{{ $import->rows_duplicate && $import->rows_presettled ? ' · ' : '' }}{{ $import->rows_presettled ? $int($import->rows_presettled) . ' schon abgerechnet' : '' }}
            </p>
        @endif
    </div>
    <div class="{{ $card }} p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400">Nutzungen</p>
        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 tabular-nums">{{ $int($totals->quantity) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">Streams, Downloads, Videos</p>
    </div>
    <div class="{{ $card }} p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400">Gutschrift</p>
        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 tabular-nums">EUR {{ $eur($totals->amount) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">brutto {{ $eur($totals->gross) }}, Rebeat-Gebühr {{ $eur($fee) }}</p>
    </div>
    <div class="{{ $card }} p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400">Ohne Track</p>
        <p class="text-lg font-semibold tabular-nums {{ $open->isNotEmpty() ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-gray-100' }}">EUR {{ $eur($openAmount) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ $open->count() }} {{ $open->count() === 1 ? 'Titel' : 'Titel' }} offen, {{ $assigned->count() }} zugeordnet</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="{{ $card }} p-5">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Nach Label (℗-Zeile)</h3>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($byLabel as $row)
                    <tr>
                        <td class="py-1.5 text-gray-900 dark:text-gray-100">{{ $row->label ?? '—' }}</td>
                        <td class="py-1.5 text-right tabular-nums text-gray-500 dark:text-gray-400">{{ $int($row->n) }} Zeilen</td>
                        <td class="py-1.5 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ $eur($row->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="{{ $card }} p-5">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Nach Art</h3>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($byType as $row)
                    <tr>
                        <td class="py-1.5 text-gray-900 dark:text-gray-100">{{ $row->sales_type ?? '—' }}{{ $row->sales_category ? ' · ' . $row->sales_category : '' }}</td>
                        <td class="py-1.5 text-right tabular-nums text-gray-500 dark:text-gray-400">{{ $int($row->quantity) }}×</td>
                        <td class="py-1.5 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ $eur($row->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Offen --}}
<div id="offen" class="{{ $card }} mb-6 overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Ohne Track ({{ $open->count() }})</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Einmal zuordnen genügt: Die Zuordnung gilt für alle Zeilen mit dieser ISRC und wird für die nächsten Importe gemerkt.</p>
    </div>
    @if($open->isEmpty())
        <p class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">Alle Zeilen haben einen Track.</p>
    @else
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($open as $group)
                @php
                    $picker = [
                        'kind' => $group->isrc ? 'track' : 'release',
                        'url' => $group->isrc ? route('admin.tracks.search') : route('admin.releases.search'),
                        'query' => $group->title,
                    ];
                @endphp
                <div class="px-5 py-4" x-data="royaltyPicker(@js($picker))">
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $group->title ?? '—' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $group->artist ?? '—' }}
                                · {{ $group->isrc ? 'ISRC ' . \App\Models\Track::formatIsrc($group->isrc) : 'ohne ISRC (Album-Download)' }}
                                @if($group->label) · ℗ {{ $group->label }} @endif
                            </p>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 tabular-nums whitespace-nowrap">
                            {{ $int($group->n) }} Zeilen · {{ $int($group->quantity) }}× · <span class="text-gray-900 dark:text-gray-100 font-medium">EUR {{ $eur($group->amount) }}</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.royalties.assign', $import) }}" x-ref="form" class="mt-3">
                        @csrf
                        <input type="hidden" name="isrc" value="{{ $group->isrc }}">
                        <input type="hidden" name="artist" value="{{ $group->artist }}">
                        <input type="hidden" name="title" value="{{ $group->title }}">
                        <input type="hidden" name="{{ $group->isrc ? 'track_id' : 'release_id' }}" x-ref="target">

                        <div class="flex flex-wrap items-center gap-2">
                            @foreach($group->suggestions as $suggestion)
                                <button type="button" @click="choose({{ $suggestion['id'] }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60"
                                        title="{{ $suggestion['isrc'] ? 'ISRC im Tool: ' . $suggestion['isrc'] : '' }}">
                                    → {{ $suggestion['title'] }}{{ $suggestion['artist'] ? ' · ' . $suggestion['artist'] : '' }}
                                    <span class="text-blue-400 dark:text-blue-400/70">{{ $suggestion['score'] }}%</span>
                                </button>
                            @endforeach
                            <button type="button" @click="toggle()" class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                                {{ $group->isrc ? 'Track suchen' : 'Produkt suchen' }}
                            </button>
                        </div>
                    </form>

                    @if($group->isrc && !$group->isrc_taken)
                        <form method="POST" action="{{ route('admin.royalties.createTrack', $import) }}" class="mt-2"
                              onsubmit="return confirm({{ \Illuminate\Support\Js::from('Track «' . $group->title . '» mit der ISRC ' . \App\Models\Track::formatIsrc($group->isrc) . ' neu anlegen?') }})">
                            @csrf
                            <input type="hidden" name="isrc" value="{{ $group->isrc }}">
                            <input type="hidden" name="artist" value="{{ $group->artist }}">
                            <input type="hidden" name="title" value="{{ $group->title }}">
                            <input type="hidden" name="label" value="{{ $group->label }}">
                            <button type="submit" class="text-xs text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:underline">
                                Gibt es den Track noch nicht? Neu anlegen
                            </button>
                        </form>
                    @endif

                    @include('admin.royalties._picker')
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Zugeordnet --}}
<div id="zugeordnet" class="{{ $card }} overflow-hidden" x-data="{ show: false }">
    <button type="button" @click="show = !show" class="w-full px-5 py-3 flex items-center justify-between text-left border-b border-gray-200 dark:border-gray-700">
        <span>
            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Zugeordnet ({{ $assigned->count() }})</span>
            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">EUR {{ $eur($assigned->sum('amount')) }} · zum Prüfen aufklappen</span>
        </span>
        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="show ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
    </button>
    <div x-show="show" x-cloak class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr class="text-xs text-gray-500 dark:text-gray-400 text-left">
                    <th class="px-5 py-2 font-medium">Track</th>
                    <th class="px-3 py-2 font-medium">Bei Rebeat</th>
                    <th class="px-3 py-2 font-medium">über</th>
                    <th class="px-3 py-2 font-medium text-right">Nutzungen</th>
                    <th class="px-3 py-2 font-medium text-right">EUR</th>
                    <th class="px-5 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($assigned as $group)
                    @php
                        [$matchLabel, $matchClass, $matchTitle] = $matchLabels[$group->match_type] ?? ['?', 'bg-gray-100 text-gray-700', ''];
                        $targetTitle = $group->track?->display_title ?? $group->release?->title;
                        $picker = [
                            'kind' => $group->isrc ? 'track' : 'release',
                            'url' => $group->isrc ? route('admin.tracks.search') : route('admin.releases.search'),
                            'query' => $group->title,
                        ];
                    @endphp
                    <tr class="align-top" x-data="royaltyPicker(@js($picker))">
                        <td class="px-5 py-2.5">
                            @if($group->track)
                                <a href="{{ route('admin.tracks.show', $group->track) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $targetTitle }}</a>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $group->track->band_names ?: '—' }}{{ $group->track->isrc ? ' · ' . $group->track->isrc_formatted : '' }}</span>
                            @elseif($group->release)
                                <a href="{{ route('admin.releases.show', $group->release) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $targetTitle }}</a>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Produkt</span>
                            @endif
                            <div x-show="open" x-cloak class="mt-2">
                                <form method="POST" action="{{ route('admin.royalties.assign', $import) }}" x-ref="form">
                                    @csrf
                                    <input type="hidden" name="isrc" value="{{ $group->isrc }}">
                                    <input type="hidden" name="artist" value="{{ $group->artist }}">
                                    <input type="hidden" name="title" value="{{ $group->title }}">
                                    <input type="hidden" name="{{ $group->isrc ? 'track_id' : 'release_id' }}" x-ref="target">
                                </form>
                                @include('admin.royalties._picker')
                            </div>
                        </td>
                        <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300">
                            {{ $group->title }}
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $group->artist }}{{ $group->isrc ? ' · ' . \App\Models\Track::formatIsrc($group->isrc) : '' }}</span>
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $matchClass }}" title="{{ $matchTitle }}">{{ $matchLabel }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $int($group->quantity) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ $eur($group->amount) }}</td>
                        <td class="px-5 py-2.5 text-right whitespace-nowrap">
                            <button type="button" @click="toggle()" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">ändern</button>
                            @if($group->match_type !== \App\Models\RoyaltyLine::MATCH_ISRC)
                                <form method="POST" action="{{ route('admin.royalties.assign', $import) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="isrc" value="{{ $group->isrc }}">
                                    <input type="hidden" name="artist" value="{{ $group->artist }}">
                                    <input type="hidden" name="title" value="{{ $group->title }}">
                                    <button type="submit" class="ml-2 text-xs text-red-600 dark:text-red-400 hover:underline">lösen</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
function royaltyPicker(config) {
    return {
        open: false,
        query: config.query || '',
        results: [],
        loading: false,
        timer: null,
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.search();
                this.$nextTick(() => this.$refs.search?.focus());
            }
        },
        search() {
            clearTimeout(this.timer);
            this.timer = setTimeout(async () => {
                this.loading = true;
                try {
                    const response = await fetch(config.url + '?q=' + encodeURIComponent(this.query), { headers: { 'Accept': 'application/json' } });
                    this.results = response.ok ? await response.json() : [];
                } finally {
                    this.loading = false;
                }
            }, 200);
        },
        choose(id) {
            this.$refs.target.value = id;
            this.$refs.form.submit();
        },
    };
}
</script>
@endsection
