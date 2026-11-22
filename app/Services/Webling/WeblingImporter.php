<?php

namespace App\Services\Webling;

use App\Models\Account;
use App\Models\Accounting;
use App\Models\Booking;
use App\Models\ChartTemplate;
use App\Services\CarryForward;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A year from Webling as an accounting here: the Webling export has no
 * single bookings, only opening, closing and the year's total per account.
 * So the year is rebuilt from those:
 *
 * - the chart of a template (account numbers as in the later years), each
 *   Webling account matched to the template account of the same type with
 *   the most similar name ("Herstellungskosten für Tonträger" = 6004
 *   "Herstellungskosten Tonträger"); an account without a match is added
 *   to its group;
 * - the Eröffnungsbilanz as opening balances;
 * - one collective booking per account against the bank, dated the last
 *   day: each income and expense account its year's total, each other
 *   balance sheet account its change. Income statement and closing balance
 *   sheet then equal Webling's to the centime.
 */
class WeblingImporter
{
    private const RANGES = ['asset' => [1000, 1999], 'liability' => [2000, 2999], 'income' => [3000, 3999], 'expense' => [4000, 9999]];
    private const STOPWORDS = ['fur', 'von', 'und', 'der', 'die', 'das', 'aus', 'an', 'gmbh', 'ag', 'etc', 'usw'];

    public function __construct(private CarryForward $carryForward) {}

    /**
     * @return array{accounting: Accounting, added: list<string>, warnings: list<string>}
     */
    public function import(string $path, Model $owner, ?string $name, ?ChartTemplate $template, ?string $fileName = null): array
    {
        $export = WeblingExport::read($path);
        $year = substr($export['period_end'], 0, 4);
        [$chart, $added] = $this->chart($export['accounts'], $template);

        // $chart by reference: the accounts created here are checked afterwards
        $accounting = DB::transaction(function () use ($export, $owner, $name, $template, &$chart, $year, $fileName) {
            $accounting = Accounting::create([
                'accountable_type' => $owner::class,
                'accountable_id' => $owner->id,
                'name' => trim((string) $name) ?: "Buchhaltung {$year} (Webling)",
                'currency' => 'CHF',
                'period_start' => $export['period_start'],
                'period_end' => $export['period_end'],
                'chart_template_id' => $template?->id,
                'notes' => 'Aus Webling übernommen' . ($fileName ? " («{$fileName}»)" : '') . ' am ' . now('Europe/Zurich')->format('d.m.Y')
                    . '. Eröffnungsbilanz als Eröffnungssaldi, das Jahr als Sammelbuchungen pro Konto.',
            ]);

            foreach ($chart as $i => &$line) {
                $line['model'] = $accounting->accounts()->create([
                    'number' => $line['number'], 'name' => $line['name'], 'type' => $line['type'],
                    'is_header' => $line['is_header'], 'opening_balance' => $line['webling']['opening'] ?? 0, 'sort_order' => $i,
                ]);
            }
            unset($line);

            $this->collectiveBookings($accounting, $chart, $year);

            return $accounting;
        });

        return ['accounting' => $accounting, 'added' => $added, 'warnings' => $this->check($accounting, $chart, $export)];
    }

    /**
     * The chart: the template's accounts with the Webling figures on the
     * matching ones, plus the Webling accounts the template does not know.
     *
     * @return array{0: list<array{number: string, name: string, type: string, is_header: bool, webling: ?array}>, 1: list<string>}
     */
    private function chart(array $weblingAccounts, ?ChartTemplate $template): array
    {
        $chart = $template
            ? $template->accounts()->orderBy('sort_order')->orderBy('number')->get()
                ->map(fn ($a) => ['number' => $a->number, 'name' => $a->name, 'type' => $a->type, 'is_header' => (bool) $a->is_header, 'webling' => null])
                ->all()
            : [];

        // Accounts first (best match wins), then the groups of the ones left over
        $pairs = [];
        foreach ($weblingAccounts as $w => $account) {
            foreach ($chart as $c => $line) {
                if ($line['is_header'] || $line['type'] !== $account['type']) continue;
                if ($score = self::similarity($account['name'], $line['name'])) $pairs[] = [$score, $w, $c];
            }
        }
        usort($pairs, fn ($a, $b) => $b[0] <=> $a[0]);
        $matched = [];
        foreach ($pairs as [$score, $w, $c]) {
            if (isset($matched[$w]) || $chart[$c]['webling'] !== null) continue;
            $chart[$c]['webling'] = $weblingAccounts[$w];
            $matched[$w] = true;
        }

        $added = [];
        foreach ($weblingAccounts as $w => $account) {
            if (isset($matched[$w])) continue;
            // An empty Webling account the template does not know would only clutter its chart
            if ($template && abs($account['opening']) < 0.005 && abs($account['closing']) < 0.005) continue;
            $chart = $this->addToGroup($chart, $account);
            $added[] = $account['name'];
        }

        usort($chart, fn ($a, $b) => strnatcmp($a['number'], $b['number']));

        return [$chart, $added];
    }

    /** A Webling account the template does not have: into its group (found by name), or a new group. */
    private function addToGroup(array $chart, array $account): array
    {
        $type = $account['type'];
        $headers = array_filter($chart, fn ($l) => $l['is_header'] && $l['type'] === $type);

        $header = null;
        $best = [0, 0];
        foreach ($headers as $line) {
            $score = self::similarity((string) $account['group'], $line['name']);
            if ($score && $score > $best) [$best, $header] = [$score, $line];
        }

        if (!$header) {
            [$from, $to] = self::RANGES[$type];
            // Equity in the Swiss chart is class 28
            $base = $type === 'liability' && str_contains(mb_strtolower((string) $account['group']), 'eigenkapital') ? 2800 : $from;
            $number = $base;
            $taken = array_column($chart, 'number');
            while (in_array((string) $number, $taken, true) && $number + 100 <= $to) $number += 100;
            $header = ['number' => (string) $number, 'name' => $account['group'] ?: self::SECTION_NAMES[$type], 'type' => $type, 'is_header' => true, 'webling' => null];
            $chart[] = $header;
        }

        // Next free number after the group's header and accounts
        $taken = array_column($chart, 'number');
        $number = (int) $header['number'] + 1;
        while (in_array((string) $number, $taken, true)) $number++;
        $chart[] = ['number' => (string) $number, 'name' => $account['name'], 'type' => $type, 'is_header' => false, 'webling' => $account];

        return $chart;
    }

    private const SECTION_NAMES = ['asset' => 'Aktiven', 'liability' => 'Passiven', 'income' => 'Ertrag', 'expense' => 'Aufwand'];

    /**
     * One booking per account against the bank: income and expense accounts
     * their total, other balance sheet accounts their change over the year.
     */
    private function collectiveBookings(Accounting $accounting, array $chart, string $year): void
    {
        $lines = array_values(array_filter($chart, fn ($l) => !$l['is_header'] && $l['webling']));
        $bank = $this->bankLine($lines);
        if (!$bank) {
            throw new WeblingException('Im Export gibt es kein Bankkonto, gegen das die Jahressummen gebucht werden können.');
        }

        foreach ($lines as $line) {
            if ($line['model']->id === $bank['model']->id) continue;

            $webling = $line['webling'];
            // In the account's own direction: income and liabilities grow on the credit side
            $amount = in_array($line['type'], ['income', 'expense']) ? $webling['closing'] : $webling['closing'] - $webling['opening'];
            $amount = round($amount, 2);
            if (abs($amount) < 0.005) continue;

            $growsWithDebit = in_array($line['type'], ['asset', 'expense']);
            $accountOnDebit = $growsWithDebit === ($amount > 0);

            Booking::create([
                'accounting_id' => $accounting->id,
                'booking_date' => $accounting->period_end,
                'description' => in_array($line['type'], ['income', 'expense'])
                    ? "Webling {$year}: Jahressumme {$line['name']}"
                    : "Webling {$year}: Veränderung {$line['name']}",
                'reference' => 'Webling',
                'debit_account_id' => $accountOnDebit ? $line['model']->id : $bank['model']->id,
                'credit_account_id' => $accountOnDebit ? $bank['model']->id : $line['model']->id,
                'amount' => abs($amount),
            ]);
        }
    }

    /** The account called "Bank" - or else the asset account with the most money on it. */
    private function bankLine(array $lines): ?array
    {
        $assets = array_filter($lines, fn ($l) => $l['type'] === 'asset');
        foreach ($assets as $line) {
            if (self::normalize($line['name']) === 'bank' || self::normalize($line['webling']['name']) === 'bank') return $line;
        }
        usort($assets, fn ($a, $b) => $b['webling']['closing'] <=> $a['webling']['closing']);

        return $assets[0] ?? null;
    }

    /** Do the rebuilt year's closing balances and result equal Webling's? */
    private function check(Accounting $accounting, array $chart, array $export): array
    {
        $warnings = [];
        if (abs($export['balance_difference']) >= 0.005) {
            $warnings[] = sprintf('Schon im Webling-Export gehen Bilanz und Erfolgsrechnung um %s nicht auf.', number_format($export['balance_difference'], 2, '.', "'"));
        }

        $accounting->load('accounts');
        $balances = $this->carryForward->balances([$accounting]);
        foreach ($chart as $line) {
            if ($line['is_header'] || !$line['webling']) continue;
            $balance = $balances[$line['model']->id] ?? 0;
            if (abs($balance - $line['webling']['closing']) >= 0.005) {
                $warnings[] = sprintf('%s %s: hier %s, in Webling %s.', $line['number'], $line['name'],
                    number_format($balance, 2, '.', "'"), number_format($line['webling']['closing'], 2, '.', "'"));
            }
        }

        return $warnings;
    }

    /**
     * How alike two account names are, 0 when not alike enough. Words are
     * compared without accents and filler words; a word may be the start of
     * the other ("Gebühren" / "Gebührenbelastung"). Score: shared words over
     * the shorter name, then over both - an exact name beats a longer one.
     *
     * @return array{0: float, 1: float}|null
     */
    public static function similarity(string $a, string $b): ?array
    {
        // "Pay Pal" is "PayPal"
        $squash = fn ($text) => preg_replace('/[^a-z0-9]+/', '', self::normalize($text));
        if ($squash($a) !== '' && $squash($a) === $squash($b)) return [1.0, 1.0];

        $wordsA = self::words($a);
        $wordsB = self::words($b);
        if (!$wordsA || !$wordsB) return null;

        $shared = 0;
        foreach ($wordsA as $word) {
            foreach ($wordsB as $other) {
                if ($word === $other || (min(strlen($word), strlen($other)) >= 5 && (str_starts_with($word, $other) || str_starts_with($other, $word)))) {
                    $shared++;
                    break;
                }
            }
        }

        $overlap = $shared / min(count($wordsA), count($wordsB));
        $jaccard = $shared / (count($wordsA) + count($wordsB) - $shared);

        return $overlap >= 0.75 ? [round($overlap, 3), round($jaccard, 3)] : null;
    }

    /** @return list<string> */
    private static function words(string $text): array
    {
        $words = preg_split('/[^a-z0-9]+/', self::normalize($text), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_diff($words, self::STOPWORDS)));
    }

    private static function normalize(string $text): string
    {
        return strtolower(Str::ascii($text));
    }
}
