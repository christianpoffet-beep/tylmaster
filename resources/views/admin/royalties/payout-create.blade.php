@extends('admin.layouts.app')

@section('title', 'Auszahlung vorbereiten')

@php
    $chf = fn ($value) => number_format((float) $value, 2, '.', "'");
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
    $labelClass = 'block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1';
    // Suggested: from the minimum on, with an IBAN that works
    $suggested = fn ($p) => $p->balance >= $minimum && $p->iban_ok;
    $initial = $parties->mapWithKeys(fn ($p) => [$p->key => [
        'pay' => old("pay.{$p->key}") !== null ? true : (old('_token') ? false : $suggested($p)),
        'amount' => (float) old("amount.{$p->key}", $p->balance),
    ]]);
@endphp

@section('content')
@include('admin.royalties._tabs')

<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Auszahlung vorbereiten</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Aus «{{ $accounting->name }}». Pro Empfänger entsteht eine Buchung Kreditoren / Bank und eine Zeile in der Zahlungsdatei (pain.001) für das E-Banking.</p>
</div>

@if($parties->isEmpty())
    <div class="{{ $card }} p-6 text-sm text-gray-500 dark:text-gray-400">Niemand hat einen Saldo zugute.</div>
@else
<form method="POST" action="{{ route('admin.royalties.payouts.store') }}" x-data="payoutForm(@js($initial))">
    @csrf
    <input type="hidden" name="accounting_id" value="{{ $accounting->id }}">

    <div class="{{ $card }} p-5 mb-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div>
            <label for="execution_date" class="{{ $labelClass }}">Ausführungsdatum</label>
            <input type="date" name="execution_date" id="execution_date" value="{{ old('execution_date', $date) }}" required class="{{ $input }}">
        </div>
        <div>
            <label for="bank_account_id" class="{{ $labelClass }}">Belasten (Bankkonto)</label>
            <select name="bank_account_id" id="bank_account_id" required class="{{ $input }}">
                @foreach($bankAccounts as $account)
                    <option value="{{ $account->id }}" @selected((string) old('bank_account_id', $defaultBank?->id) === (string) $account->id)>{{ $account->number }} {{ $account->name }}{{ $account->iban ? ' · ' . $account->iban : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="payable_account_id" class="{{ $labelClass }}">Schuld (Kreditoren)</label>
            <select name="payable_account_id" id="payable_account_id" required class="{{ $input }}">
                @foreach($payableAccounts as $account)
                    <option value="{{ $account->id }}" @selected((string) old('payable_account_id', $defaultPayable?->id) === (string) $account->id)>{{ $account->number }} {{ $account->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="message" class="{{ $labelClass }}">Mitteilung an Empfänger</label>
            <input type="text" name="message" id="message" value="{{ old('message') }}" maxlength="140" placeholder="leer: «Abrechnung …» der letzten Abrechnung" class="{{ $input }}">
        </div>
    </div>

    <div class="{{ $card }} overflow-hidden mb-4">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-xs text-gray-500 dark:text-gray-400 text-left">
                        <th class="px-5 py-2 w-8"></th>
                        <th class="px-3 py-2 font-medium">Empfänger</th>
                        <th class="px-3 py-2 font-medium">IBAN / Adresse</th>
                        <th class="px-3 py-2 font-medium text-right">Saldo</th>
                        <th class="px-5 py-2 font-medium text-right">Auszahlen CHF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($parties as $party)
                        <tr :class="rows[@js($party->key)].pay ? '' : 'opacity-60'">
                            <td class="px-5 py-2">
                                <input type="checkbox" name="pay[{{ $party->key }}]" value="1" x-model="rows[@js($party->key)].pay"
                                       class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-3 py-2 text-gray-900 dark:text-gray-100">
                                {{ $party->name }}
                                @if($party->balance < $minimum)
                                    <span class="block text-xs text-gray-400">unter CHF {{ $chf($minimum) }} – normalerweise vorgetragen</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs">
                                @if($party->iban_ok)
                                    <span class="font-mono text-gray-600 dark:text-gray-300">{{ \App\Support\Iban::format($party->iban) }}</span>
                                @else
                                    <span class="text-amber-600 dark:text-amber-400">{{ $party->iban ? 'IBAN ungültig' : 'keine IBAN' }} – kommt nicht in die Zahlungsdatei (bar oder von Hand zahlen)</span>
                                @endif
                                @if(!$party->city)
                                    <span class="block text-amber-600 dark:text-amber-400">Ort fehlt – die Bank verlangt die Adresse</span>
                                @else
                                    <span class="block text-gray-400">{{ trim(($party->street ? $party->street . ', ' : '') . $party->zip . ' ' . $party->city) }}{{ $party->country && $party->country !== 'CH' ? ' (' . $party->country . ')' : '' }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $chf($party->balance) }}</td>
                            <td class="px-5 py-2 text-right">
                                <input type="number" name="amount[{{ $party->key }}]" x-model.number="rows[@js($party->key)].amount" step="0.01" min="0" max="{{ $party->balance }}"
                                       class="w-28 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm text-right focus:border-blue-500 focus:ring-blue-500">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td colspan="4" class="px-5 py-2 text-right text-gray-500 dark:text-gray-400"><span x-text="count"></span> Empfänger, total</td>
                        <td class="px-5 py-2 text-right tabular-nums font-medium text-gray-900 dark:text-gray-100" x-text="'CHF ' + total"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-5 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700"
                onclick="return confirm('Auszahlung buchen? Die Saldi werden um die Beträge reduziert.')">Buchen und Zahlungsdatei erstellen</button>
        <a href="{{ route('admin.royalties.payouts.index') }}" class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Abbrechen</a>
    </div>
</form>

<script>
function payoutForm(rows) {
    return {
        rows,
        get chosen() { return Object.values(this.rows).filter(r => r.pay && r.amount > 0); },
        get count() { return this.chosen.length; },
        get total() {
            return this.chosen.reduce((s, r) => s + (parseFloat(r.amount) || 0), 0)
                .toLocaleString('de-CH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    };
}
</script>
@endif
@endsection
