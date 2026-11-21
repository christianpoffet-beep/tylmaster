<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Accounting;
use App\Models\Booking;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Saldovortrag: a new fiscal year opens with the closing balances of the
 * previous one. Asset and liability accounts carry their balance over, the
 * year's profit (or loss) goes onto an equity account, income and expense
 * accounts start at zero. The new year keeps the chart of the old one -
 * including the accounts added during the year - and finds its accounts by
 * number when it compares or refreshes them later.
 *
 * @phpstan-type Plan array{openings: array<int, float>, profit: float, assets: float, liabilities: float, difference: float}
 */
class CarryForward
{
    /**
     * Balance of every account (opening + bookings, in the account's natural
     * sign), keyed by account id - two queries for any number of accountings.
     *
     * @param  iterable<Accounting>  $accountings  with their accounts loaded
     * @return array<int, float>
     */
    public function balances(iterable $accountings): array
    {
        $accountings = collect($accountings);
        $ids = $accountings->pluck('id');
        $sums = fn (string $side) => Booking::whereIn('accounting_id', $ids)
            ->groupBy($side)->selectRaw("{$side} as account_id, SUM(amount) as total")
            ->pluck('total', 'account_id');
        $debits = $sums('debit_account_id');
        $credits = $sums('credit_account_id');

        $balances = [];
        foreach ($accountings as $accounting) {
            foreach ($accounting->accounts as $account) {
                $debit = (float) ($debits[$account->id] ?? 0);
                $credit = (float) ($credits[$account->id] ?? 0);
                $movement = in_array($account->type, ['asset', 'expense']) ? $debit - $credit : $credit - $debit;
                $balances[$account->id] = round((float) $account->opening_balance + $movement, 2);
            }
        }

        return $balances;
    }

    /**
     * What the next year opens with, keyed by the id of the previous year's
     * account (asset and liability accounts only).
     *
     * @param  array<int, float>|null  $balances
     * @return Plan
     */
    public function plan(Accounting $previous, ?Account $profitAccount, ?array $balances = null): array
    {
        $balances ??= $this->balances([$previous]);
        $accounts = $previous->accounts->where('is_header', false);

        $profit = 0.0;
        foreach ($accounts as $account) {
            if ($account->type === 'income') $profit += $balances[$account->id] ?? 0;
            if ($account->type === 'expense') $profit -= $balances[$account->id] ?? 0;
        }
        $profit = round($profit, 2);

        $openings = [];
        $totals = ['asset' => 0.0, 'liability' => 0.0];
        foreach ($accounts->whereIn('type', ['asset', 'liability']) as $account) {
            $opening = ($balances[$account->id] ?? 0) + ($profitAccount && $account->id === $profitAccount->id ? $profit : 0);
            $openings[$account->id] = round($opening, 2);
            $totals[$account->type] += $opening;
        }

        return [
            'openings' => $openings,
            'profit' => $profit,
            'assets' => round($totals['asset'], 2),
            'liabilities' => round($totals['liability'], 2),
            // Only when the previous year did not balance in the first place
            'difference' => round($totals['asset'] - $totals['liability'], 2),
        ];
    }

    /** The equity account a profit usually goes to: 2801, else one called "Eigenkapital", else a 28xx. */
    public function defaultProfitAccount(Collection $accounts): ?Account
    {
        $candidates = $accounts->where('is_header', false)->where('type', 'liability');

        return $candidates->firstWhere('number', '2801')
            ?? $candidates->first(fn (Account $a) => str_contains(mb_strtolower($a->name), 'eigenkapital'))
            ?? $candidates->first(fn (Account $a) => str_starts_with($a->number, '28'));
    }

    /**
     * Opens the next year: same owner and currency, the whole chart (IBANs
     * too), the closing balances as opening balances.
     *
     * @param  array{name: string, period_start: string, period_end: string, notes?: ?string}  $attributes
     */
    public function open(Accounting $previous, array $attributes, Account $profitAccount): Accounting
    {
        $previous->loadMissing('accounts');
        $plan = $this->plan($previous, $profitAccount);

        return DB::transaction(function () use ($previous, $attributes, $profitAccount, $plan) {
            $next = Accounting::create([
                'accountable_type' => $previous->accountable_type,
                'accountable_id' => $previous->accountable_id,
                'currency' => $previous->currency,
                'chart_template_id' => $previous->chart_template_id,
                'previous_accounting_id' => $previous->id,
                'name' => $attributes['name'],
                'period_start' => $attributes['period_start'],
                'period_end' => $attributes['period_end'],
                'notes' => $attributes['notes'] ?? null,
            ]);

            foreach ($previous->accounts as $account) {
                $copy = $this->copyAccount($next, $account, $plan['openings'][$account->id] ?? 0);
                if ($account->id === $profitAccount->id) {
                    $next->update(['profit_account_id' => $copy->id]);
                }
            }

            return $next;
        });
    }

    /**
     * Where the opening balances no longer match the previous year's end - a
     * booking added, changed or removed there after the new year was opened.
     *
     * @return Collection<int, array{number: string, name: string, expected: float, actual: ?float, account: ?Account, source: Account}>
     */
    public function differences(Accounting $next): Collection
    {
        $previous = $next->previousAccounting;
        if (!$previous) return collect();

        $previous->loadMissing('accounts');
        $next->loadMissing('accounts', 'profitAccount');

        $byNumber = $next->accounts->where('is_header', false)->keyBy('number');
        $profitNumber = $next->profitAccount?->number ?? $this->defaultProfitAccount($next->accounts)?->number;
        $plan = $this->plan($previous, $previous->accounts->where('is_header', false)->firstWhere('number', $profitNumber));

        return $previous->accounts->whereIn('id', array_keys($plan['openings']))
            ->map(function (Account $source) use ($plan, $byNumber) {
                $account = $byNumber->get($source->number);
                $expected = $plan['openings'][$source->id];
                $actual = $account ? (float) $account->opening_balance : null;

                return ['number' => $source->number, 'name' => $account?->name ?? $source->name,
                    'expected' => $expected, 'actual' => $actual, 'account' => $account, 'source' => $source];
            })
            // An account missing in the new year only matters when it had money on it
            ->filter(fn (array $d) => abs($d['expected'] - ($d['actual'] ?? 0)) >= 0.005)
            ->values();
    }

    /** Takes the previous year's closing balances over again; returns how many accounts changed. */
    public function refresh(Accounting $next): int
    {
        $differences = $this->differences($next);

        DB::transaction(function () use ($next, $differences) {
            foreach ($differences as $difference) {
                if ($difference['account']) {
                    $difference['account']->update(['opening_balance' => $difference['expected']]);
                } else {
                    $this->copyAccount($next, $difference['source'], $difference['expected']);
                }
            }
        });

        return $differences->count();
    }

    private function copyAccount(Accounting $next, Account $account, float $opening): Account
    {
        return $next->accounts()->create([
            'number' => $account->number,
            'name' => $account->name,
            'iban' => $account->iban,
            'type' => $account->type,
            'is_header' => $account->is_header,
            'opening_balance' => $opening,
            'sort_order' => $account->sort_order,
        ]);
    }
}
