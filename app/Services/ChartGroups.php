<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Collection;

/**
 * A chart of accounts read as groups: each header account ("1000
 * Umlaufvermögen") owns the accounts after it, up to the next header. The
 * accounts table has no parent column - the order of the numbers is the
 * structure, as in the chart templates.
 */
class ChartGroups
{
    /**
     * @param  Collection<int, Account>  $accounts  any order, headers included
     * @return list<array{header: ?Account, accounts: Collection<int, Account>}>
     */
    public static function of(Collection $accounts): array
    {
        $groups = [];
        $current = ['header' => null, 'accounts' => collect()];

        foreach ($accounts->sortBy('number', SORT_NATURAL) as $account) {
            if ($account->is_header) {
                if ($current['header'] || $current['accounts']->isNotEmpty()) $groups[] = $current;
                $current = ['header' => $account, 'accounts' => collect()];
            } else {
                $current['accounts']->push($account);
            }
        }
        if ($current['header'] || $current['accounts']->isNotEmpty()) $groups[] = $current;

        return $groups;
    }

    /**
     * The sum under each header account, keyed by its id.
     *
     * @param  callable(Account): float  $value  e.g. fn ($a) => $a->balance
     * @return array<int, float>
     */
    public static function headerTotals(Collection $accounts, callable $value): array
    {
        $totals = [];
        foreach (self::of($accounts) as $group) {
            if ($group['header']) {
                $totals[$group['header']->id] = round($group['accounts']->sum($value), 2);
            }
        }

        return $totals;
    }
}
