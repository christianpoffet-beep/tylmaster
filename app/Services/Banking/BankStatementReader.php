<?php

namespace App\Services\Banking;

use Carbon\CarbonImmutable;

/**
 * Reads a bank statement exported from the e-banking into plain rows.
 *
 * So far: the Raiffeisen CSV ("IBAN;Booked At;Text;Credit/Debit Amount;
 * Balance;Valuta Date"). Each movement is one line; the line below it, with
 * the first two columns empty, carries the details (address, reference,
 * message) and is folded into the movement. Lines with amount 0 (the yearly
 * "Abschlussbetreffnis") are no movement and are left out, but their balance
 * still counts for the closing balance.
 *
 * Another bank is a new entry in COLUMNS (if only the headers differ) or a
 * method of its own.
 *
 * @phpstan-type Row array{booking_date: string, value_date: ?string, amount: float, balance_after: ?float, text: string, details: ?string, fingerprint: string}
 */
class BankStatementReader
{
    /** Header aliases per field, compared case-insensitively. */
    private const COLUMNS = [
        'iban' => ['iban'],
        'date' => ['booked at', 'buchungsdatum', 'datum', 'booking date'],
        'text' => ['text', 'buchungstext', 'beschreibung'],
        'amount' => ['credit/debit amount', 'betrag', 'amount'],
        'balance' => ['balance', 'saldo'],
        'valuta' => ['valuta date', 'valuta', 'valutadatum', 'value date'],
    ];

    /**
     * @return array{format: string, iban: ?string, rows: list<Row>, from: ?string, to: ?string, opening_balance: ?float, closing_balance: ?float}
     */
    public static function read(string $path): array
    {
        $content = @file_get_contents($path);
        if ($content === false || trim($content) === '') {
            throw new BankStatementException('Die Datei ist leer oder konnte nicht gelesen werden.');
        }

        return self::parse($content);
    }

    public static function parse(string $content): array
    {
        $content = self::toUtf8($content);
        $lines = preg_split('/\r\n|\r|\n/', $content);

        $header = null;
        $map = null;
        $movements = [];

        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $cells = array_map('trim', str_getcsv($line, ';', '"', ''));

            if ($map === null) {
                $map = self::mapHeader($cells);
                if ($map === null) {
                    throw new BankStatementException(
                        'Unbekanntes Format. Unterstützt wird bisher der CSV-Export von Raiffeisen '
                        . '(Spalten «IBAN; Booked At; Text; Credit/Debit Amount; Balance; Valuta Date»).'
                    );
                }
                continue;
            }

            $date = $cells[$map['date']] ?? '';

            // Details line under a movement: only the text column is filled
            if ($date === '') {
                $extra = $cells[$map['text']] ?? '';
                if ($extra !== '' && $movements) {
                    $last = array_key_last($movements);
                    $movements[$last]['details'] = trim(($movements[$last]['details'] ?? '') . ' ' . $extra);
                }
                continue;
            }

            $movements[] = [
                'iban' => self::normalizeIban($cells[$map['iban']] ?? ''),
                'booking_date' => self::parseDate($date),
                'value_date' => isset($map['valuta']) && ($cells[$map['valuta']] ?? '') !== '' ? self::parseDate($cells[$map['valuta']]) : null,
                'amount' => self::parseAmount($cells[$map['amount']] ?? ''),
                'balance_after' => isset($map['balance']) && ($cells[$map['balance']] ?? '') !== '' ? self::parseAmount($cells[$map['balance']]) : null,
                'text' => $cells[$map['text']] ?? '',
                'details' => null,
            ];
        }

        if ($map === null) {
            throw new BankStatementException('Die Datei enthält keine Kopfzeile.');
        }
        if (!$movements) {
            throw new BankStatementException('Die Datei enthält keine Bewegungen.');
        }

        // Oldest first, whatever order the bank exported in
        if ($movements[0]['booking_date'] > $movements[array_key_last($movements)]['booking_date']) {
            $movements = array_reverse($movements);
        }

        $first = $movements[0];
        $last = $movements[array_key_last($movements)];
        $iban = collect($movements)->pluck('iban')->filter()->first();

        $rows = [];
        $seen = [];
        foreach ($movements as $movement) {
            if (round($movement['amount'], 2) == 0.0) continue;

            $key = implode('|', [
                $iban, $movement['booking_date'], number_format($movement['amount'], 2, '.', ''),
                $movement['balance_after'] === null ? '' : number_format($movement['balance_after'], 2, '.', ''),
                $movement['text'], $movement['details'],
            ]);
            // Two identical lines in one file (no balance to tell them apart) are still two movements
            $seen[$key] = ($seen[$key] ?? 0) + 1;

            unset($movement['iban']);
            $movement['fingerprint'] = hash('sha256', $key . '#' . $seen[$key]);
            $rows[] = $movement;
        }

        return [
            'format' => 'raiffeisen_csv',
            'iban' => $iban,
            'rows' => $rows,
            'from' => $first['booking_date'],
            'to' => $last['booking_date'],
            'opening_balance' => $first['balance_after'] !== null ? round($first['balance_after'] - $first['amount'], 2) : null,
            'closing_balance' => $last['balance_after'],
        ];
    }

    /** @return array<string,int>|null column index per field, null when this is no known header */
    private static function mapHeader(array $cells): ?array
    {
        $cells = array_map(fn ($c) => mb_strtolower(trim($c, " \t\"\u{FEFF}")), $cells);
        $map = [];
        foreach (self::COLUMNS as $field => $aliases) {
            foreach ($cells as $index => $cell) {
                if (in_array($cell, $aliases, true)) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        foreach (['date', 'text', 'amount'] as $required) {
            if (!isset($map[$required])) return null;
        }
        $map['iban'] ??= -1;

        return $map;
    }

    private static function toUtf8(string $content): string
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // Raiffeisen exports Windows-1252 ("Zürich" as one byte 0xFC)
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        return $content;
    }

    private static function parseDate(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2,4})/', $value, $m)) {
            $year = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
            return CarbonImmutable::createFromDate((int) $year, (int) $m[2], (int) $m[1])->toDateString();
        }

        throw new BankStatementException("Unbekanntes Datum «{$value}».");
    }

    private static function parseAmount(string $value): float
    {
        $clean = str_replace(["'", '’', ' ', "\u{00A0}"], '', trim($value));
        // "1234,50" (comma as decimal separator) - but "1,234.50" keeps its point
        if (str_contains($clean, ',') && !str_contains($clean, '.')) {
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }

        if ($clean === '' || !is_numeric($clean)) {
            throw new BankStatementException("Unbekannter Betrag «{$value}».");
        }

        return round((float) $clean, 2);
    }

    private static function normalizeIban(string $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', $value));
    }
}
