@extends('admin.layouts.app')

@section('title', 'Bankabgleich – ' . $accounting->name)

@php
    $chf = fn ($value) => number_format((float) $value, 2, '.', "'");
    $accountsById = $accounts->keyBy('id');
    // Open expenses that can be picked for a payment - and what booking one means
    // (last year's ones too: they are booked - and move - into this year)
    $expenseTexts = $openExpenses->mapWithKeys(fn ($e) => ['expense:' . $e->id => [
        'option' => $e->expense_date->format('d.m.Y') . ' · ' . \Illuminate\Support\Str::limit($e->description, 40) . ' · ' . $e->currency . ' ' . number_format((float) $e->amount, 2, '.', "'")
            . ((int) $e->accounting_id !== (int) $accounting->id ? ' · aus ' . $e->accounting?->name : ''),
        'text' => 'Wird als Ausgabe «' . $e->description . '» auf ' . $e->account?->number . ' ' . $e->account?->name . ' gebucht, die Ausgabe gilt danach als bezahlt'
            . ((int) $e->accounting_id !== (int) $accounting->id ? ' und wechselt aus «' . $e->accounting?->name . '» in diese Buchhaltung.' : '.'),
    ]])->all();
    $suggestionCount = collect($suggestions)->filter(fn ($s) => \App\Services\Banking\BankTransactionMatcher::bookingInput($s))->count();
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
    $input = 'rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Bankabgleich</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $accounting->name }} · {{ $accounting->accountable_name }} · {{ $accounting->period_start->format('d.m.Y') }} – {{ $accounting->period_end->format('d.m.Y') }}</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.accountings.journal', $accounting) }}" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Buchungsjournal</a>
        <a href="{{ route('admin.accountings.show', $accounting) }}" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Zurück</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    {{-- Einlesen --}}
    <div class="{{ $card }} p-5">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">Bankauszug einlesen</h3>
        @if($accounting->is_closed)
            <p class="text-sm text-gray-500 dark:text-gray-400">Die Buchhaltung ist abgeschlossen.</p>
        @elseif($bankAccounts->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">Im Kontenplan gibt es noch kein Aktivkonto für die Bank.</p>
        @else
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                CSV-Export aus dem E-Banking (bisher Raiffeisen). Zeiträume dürfen sich überschneiden &ndash; was schon eingelesen ist, wird übersprungen. Es wird noch nichts gebucht.
            </p>
            <form method="POST" action="{{ route('admin.accountings.bank.import', $accounting) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="bank-file" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Datei *</label>
                        <input type="file" name="file" id="bank-file" accept=".csv,text/csv,text/plain" required class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                        @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="bank-account" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Bankkonto *</label>
                        <select name="account_id" id="bank-account" class="w-full {{ $input }}">
                            @foreach($bankAccounts as $bankAccount)
                                <option value="{{ $bankAccount->id }}" @selected($defaultBankAccount && $defaultBankAccount->id === $bankAccount->id)>
                                    {{ $bankAccount->number }} {{ $bankAccount->name }}{{ $bankAccount->iban ? ' · ' . $bankAccount->iban : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Einlesen</button>
            </form>
        @endif
    </div>

    {{-- Saldo-Abgleich --}}
    <div class="{{ $card }} p-5">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Saldo-Abgleich</h3>
        @forelse($reconciliation as $rec)
            <div class="mb-4 last:mb-0">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">{{ $rec['account']->number }} {{ $rec['account']->name }}</p>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 dark:text-gray-500">
                            <th class="text-left font-normal py-1">Stichtag</th>
                            <th class="text-right font-normal py-1">Bank</th>
                            <th class="text-right font-normal py-1">Buchhaltung</th>
                            <th class="text-right font-normal py-1 w-28"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['opening' => 'Vor erster Bewegung', 'closing' => 'Nach letzter Bewegung'] as $key => $caption)
                            @php $point = $rec[$key]; $diff = round(($point['books'] ?? 0) - ($point['bank'] ?? 0), 2); @endphp
                            <tr class="border-t border-gray-100 dark:border-gray-700">
                                <td class="py-1.5 text-gray-600 dark:text-gray-300">{{ $caption }} <span class="text-gray-400 dark:text-gray-500">{{ $point['date']->format('d.m.Y') }}</span></td>
                                <td class="py-1.5 text-right font-mono text-gray-900 dark:text-gray-100">{{ $point['bank'] === null ? '—' : $chf($point['bank']) }}</td>
                                <td class="py-1.5 text-right font-mono text-gray-900 dark:text-gray-100">{{ $chf($point['books']) }}</td>
                                <td class="py-1.5 text-right text-xs">
                                    @if($point['bank'] === null)
                                    @elseif($diff == 0)
                                        <span class="text-green-600 dark:text-green-400 font-medium">✓ stimmt</span>
                                    @else
                                        <span class="text-red-600 dark:text-red-400 font-medium">Differenz {{ $diff > 0 ? '+' : '' }}{{ $chf($diff) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($rec['open'])
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Noch {{ $rec['open'] }} offene Bewegung(en) &ndash; der Schlusssaldo stimmt erst, wenn alle gebucht sind.</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Noch kein Bankauszug eingelesen. Danach siehst du hier, ob der Saldo der Bank mit der Buchhaltung übereinstimmt.</p>
        @endforelse
    </div>
</div>

{{-- Tabs --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
        @foreach([\App\Models\BankTransaction::OPEN => 'Offen', \App\Models\BankTransaction::BOOKED => 'Gebucht', \App\Models\BankTransaction::IGNORED => 'Ignoriert'] as $value => $label)
            <a href="{{ route('admin.accountings.bank', [$accounting, 'status' => $value]) }}"
               class="px-3 py-1.5 text-sm rounded-md {{ $status === $value ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm font-medium' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}">
                {{ $label }} <span class="text-xs text-gray-400 dark:text-gray-500">{{ $counts[$value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>
    @if($status === 'open' && $suggestionCount && !$accounting->is_closed)
        <form method="POST" action="{{ route('admin.accountings.bank.book', $accounting) }}" onsubmit="return confirm('Alle {{ $suggestionCount }} Vorschläge so buchen, wie sie vorgeschlagen sind?')">
            @csrf
            <input type="hidden" name="all" value="1">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">Alle Vorschläge übernehmen ({{ $suggestionCount }})</button>
        </form>
    @endif
</div>

@if($status === 'open')
    {{-- Inbox --}}
    <div class="space-y-3">
        @forelse($transactions as $tx)
            @php
                $s = $suggestions[$tx->id] ?? null;
                $counter = ($s['account_id'] ?? null) ? $accountsById->get($s['account_id']) : null;
                $counterName = $counter ? "{$counter->number} {$counter->name}" : '';
                $row = [
                    'amount' => $tx->abs_amount,
                    'bankAccountId' => $tx->account_id,
                    'incoming' => $tx->is_incoming,
                    'match' => \App\Services\Banking\BankTransactionMatcher::bookingInput($s)['match'] ?? '',
                    'matchText' => match ($s['type'] ?? null) {
                        'booking' => 'Die Buchung ist schon im Journal – sie wird nur mit dieser Bewegung verknüpft (keine Doppelbuchung).',
                        'invoice' => 'Zahlung wird gebucht ' . ($tx->is_incoming ? 'auf ' : 'gegen ') . $counterName . ', die Rechnung gilt danach als bezahlt.',
                        'expense' => $expenseTexts['expense:' . $s['expense_id']]['text'] ?? '',
                        default => '',
                    },
                    'accountId' => $s['account_id'] ?? null,
                    'description' => $s['description'] ?? $tx->text,
                    'fromRule' => ($s['type'] ?? null) === 'rule',
                    'remember' => !$s,
                    'pattern' => $tx->counterparty,
                ];
            @endphp
            <form method="POST" action="{{ route('admin.accountings.bank.book', $accounting) }}"
                  x-data="bankRow(@js($row))" class="{{ $card }} p-4">
                @csrf
                <input type="hidden" name="transaction" value="{{ $tx->id }}">

                <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400 w-24 shrink-0">{{ $tx->booking_date->format('d.m.Y') }}</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $tx->text }}</p>
                        @if($tx->details)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 break-words">{{ $tx->details }}</p>
                        @endif
                    </div>
                    <div class="text-sm font-mono font-semibold whitespace-nowrap {{ $tx->is_incoming ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $tx->is_incoming ? '+' : '−' }}{{ $chf($tx->abs_amount) }}
                    </div>
                </div>

                @unless($accounting->is_closed)
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 sm:pl-28 space-y-3">
                    @if($s)
                        <p class="text-xs">
                            <span class="inline-flex items-center px-2 py-0.5 rounded font-medium
                                {{ match ($s['type']) {
                                    'rule' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
                                    'expense' => 'bg-teal-100 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300',
                                    default => 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300',
                                } }}">
                                Vorschlag
                            </span>
                            <span class="text-gray-600 dark:text-gray-300 ml-1">{{ $s['label'] }}</span>
                        </p>
                    @endif

                    <input type="hidden" name="match" :value="match">

                    {{-- Bestehende Buchung / Rechnung / Ausgabe --}}
                    <template x-if="match">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            <span x-text="matchText()"></span>
                            <button type="button" @click="match = ''" class="ml-1 text-xs text-blue-600 dark:text-blue-400 hover:underline">Stattdessen Konto wählen</button>
                        </div>
                    </template>

                    {{-- Gegenkonto --}}
                    <template x-if="!match && !split">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $tx->is_incoming ? 'Gegenkonto (Haben)' : 'Gegenkonto (Soll)' }}</label>
                                <div x-data="{ search: '', open: false }" @click.outside="open = false" class="relative">
                                    <input type="text" x-model="search" @focus="open = true" @input="open = true"
                                        :placeholder="accountId ? label(accountId) : 'Konto suchen...'"
                                        class="w-full {{ $input }}" :class="accountId ? 'placeholder-gray-700 dark:placeholder-gray-200' : ''">
                                    <input type="hidden" name="account_id" :value="accountId">
                                    <div x-show="open" x-transition class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-56 overflow-y-auto">
                                        <template x-for="acc in filtered(search)" :key="acc.id">
                                            <button type="button" @click="choose(acc.id); search = ''; open = false"
                                                class="w-full text-left px-3 py-1.5 text-sm text-gray-900 dark:text-gray-100 hover:bg-blue-50 dark:hover:bg-gray-700/50 flex justify-between"
                                                :class="acc.id == accountId ? 'bg-blue-50 dark:bg-gray-700 font-medium' : ''">
                                                <span x-text="acc.number + ' ' + acc.name"></span>
                                                <span class="text-xs text-gray-400" x-text="typeLabel(acc.type)"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Buchungstext</label>
                                <input type="text" name="description" x-model="description" maxlength="250" class="w-full {{ $input }}">
                            </div>
                            @if(!$tx->is_incoming && count($expenseTexts))
                                <div class="md:col-span-2 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span>oder offene Ausgabe zuordnen:</span>
                                    <select x-model="match" class="{{ $input }} py-1 max-w-full">
                                        <option value="">—</option>
                                        @foreach($expenseTexts as $key => $expenseText)
                                            <option value="{{ $key }}">{{ $expenseText['option'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="md:col-span-2 flex flex-wrap items-center gap-2">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <input type="checkbox" name="remember" value="1" x-model="remember" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 focus:ring-blue-500">
                                    <span x-text="fromRule ? 'Regel anpassen' : 'Als Regel merken'"></span>
                                </label>
                                <template x-if="remember">
                                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span>wenn Text enthält</span>
                                        <input type="text" name="pattern" x-model="pattern" maxlength="250" class="w-56 {{ $input }} py-1">
                                    </div>
                                </template>
                            </div>
                            @unless($tx->is_incoming)
                                <p x-show="isExpenseAccount(accountId)" class="md:col-span-2 text-xs text-gray-400 dark:text-gray-500">Auf einem Aufwandkonto entsteht dabei automatisch eine Ausgabe &ndash; dort kannst du den Beleg anhängen.</p>
                            @endunless
                        </div>
                    </template>

                    {{-- Aufteilen --}}
                    <template x-if="!match && split">
                        <div class="space-y-2">
                            <input type="hidden" name="split" value="1">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Buchungstext</label>
                                <input type="text" name="description" x-model="description" maxlength="250" class="w-full md:w-1/2 {{ $input }}">
                            </div>
                            <template x-for="(line, i) in lines" :key="line.key">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div x-data="{ search: '', open: false }" @click.outside="open = false" class="relative flex-1 min-w-[14rem]">
                                        <input type="text" x-model="search" @focus="open = true" @input="open = true"
                                            :placeholder="line.account_id ? label(line.account_id) : 'Konto suchen...'"
                                            class="w-full {{ $input }}" :class="line.account_id ? 'placeholder-gray-700 dark:placeholder-gray-200' : ''">
                                        <input type="hidden" :name="'splits[' + i + '][account_id]'" :value="line.account_id">
                                        <div x-show="open" x-transition class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-56 overflow-y-auto">
                                            <template x-for="acc in filtered(search)" :key="acc.id">
                                                <button type="button" @click="line.account_id = acc.id; search = ''; open = false"
                                                    class="w-full text-left px-3 py-1.5 text-sm text-gray-900 dark:text-gray-100 hover:bg-blue-50 dark:hover:bg-gray-700/50 flex justify-between">
                                                    <span x-text="acc.number + ' ' + acc.name"></span>
                                                    <span class="text-xs text-gray-400" x-text="typeLabel(acc.type)"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <input type="number" step="0.01" min="0.01" :name="'splits[' + i + '][amount]'" x-model="line.amount" class="w-32 text-right {{ $input }}">
                                    <button type="button" @click="lines.splice(i, 1)" x-show="lines.length > 2" class="text-xs text-red-500 dark:text-red-400 hover:text-red-700">Entfernen</button>
                                </div>
                            </template>
                            <div class="flex items-center gap-4 text-sm">
                                <button type="button" @click="addLine()" class="text-blue-600 dark:text-blue-400 hover:underline">+ Zeile</button>
                                <span :class="Math.abs(remainder) < 0.005 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'"
                                      x-text="Math.abs(remainder) < 0.005 ? '✓ geht auf' : 'Rest: ' + remainder.toFixed(2)"></span>
                            </div>
                        </div>
                    </template>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Buchen</button>
                        <button type="button" x-show="!match" @click="toggleSplit()" class="px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700" x-text="split ? 'Nicht aufteilen' : 'Aufteilen'"></button>
                        <button type="submit" formaction="{{ route('admin.accountings.bank.ignore', $accounting) }}" formnovalidate
                                onclick="return confirm('Diese Bewegung ignorieren? Sie wird nicht gebucht.')"
                                class="px-3 py-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Ignorieren</button>
                    </div>
                </div>
                @endunless
            </form>
        @empty
            <div class="{{ $card }} p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ ($counts['booked'] ?? 0) || ($counts['ignored'] ?? 0) ? 'Alles erledigt – keine offenen Bewegungen.' : 'Noch keine Bewegungen. Lies oben einen Bankauszug ein.' }}
            </div>
        @endforelse
    </div>
@else
    {{-- Gebucht / Ignoriert --}}
    <div class="{{ $card }} overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-28">Datum</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bewegung</th>
                        @if($status === 'booked')
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Gebucht als</th>
                        @endif
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-32">Betrag</th>
                        <th class="px-4 py-2 w-32"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($transactions as $tx)
                        <tr class="align-top">
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $tx->booking_date->format('d.m.Y') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                {{ $tx->text }}
                                @if($tx->details)<p class="text-xs text-gray-500 dark:text-gray-400">{{ $tx->details }}</p>@endif
                            </td>
                            @if($status === 'booked')
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-300">
                                    @foreach($tx->bookings as $booking)
                                        <div>
                                            {{ $booking->debitAccount->number }} an {{ $booking->creditAccount->number }} · {{ $chf($booking->amount) }}
                                            <span class="text-gray-400 dark:text-gray-500">{{ $booking->description }}</span>
                                            @unless($booking->from_bank_import)<span class="text-indigo-600 dark:text-indigo-400">(verknüpft)</span>@endunless
                                            @if($booking->expense)
                                                <a href="{{ route('admin.expenses.edit', $booking->expense) }}" class="text-teal-600 dark:text-teal-400 hover:underline">Ausgabe / Beleg</a>
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                            @endif
                            <td class="px-4 py-2 text-sm text-right font-mono whitespace-nowrap {{ $tx->is_incoming ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $tx->is_incoming ? '+' : '−' }}{{ $chf($tx->abs_amount) }}
                            </td>
                            <td class="px-4 py-2 text-right">
                                @unless($accounting->is_closed)
                                    <form method="POST" action="{{ route('admin.bank-transactions.reopen', $tx) }}"
                                          onsubmit="return confirm('{{ $status === 'booked' ? 'Zurück in die Inbox? Buchungen aus dem Import werden gelöscht, verknüpfte bleiben stehen.' : 'Zurück in die Inbox?' }}')">
                                        @csrf @method('PATCH')
                                        <button class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Wieder öffnen</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Keine Bewegungen.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $transactions->links() }}</div>
@endif

{{-- Regeln --}}
<details class="{{ $card }} mt-6 group" @if($rules->count()) open @endif>
    <summary class="px-4 py-3 cursor-pointer text-sm font-medium text-gray-900 dark:text-gray-100 select-none">
        Regeln <span class="text-xs text-gray-400 dark:text-gray-500">{{ $rules->count() }}</span>
        <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-2">gelten für alle Buchhaltungen von {{ $accounting->accountable_name }}</span>
    </summary>
    <div class="border-t border-gray-200 dark:border-gray-700">
        @if($rules->isEmpty())
            <p class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">Noch keine Regeln. Beim Buchen «Als Regel merken» ankreuzen &ndash; beim nächsten Import ist das Konto dann vorausgewählt.</p>
        @else
            @php $accountsByNumber = $accounts->keyBy('number'); @endphp
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Text enthält</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-28">Richtung</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Konto</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Buchungstext</th>
                        <th class="px-4 py-2 w-20"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($rules as $rule)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $rule->pattern }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ \App\Models\BookingRule::DIRECTIONS[$rule->direction] ?? $rule->direction }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">
                                {{ $rule->account_number }} {{ $accountsByNumber->get($rule->account_number)?->name }}
                                @unless($accountsByNumber->has($rule->account_number))<span class="text-xs text-amber-600 dark:text-amber-400">(fehlt in diesem Kontenplan)</span>@endunless
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $rule->description ?: '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                <form method="POST" action="{{ route('admin.booking-rules.destroy', $rule) }}" onsubmit="return confirm('Regel löschen?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Löschen</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</details>

{{-- Importe --}}
@if($imports->count())
<details class="{{ $card }} mt-4">
    <summary class="px-4 py-3 cursor-pointer text-sm font-medium text-gray-900 dark:text-gray-100 select-none">
        Eingelesene Dateien <span class="text-xs text-gray-400 dark:text-gray-500">{{ $imports->count() }}</span>
    </summary>
    <div class="border-t border-gray-200 dark:border-gray-700 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Eingelesen</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Datei</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Konto</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Zeitraum</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bewegungen</th>
                    <th class="px-4 py-2 w-20"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($imports as $import)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $import->created_at->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if($import->file_path)
                                <a href="{{ route('admin.bank-imports.file', $import) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $import->file_name }}</a>
                            @else
                                <span class="text-gray-900 dark:text-gray-100">{{ $import->file_name }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $import->account?->number }} {{ $import->account?->name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $import->statement_from?->format('d.m.Y') }} – {{ $import->statement_to?->format('d.m.Y') }}</td>
                        <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-300">
                            {{ $import->rows_new }} neu
                            @if($import->rows_duplicate) · {{ $import->rows_duplicate }} schon da @endif
                            @if($import->rows_outside) · {{ $import->rows_outside }} ausserhalb @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            @if(!$accounting->is_closed && !$import->booked_count)
                                <form method="POST" action="{{ route('admin.bank-imports.destroy', $import) }}" onsubmit="return confirm('Import entfernen? Seine offenen und ignorierten Bewegungen verschwinden aus der Inbox.')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Entfernen</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</details>
@endif

@php
    $accountsJson = $accounts->map(fn ($a) => ['id' => $a->id, 'number' => $a->number, 'name' => $a->name, 'type' => $a->type])->values();
@endphp
<script>
window.bankAccounts = @json($accountsJson);
window.bankExpenses = @json($expenseTexts);

function bankRow(cfg) {
    return {
        amount: Number(cfg.amount),
        bankAccountId: cfg.bankAccountId,
        match: cfg.match || '',
        suggestedMatch: cfg.match || '',
        suggestedText: cfg.matchText || '',

        matchText() {
            if (this.match === this.suggestedMatch) return this.suggestedText;
            return (window.bankExpenses[this.match] || {}).text || '';
        },
        isExpenseAccount(id) {
            const acc = window.bankAccounts.find(a => a.id == id);
            return !!acc && acc.type === 'expense';
        },
        accountId: cfg.accountId || '',
        description: cfg.description || '',
        fromRule: !!cfg.fromRule,
        remember: !!cfg.remember,
        pattern: cfg.pattern || '',
        split: false,
        lines: [],
        nextKey: 0,

        filtered(search) {
            const q = (search || '').toLowerCase();
            return window.bankAccounts.filter(a => a.id != this.bankAccountId
                && (!q || (a.number + ' ' + a.name).toLowerCase().includes(q)));
        },
        label(id) {
            const acc = window.bankAccounts.find(a => a.id == id);
            return acc ? acc.number + ' ' + acc.name : '';
        },
        typeLabel(type) {
            return { asset: 'Aktiv', liability: 'Passiv', income: 'Ertrag', expense: 'Aufwand' }[type] || type;
        },
        choose(id) {
            // Another account than the rule says: offer to update the rule
            if (this.fromRule && id != this.accountId) this.remember = true;
            this.accountId = id;
        },
        addLine(amount = '') {
            this.lines.push({ key: this.nextKey++, account_id: '', amount: amount });
        },
        toggleSplit() {
            this.split = !this.split;
            if (this.split && !this.lines.length) {
                this.lines.push({ key: this.nextKey++, account_id: this.accountId, amount: this.amount.toFixed(2) });
                this.addLine();
            }
        },
        get remainder() {
            const sum = this.lines.reduce((total, line) => total + (parseFloat(line.amount) || 0), 0);
            return Math.round((this.amount - sum) * 100) / 100;
        },
    };
}
</script>
@endsection
