<?php

namespace App\Services\Webling;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Reads the Webling export "Bilanz und Erfolgsrechnung" (xlsx). Each sheet
 * has two sides next to each other - Aufwand | Ertrag, Aktiv | Passiv - with
 * the account names in the column of the side's heading and the amount next
 * to it ("Aktuell", in the opening balance sheet the column right of the
 * name). Rows without any number are group headings ("Umlaufvermögen"),
 * rows without a name are subtotals, "Total ..." / "Gewinn" / "Verlust" are
 * skipped.
 *
 * @phpstan-type WeblingAccount array{type: string, group: ?string, name: string, opening: float, closing: float}
 */
class WeblingExport
{
    private const SIDES = [
        'Erfolgsrechnung' => ['Aufwand' => 'expense', 'Ertrag' => 'income'],
        'Bilanz' => ['Aktiv' => 'asset', 'Passiv' => 'liability'],
        'Eröffnungsbilanz' => ['Aktiv' => 'asset', 'Passiv' => 'liability'],
    ];

    private const SKIP = ['gewinn', 'verlust', 'gewinn / verlust'];

    /**
     * @return array{period_start: string, period_end: string, accounts: list<WeblingAccount>, result: float, balance_difference: float}
     */
    public static function read(string $path): array
    {
        try {
            $book = IOFactory::load($path);
        } catch (\Throwable $e) {
            throw new WeblingException('Die Datei lässt sich nicht als Excel-Datei lesen.');
        }

        $sheets = [];
        foreach (array_keys(self::SIDES) as $name) {
            $sheet = $book->getSheetByName($name);
            if (!$sheet) {
                throw new WeblingException("Im Export fehlt das Blatt «{$name}». Bitte in Webling «Bilanz und Erfolgsrechnung» als Excel exportieren.");
            }
            $sheets[$name] = $sheet->toArray(null, true, false);
        }

        [$start, $end] = self::period($sheets['Erfolgsrechnung']);

        $accounts = [];
        foreach (['Erfolgsrechnung', 'Bilanz'] as $name) {
            foreach (self::SIDES[$name] as $label => $type) {
                foreach (self::side($sheets[$name], $label) as $row) {
                    $accounts[self::key($type, $row)] = [
                        'type' => $type, 'group' => $row['group'], 'name' => $row['name'], 'opening' => 0.0, 'closing' => $row['amount'],
                    ];
                }
            }
        }
        // Opening balances: the Eröffnungsbilanz already holds last year's result in the equity
        foreach (self::SIDES['Eröffnungsbilanz'] as $label => $type) {
            foreach (self::side($sheets['Eröffnungsbilanz'], $label) as $row) {
                $key = self::key($type, $row);
                $accounts[$key] ??= ['type' => $type, 'group' => $row['group'], 'name' => $row['name'], 'opening' => 0.0, 'closing' => 0.0];
                $accounts[$key]['opening'] = $row['amount'];
            }
        }
        $accounts = array_values($accounts);

        $sum = fn (string $type, string $field) => array_sum(array_column(array_filter($accounts, fn ($a) => $a['type'] === $type), $field));
        $result = round($sum('income', 'closing') - $sum('expense', 'closing'), 2);

        if (!array_filter($accounts, fn ($a) => in_array($a['type'], ['asset', 'liability']))) {
            throw new WeblingException('Im Export wurden keine Bilanzkonten gefunden.');
        }

        return [
            'period_start' => $start,
            'period_end' => $end,
            'accounts' => $accounts,
            'result' => $result,
            // Aktiven = Passiven + Ergebnis, as long as the export is complete
            'balance_difference' => round($sum('asset', 'closing') - $sum('liability', 'closing') - $result, 2),
        ];
    }

    /** "Erfolgsrechnung Buchungsperiode 2024 (01.01.2024 - 31.12.2024)" */
    private static function period(array $rows): array
    {
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                if (is_string($cell) && preg_match('/(\d{1,2})\.(\d{1,2})\.(\d{4})\s*[-–]\s*(\d{1,2})\.(\d{1,2})\.(\d{4})/u', $cell, $m)) {
                    return [sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]), sprintf('%04d-%02d-%02d', $m[6], $m[5], $m[4])];
                }
            }
        }

        throw new WeblingException('Die Buchungsperiode (z.B. «01.01.2024 - 31.12.2024») steht nicht in der Erfolgsrechnung.');
    }

    /** @return list<array{group: ?string, name: string, amount: float}> */
    private static function side(array $rows, string $label): array
    {
        [$headerRow, $nameCol] = self::find($rows, $label);
        $header = $rows[$headerRow];

        // "Aktuell" right of the name - or, in the opening balance sheet, simply the next column
        $amountCol = $nameCol + 1;
        $valueCols = [$amountCol];
        foreach ($header as $col => $cell) {
            if ($col > $nameCol && is_string($cell) && trim($cell) === 'Aktuell') {
                $amountCol = $col;
                $valueCols = [$col, $col + 1, $col + 2]; // Aktuell, %, Vorjahr
                break;
            }
        }

        $entries = [];
        $group = null;
        foreach (array_slice($rows, $headerRow + 1) as $row) {
            $name = trim((string) ($row[$nameCol] ?? ''));
            if ($name === '' || str_starts_with(mb_strtolower($name), 'total') || in_array(mb_strtolower($name), self::SKIP, true)) {
                continue;
            }

            $hasNumber = (bool) array_filter($valueCols, fn ($col) => is_numeric($row[$col] ?? null));
            if (!$hasNumber) {
                $group = $name;
                continue;
            }

            $entries[] = ['group' => $group, 'name' => $name, 'amount' => round((float) ($row[$amountCol] ?? 0), 2)];
        }

        return $entries;
    }

    private static function find(array $rows, string $label): array
    {
        foreach ($rows as $r => $row) {
            foreach ($row as $c => $cell) {
                if (is_string($cell) && trim($cell) === $label) {
                    return [$r, $c];
                }
            }
        }

        throw new WeblingException("Die Spalte «{$label}» fehlt im Export.");
    }

    private static function key(string $type, array $row): string
    {
        return $type . '|' . mb_strtolower((string) $row['group']) . '|' . mb_strtolower($row['name']);
    }
}
