@extends('admin.layouts.app')

@section('title', $statement->title)

@use('App\Models\RoyaltyStatementItem', 'Item')
@php
    $chf = fn ($value) => number_format((float) $value, 2, '.', "'");
    $int = fn ($value) => number_format((int) $value, 0, '.', "'");
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
    $labelClass = 'block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1';
    $final = $statement->is_final;
    $roles = [
        Item::ARTIST => ['Künstler:in', 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'],
        Item::LABEL => ['Label', 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300'],
        Item::OWN => ['eigenes Label', 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'],
        Item::NONE => ['bleibt bei TYL', 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'],
    ];
    $credited = $parties->whereIn('role', [Item::ARTIST, Item::LABEL]);
    $artists = round($parties->where('role', Item::ARTIST)->sum('credit'), 2);
    $recouped = round($result['items']->sum('recouped'), 2);
    $stays = $parties->whereIn('role', [Item::OWN, Item::NONE])->sum('share');
    $accountName = fn ($id) => ($a = $accountOptions->firstWhere('id', (int) $id)) ? "{$a->number} {$a->name}" : '— nicht gewählt —';
@endphp

@section('content')
@include('admin.royalties._tabs')

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            {{ $statement->title }}
            @if($final)
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">abgeschlossen</span>
            @else
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Entwurf</span>
            @endif
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ $statement->statement_date->format('d.m.Y') }} · Nutzung {{ $statement->period_from->format('d.m.Y') }} – {{ $statement->period_to->format('d.m.Y') }}
            · {{ $statement->accounting?->name ?? 'keine Buchhaltung gewählt' }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        @if($final)
            <form method="POST" action="{{ route('admin.royalties.statements.undo', $statement) }}"
                  onsubmit="return confirm('Abrechnung rückgängig machen? Buchungen und Gutschriften werden gelöscht, die Nutzung ist wieder offen.')">
                @csrf
                <button type="submit" class="px-4 py-2 bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-red-50 dark:hover:bg-red-900/20">Rückgängig machen</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.royalties.statements.destroy', $statement) }}" onsubmit="return confirm('Diesen Entwurf löschen?')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-red-50 dark:hover:bg-red-900/20">Entwurf löschen</button>
            </form>
            <form method="POST" action="{{ route('admin.royalties.statements.finalize', $statement) }}"
                  onsubmit="return confirm({{ \Illuminate\Support\Js::from('Abrechnung abschliessen? Die Anteile werden gutgeschrieben und in «' . ($statement->accounting?->name ?? '') . '» gebucht.') }})">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">Abschliessen und buchen</button>
            </form>
        @endif
    </div>
</div>

@if(!$final && $result['warnings'])
    <div class="mb-6 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
        <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-1">Vor dem Abschliessen prüfen</p>
        <ul class="list-disc list-inside text-sm text-amber-800 dark:text-amber-200 space-y-0.5">
            @foreach($result['warnings'] as $warning)
                <li>{{ $warning }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if(!$final && $result['unconfirmed'])
    <div class="mb-6 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 text-sm text-amber-800 dark:text-amber-200">
        {{ $int($result['unconfirmed']) }} Nutzungszeilen im Zeitraum stammen aus Importen, die noch nicht bestätigt sind – sie zählen erst nach der Bestätigung im <a href="{{ route('admin.royalties.index') }}" class="underline">Rebeat-Matcher</a>.
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="{{ $card }} p-5">
        <p class="text-xs text-gray-500 dark:text-gray-400">Zu verteilen (Rebeat-Zahlungen)</p>
        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 tabular-nums">CHF {{ $chf($result['pot']) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ $result['payments']->count() }} Zahlungen, EUR {{ $chf($result['payments']->sum('amount_foreign')) }} · <a href="{{ route('admin.royalties.payments.index') }}" class="underline">Zahlungen</a></p>
    </div>
    <div class="{{ $card }} p-5">
        <p class="text-xs text-gray-500 dark:text-gray-400">Nutzung im Zeitraum (Verteilschlüssel)</p>
        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 tabular-nums">EUR {{ $chf($result['usage']) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">1 EUR Nutzung = CHF {{ number_format($result['factor'], 4, '.', "'") }}</p>
    </div>
    <div class="{{ $card }} p-5">
        <p class="text-xs text-gray-500 dark:text-gray-400">Gutschriften</p>
        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 tabular-nums">CHF {{ $chf($credited->sum('credit')) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">an {{ $credited->count() }} Parteien · bleibt bei TYL CHF {{ $chf($stays) }}{{ $recouped > 0 ? ' · Vorschüsse verrechnet CHF ' . $chf($recouped) : '' }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    {{-- Einstellungen --}}
    <div class="{{ $card }} p-5">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Einstellungen</h3>
        @if($final)
            <dl class="text-sm grid grid-cols-2 gap-y-1">
                <dt class="text-gray-500 dark:text-gray-400">Bereits pauschal gebucht</dt><dd class="text-right tabular-nums">CHF {{ $chf($statement->pre_booked) }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">Abgeschlossen</dt><dd class="text-right">{{ $statement->finalized_at?->timezone('Europe/Zurich')->format('d.m.Y H:i') }}</dd>
            </dl>
        @else
            <form method="POST" action="{{ route('admin.royalties.statements.update', $statement) }}" class="space-y-3">
                @csrf @method('PUT')
                <div>
                    <label class="{{ $labelClass }}" for="title">Titel</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $statement->title) }}" required maxlength="255" class="{{ $input }}">
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="{{ $labelClass }}" for="statement_date">Datum</label>
                        <input type="date" name="statement_date" id="statement_date" value="{{ $statement->statement_date->toDateString() }}" required class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="period_from">Nutzung von</label>
                        <input type="date" name="period_from" id="period_from" value="{{ $statement->period_from->toDateString() }}" required class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="period_to">bis</label>
                        <input type="date" name="period_to" id="period_to" value="{{ $statement->period_to->toDateString() }}" required class="{{ $input }}">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="{{ $labelClass }}" for="accounting_id">Buchen in</label>
                        <select name="accounting_id" id="accounting_id" class="{{ $input }}">
                            <option value="">— wählen —</option>
                            @foreach($accountings as $accounting)
                                <option value="{{ $accounting->id }}" @selected($statement->accounting_id === $accounting->id)>{{ $accounting->name }} ({{ $accounting->period_start->format('Y') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="pre_booked">Bereits pauschal gebucht (CHF)</label>
                        <input type="number" name="pre_booked" id="pre_booked" value="{{ (float) $statement->pre_booked }}" step="0.01" min="0" class="{{ $input }}">
                    </div>
                </div>
                @if($statement->accounting)
                    @php
                        $accountSelect = function (string $name, $selected, ?string $type = null) use ($accountOptions, $input) {
                            $options = $accountOptions->when($type, fn ($c) => $c->where('type', $type));
                            return view('admin.royalties._account-select', compact('name', 'selected', 'options', 'input'))->render();
                        };
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div><label class="{{ $labelClass }}">Anteile Künstler:innen</label>{!! $accountSelect('accounts[expense]', $accounts['expense'] ?? null, 'expense') !!}</div>
                        <div><label class="{{ $labelClass }}">Schuld (Kreditoren)</label>{!! $accountSelect('accounts[payable]', $accounts['payable'] ?? null, 'liability') !!}</div>
                        <div><label class="{{ $labelClass }}">Verrechnete Vorschüsse</label>{!! $accountSelect('accounts[recouped]', $accounts['recouped'] ?? null, 'income') !!}</div>
                    </div>
                    @foreach($labels as $label)
                        <div>
                            <label class="{{ $labelClass }}">Anteil {{ $label->primary_name }}</label>
                            {!! $accountSelect("accounts[labels][{$label->id}]", \App\Services\Royalties\RoyaltyStatementBooker::labelAccount($statement, $label), 'expense') !!}
                        </div>
                    @endforeach
                @endif
                <button type="submit" class="px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">Speichern und neu rechnen</button>
            </form>
        @endif
    </div>

    {{-- Buchungen --}}
    <div class="{{ $card }} p-5">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">{{ $final ? 'Buchungen' : 'Diese Buchungen entstehen beim Abschliessen' }}</h3>
        @if($final)
            @forelse($bookings as $booking)
                <div class="text-sm py-1.5 border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <div class="flex justify-between gap-2">
                        <span class="text-gray-900 dark:text-gray-100">{{ $booking->debitAccount?->number }} / {{ $booking->creditAccount?->number }}</span>
                        <span class="tabular-nums text-gray-900 dark:text-gray-100">CHF {{ $chf($booking->amount) }}</span>
                    </div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $booking->description }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Keine.</p>
            @endforelse
            @if($statement->accounting)
                <a href="{{ route('admin.accountings.journal', $statement->accounting) }}" class="inline-block mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">Zum Buchungsjournal</a>
            @endif
        @else
            @php
                $net = $artists - (float) $statement->pre_booked;
                $preview = collect([
                    ['Soll ' . $accountName($accounts['expense'] ?? null) . ' / Haben ' . $accountName($accounts['payable'] ?? null), $net, 'Anteile Künstler:innen' . ((float) $statement->pre_booked ? ' (CHF ' . $chf($artists) . ' abzüglich ' . $chf($statement->pre_booked) . ' pauschal gebucht)' : '')],
                    ['Soll ' . $accountName($accounts['expense'] ?? null) . ' / Haben ' . $accountName($accounts['recouped'] ?? null), $recouped, 'verrechnete Vorschüsse'],
                ])->concat($parties->where('role', Item::LABEL)->map(fn ($p) => [
                    'Soll ' . $accountName(\App\Services\Royalties\RoyaltyStatementBooker::labelAccount($statement, $p->organization)) . ' / Haben ' . $accountName($accounts['payable'] ?? null), $p->credit, 'Anteil ' . $p->name,
                ]))->filter(fn ($b) => abs($b[1]) >= 0.005);
            @endphp
            @forelse($preview as [$accountsText, $amount, $text])
                <div class="text-sm py-1.5 border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <div class="flex justify-between gap-2">
                        <span class="text-gray-900 dark:text-gray-100">{{ $amount < 0 ? 'umgekehrt: ' : '' }}{{ $accountsText }}</span>
                        <span class="tabular-nums text-gray-900 dark:text-gray-100 whitespace-nowrap">CHF {{ $chf(abs($amount)) }}</span>
                    </div>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $text }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Nichts zu buchen.</p>
            @endforelse
            <p class="text-xs text-gray-400 mt-2">Datum {{ $statement->statement_date->format('d.m.Y') }}. Die Gutschriften erhöhen den Saldo der Parteien.</p>
        @endif
    </div>
</div>

{{-- Rebeat-Zahlungen --}}
<details class="{{ $card }} mb-6 overflow-hidden">
    <summary class="px-5 py-3 cursor-pointer text-sm font-medium text-gray-900 dark:text-gray-100">Verteilte Rebeat-Zahlungen ({{ $result['payments']->count() }})</summary>
    <table class="w-full text-sm">
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($result['payments'] as $payment)
                <tr>
                    <td class="px-5 py-1.5 text-gray-700 dark:text-gray-300">{{ $payment->paid_at->format('d.m.Y') }}</td>
                    <td class="px-3 py-1.5 text-right tabular-nums text-gray-500 dark:text-gray-400">{{ $payment->amount_foreign !== null ? 'EUR ' . $chf($payment->amount_foreign) : '' }}</td>
                    <td class="px-5 py-1.5 text-right tabular-nums text-gray-900 dark:text-gray-100">CHF {{ $chf($payment->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</details>

{{-- Parteien --}}
<div class="{{ $card }} overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Anteile pro Partei</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Anklicken für die Tracks und Produkte dahinter. «PDF» zeigt die Abrechnung, wie die Partei sie erhält{{ $final ? '' : ' (als Entwurf markiert)' }}.</p>
        </div>
        @if($credited->isNotEmpty())
            <a href="{{ route('admin.royalties.statements.pdfs', $statement) }}" class="px-3 py-1.5 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">Alle PDFs (ZIP)</a>
        @endif
    </div>
    @if($parties->isEmpty())
        <p class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">Nichts zu verteilen.</p>
    @else
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($parties as $party)
                @php [$roleLabel, $roleClass] = $roles[$party->role]; @endphp
                <details class="group">
                    <summary class="px-5 py-2.5 cursor-pointer flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $roleClass }}">{{ $roleLabel }}</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $party->name }}</span>
                        <span class="ml-auto tabular-nums text-gray-500 dark:text-gray-400">Anteil CHF {{ $chf($party->share) }}</span>
                        @if($party->recouped > 0.004)
                            <span class="tabular-nums text-gray-500 dark:text-gray-400">− Vorschuss {{ $chf($party->recouped) }}</span>
                        @endif
                        @if(in_array($party->role, [Item::ARTIST, Item::LABEL], true))
                            <span class="tabular-nums font-medium text-gray-900 dark:text-gray-100 w-36 text-right">Gutschrift CHF {{ $chf($party->credit) }}</span>
                            <a href="{{ route('admin.royalties.statements.pdf', [$statement, $party->organization ? 'org-' . $party->organization->id : 'contact-' . $party->contact?->id]) }}"
                               target="_blank" class="text-xs text-blue-600 dark:text-blue-400 hover:underline" onclick="event.stopPropagation()">PDF</a>
                        @else
                            <span class="w-36"></span>
                        @endif
                    </summary>
                    <div class="overflow-x-auto bg-gray-50 dark:bg-gray-900/30">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-gray-500 dark:text-gray-400 text-left">
                                    <th class="px-5 py-1.5 font-medium">Track / Produkt</th>
                                    <th class="px-3 py-1.5 font-medium">Vertrag</th>
                                    <th class="px-3 py-1.5 font-medium text-right">Nutzungen</th>
                                    <th class="px-3 py-1.5 font-medium text-right">EUR</th>
                                    <th class="px-3 py-1.5 font-medium text-right">CHF</th>
                                    <th class="px-3 py-1.5 font-medium text-right">Anteil</th>
                                    <th class="px-5 py-1.5 font-medium text-right">CHF</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($party->items as $item)
                                    <tr>
                                        <td class="px-5 py-1.5 text-gray-900 dark:text-gray-100">{{ $item->track?->display_title ?? ($item->release ? $item->release->title . ' (Album)' : 'ohne Track') }}</td>
                                        <td class="px-3 py-1.5 text-gray-500 dark:text-gray-400">
                                            @if($item->contract)
                                                <a href="{{ route('admin.contracts.show', $item->contract) }}" class="hover:underline">{{ $item->contract->contract_number ?: $item->contract->title }}</a>
                                            @else
                                                {{ $party->role === Item::LABEL ? 'ohne Vertrag: 100 %' : '—' }}
                                            @endif
                                        </td>
                                        <td class="px-3 py-1.5 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $int($item->quantity) }}</td>
                                        <td class="px-3 py-1.5 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $chf($item->usage_amount) }}</td>
                                        <td class="px-3 py-1.5 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $chf($item->base_amount) }}</td>
                                        <td class="px-3 py-1.5 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ rtrim(rtrim(number_format($item->percent, 2, '.', ''), '0'), '.') }} %</td>
                                        <td class="px-5 py-1.5 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ $chf($item->amount) }}</td>
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
