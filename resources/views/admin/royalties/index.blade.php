@extends('admin.layouts.app')

@section('title', 'Rebeat-Matcher')

@php
    $eur = fn ($value) => number_format((float) $value, 2, '.', "'");
    $int = fn ($value) => number_format((int) $value, 0, '.', "'");
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Rebeat-Matcher</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Nutzungsdaten von Rebeat einlesen und den Tracks zuordnen – die Grundlage für die Abrechnungen an Künstler:innen und Labels.</p>
    </div>
    @if($imports->isNotEmpty())
        <div class="flex items-center gap-2">
        <a href="{{ route('admin.royalties.contracts') }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Verträge für die Abrechnung</a>
        <form method="POST" action="{{ route('admin.royalties.rematch') }}">
            @csrf
            <button type="submit" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                    title="Offene Zeilen nochmals über die ISRC suchen – z.B. nachdem bei Tracks ISRCs ergänzt wurden">Neu zuordnen</button>
        </form>
        </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="{{ $card }} p-5">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">Rebeat-Export einlesen</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
            Die CSV-Datei aus Rebeat (Accounting → Export, «AccountingDataExport-…csv»), direkt hochladen, ohne sie vorher in Excel zu speichern.
            Zeiträume dürfen sich überschneiden – Zeilen, die schon da sind, werden übersprungen.
        </p>
        {{-- Sent in compressed pieces by rebeatUpload() below; the plain form is the fallback without JavaScript --}}
        <form method="POST" action="{{ route('admin.royalties.store') }}" enctype="multipart/form-data" class="space-y-3"
              x-data="rebeatUpload(@js(['start' => route('admin.royalties.start', [], false), 'token' => route('admin.royalties.token', [], false)]))" @submit.prevent="upload($refs.file.files[0])">
            @csrf
            <div>
                <input type="file" name="file" id="royalty-file" x-ref="file" accept=".csv,text/csv,text/plain" required :disabled="busy" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div x-show="busy" x-cloak>
                <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                    <div class="h-2 bg-blue-600 transition-all" :style="`width: ${progress}%`"></div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="status"></p>
            </div>
            <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
            <button type="submit" :disabled="busy" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-60">
                <span x-show="!busy">Einlesen</span>
                <span x-show="busy" x-cloak>Wird eingelesen …</span>
            </button>
        </form>
    </div>

    <div class="{{ $card }} p-5">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Nutzungen pro Jahr</h3>
        @if($years->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">Noch keine Daten.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 dark:text-gray-400 text-left">
                        <th class="pb-2 font-medium">Jahr</th>
                        <th class="pb-2 font-medium text-right">Nutzungen</th>
                        <th class="pb-2 font-medium text-right">Gutschrift EUR</th>
                        <th class="pb-2 font-medium text-right">ohne Track</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($years as $year => $row)
                        <tr>
                            <td class="py-1.5 text-gray-900 dark:text-gray-100">{{ $year }}</td>
                            <td class="py-1.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $int($row['quantity']) }}</td>
                            <td class="py-1.5 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ $eur($row['amount']) }}</td>
                            <td class="py-1.5 text-right tabular-nums {{ $row['open_amount'] > 0.005 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">{{ $eur($row['open_amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="text-xs text-gray-400 mt-3">Nach Nutzungsmonat. Gutschrift = was Rebeat nach seiner Gebühr gutschreibt, in EUR. Nutzungen bis {{ \Carbon\Carbon::parse(config('royalties.settled_until'))->format('d.m.Y') }} sind schon von Hand abgerechnet.</p>
        @endif
    </div>
</div>

<div class="{{ $card }} overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Importe</h3>
    </div>
    @if($imports->isEmpty())
        <p class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">Noch nichts eingelesen.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-xs text-gray-500 dark:text-gray-400 text-left">
                        <th class="px-5 py-2 font-medium">Eingelesen</th>
                        <th class="px-3 py-2 font-medium">Datei</th>
                        <th class="px-3 py-2 font-medium">Nutzungsmonate</th>
                        <th class="px-3 py-2 font-medium text-right">Zeilen</th>
                        <th class="px-3 py-2 font-medium text-right">EUR</th>
                        <th class="px-3 py-2 font-medium text-right">ohne Track</th>
                        <th class="px-5 py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($imports as $import)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-5 py-2.5 text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $import->created_at->format('d.m.Y H:i') }}</td>
                            <td class="px-3 py-2.5">
                                <a href="{{ route('admin.royalties.show', $import) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $import->file_name }}</a>
                            </td>
                            <td class="px-3 py-2.5 text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $import->period_from?->format('m.Y') }} – {{ $import->period_to?->format('m.Y') }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $int($import->lines_count) }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ $eur($import->lines_sum_amount) }}</td>
                            <td class="px-3 py-2.5 text-right tabular-nums {{ $import->open_count ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">{{ $int($import->open_count) }}</td>
                            <td class="px-5 py-2.5 whitespace-nowrap">
                                @if(!$import->is_complete)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">unvollständig</span>
                                @elseif($import->is_confirmed)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">bestätigt</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">zu prüfen</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
/*
 * Sends the export in pieces of about 1 MB, cut at line ends and gzipped
 * where the browser can: no request comes near PHP's upload limits, and the
 * server reads each piece while the next one is on its way.
 */
function rebeatUpload(urls) {
    const PIECE = 1024 * 1024;

    return {
        busy: false,
        progress: 0,
        status: '',
        error: '',
        token: document.querySelector('meta[name=csrf-token]').content,

        async upload(file) {
            if (!file || this.busy) return;
            this.busy = true;
            this.error = '';
            this.progress = 0;
            try {
                this.status = 'Datei wird gelesen …';
                const bytes = new Uint8Array(await file.arrayBuffer());
                const pieces = this.cut(bytes);

                // The page may be older than the session's token (open for long, or from before a deploy)
                await this.refreshToken();

                // Plain form posts (multipart, token in the body), like every other upload of the admin
                const started = await this.send('Start', urls.start, { file_name: file.name });
                for (let i = 0; i < pieces.length; i++) {
                    this.status = `Teil ${i + 1} von ${pieces.length} wird eingelesen …`;
                    const [body, encoding] = await this.compress(pieces[i]);
                    await this.send(`Teil ${i + 1}`, started.piece, {
                        index: String(i), encoding, piece: new File([body], `teil-${i + 1}.${encoding === 'gzip' ? 'gz' : 'csv'}`),
                    });
                    this.progress = Math.round(90 * (i + 1) / pieces.length);
                }

                this.status = 'Tracks werden zugeordnet …';
                const finished = await this.send('Abschluss', started.finish, {});
                this.progress = 100;
                window.location = finished.redirect;
            } catch (e) {
                this.error = e.message;
                this.busy = false;
            }
        },

        cut(bytes) {
            const pieces = [];
            let start = 0;
            while (start < bytes.length) {
                let end = Math.min(start + PIECE, bytes.length);
                if (end < bytes.length) {
                    const newline = bytes.indexOf(10, end);
                    end = newline === -1 ? bytes.length : newline + 1;
                }
                pieces.push(bytes.subarray(start, end));
                start = end;
            }
            return pieces;
        },

        async compress(piece) {
            if (!('CompressionStream' in window)) return [piece, 'identity'];
            const stream = new Blob([piece]).stream().pipeThrough(new CompressionStream('gzip'));
            return [new Uint8Array(await new Response(stream).arrayBuffer()), 'gzip'];
        },

        async refreshToken() {
            let data = null;
            try {
                const response = await fetch(urls.token, { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                data = await response.json();
            } catch (e) {
                // A login page instead of JSON: the session is gone
            }
            if (!data || !data.token) throw new Error('Du bist nicht mehr angemeldet. Bitte die Seite neu laden, anmelden und nochmals versuchen.');
            this.token = data.token;
        },

        /** A form post with the current token; a 419 gets a fresh token and one more try. */
        async send(step, url, fields, retried = false) {
            const form = new FormData();
            form.append('_token', this.token);
            Object.entries(fields).forEach(([name, value]) => form.append(name, value));

            let response;
            try {
                response = await fetch(url, {
                    method: 'POST', body: form, credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.token, 'X-Requested-With': 'XMLHttpRequest' },
                });
            } catch (e) {
                throw new Error(`${step}: keine Verbindung zum Server. Bitte nochmals versuchen.`);
            }
            if (response.status === 419 && !retried) {
                await this.refreshToken();
                return this.send(step, url, fields, true);
            }
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const detail = data.diagnose ? ` [${Object.entries(data.diagnose).map(([k, v]) => `${k}: ${v}`).join(', ')}]` : '';
                throw new Error(`${step}: ${data.message || 'Der Server meldet einen Fehler'} (${response.status})${detail}`);
            }
            return data;
        },
    };
}
</script>
@endsection
