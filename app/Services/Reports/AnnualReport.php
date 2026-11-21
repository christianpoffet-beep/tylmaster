<?php

namespace App\Services\Reports;

use App\Models\Account;
use App\Models\Accounting;
use App\Services\CarryForward;
use Illuminate\Support\Collection;

/**
 * The figures of a Jahresrechnung: balance sheet and income statement, the
 * accounts grouped under their header accounts with subtotals, next to a
 * comparison column.
 *
 * - Balance sheet: end of the year against its opening (= the end of the
 *   year before), so it compares even without a previous accounting.
 * - Income statement: this year against the previous accounting (when the
 *   year was opened from one), matched by account number.
 *
 * Lines that are zero in both columns are left out; a group without lines too.
 *
 * @phpstan-type Line array{number: string, name: string, amount: float, compare: ?float}
 * @phpstan-type Group array{title: ?string, lines: list<Line>, total: float, compare: ?float}
 * @phpstan-type Section array{groups: list<Group>, total: float, compare: ?float}
 */
class AnnualReport
{
    public const SECTION_TITLES = ['asset' => 'Aktiven', 'liability' => 'Passiven', 'income' => 'Ertrag', 'expense' => 'Aufwand'];

    public function __construct(private CarryForward $carryForward) {}

    public function build(Accounting $accounting): array
    {
        $accounting->loadMissing('accounts', 'accountable', 'previousAccounting.accounts');
        $closing = $this->carryForward->balances([$accounting]);

        $previous = $accounting->previousAccounting;
        $previousByNumber = null;
        if ($previous) {
            $previousBalances = $this->carryForward->balances([$previous]);
            $previousByNumber = $previous->accounts->where('is_header', false)
                ->mapWithKeys(fn (Account $a) => [$a->number => $previousBalances[$a->id] ?? 0.0])->all();
        }

        $sections = [];
        foreach (array_keys(self::SECTION_TITLES) as $type) {
            $compare = in_array($type, ['asset', 'liability'])
                ? fn (Account $a) => (float) $a->opening_balance
                : ($previousByNumber === null ? null : fn (Account $a) => (float) ($previousByNumber[$a->number] ?? 0));
            $sections[$type] = $this->section($accounting->accounts->where('type', $type), $closing, $compare);
        }

        $result = round($sections['income']['total'] - $sections['expense']['total'], 2);
        $previousResult = $previousByNumber === null ? null
            : round($sections['income']['compare'] - $sections['expense']['compare'], 2);

        // Before closing, the year's result is not on an equity account yet
        $totalLiabilities = round($sections['liability']['total'] + $result, 2);
        $equityAccounts = $accounting->accounts->where('is_header', false)->where('type', 'liability')
            ->filter(fn (Account $a) => self::isEquity($a));
        $equity = round($equityAccounts->sum(fn (Account $a) => $closing[$a->id] ?? 0) + $result, 2);

        return [
            'accounting' => $accounting,
            'previous' => $previous,
            'sections' => $sections,
            'result' => $result,
            'previous_result' => $previousResult,
            'total_assets' => $sections['asset']['total'],
            'total_liabilities' => $totalLiabilities,
            'difference' => round($sections['asset']['total'] - $totalLiabilities, 2),
            'equity' => $equity,
            'debt' => round($totalLiabilities - $equity, 2),
        ];
    }

    /** Eigenkapital in the Swiss chart: class 28 and 29. */
    public static function isEquity(Account $account): bool
    {
        return preg_match('/^2[89]/', $account->number) === 1;
    }

    /** "Mehrertrag" / "Mehraufwand" - how an association names its result. */
    public static function resultLabel(float $result): string
    {
        return $result < 0 ? 'Mehraufwand' : 'Mehrertrag';
    }

    /**
     * @param  Collection<int, Account>  $accounts  of one type, in chart order
     * @param  array<int, float>  $closing
     * @return Section
     */
    private function section(Collection $accounts, array $closing, ?\Closure $compare): array
    {
        $groups = [];
        $current = ['title' => null, 'lines' => []];
        $flush = function () use (&$groups, &$current, $compare) {
            if ($current['lines']) {
                $current['total'] = round(array_sum(array_column($current['lines'], 'amount')), 2);
                $current['compare'] = $compare ? round(array_sum(array_column($current['lines'], 'compare')), 2) : null;
                $groups[] = $current;
            }
        };

        // In chart order (by number, as on the accounting's page)
        foreach ($accounts->sortBy('number', SORT_NATURAL) as $account) {
            if ($account->is_header) {
                $flush();
                $current = ['title' => $account->name, 'lines' => []];
                continue;
            }

            $line = [
                'number' => $account->number,
                'name' => $account->name,
                'amount' => round($closing[$account->id] ?? 0, 2),
                'compare' => $compare ? round($compare($account), 2) : null,
            ];
            if (abs($line['amount']) >= 0.005 || abs($line['compare'] ?? 0) >= 0.005) {
                $current['lines'][] = $line;
            }
        }
        $flush();

        return [
            'groups' => $groups,
            'total' => round(array_sum(array_column($groups, 'total')), 2),
            'compare' => $compare ? round(array_sum(array_column($groups, 'compare')), 2) : null,
        ];
    }
}
