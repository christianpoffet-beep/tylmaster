<?php

namespace App\Services\Reports;

use App\Models\Account;
use App\Models\Booking;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * The Jahresrechnung as a workbook: balance sheet, income statement, journal
 * (with the receipt numbers), one ledger per account and the receipt list -
 * what a tax office or an auditor wants to sort and filter.
 */
class AnnualReportSpreadsheet
{
    private const MONEY = "#,##0.00;-#,##0.00";

    /** @param  array  $report  from AnnualReport::build(), with 'journal' and 'receipts' from AnnualReportReceipts */
    public function write(array $report, string $path): void
    {
        $book = new Spreadsheet();
        $book->getProperties()->setTitle('Jahresrechnung ' . $report['accounting']->name)->setCreator('TYL Admin');

        $this->balanceSheet($book->getActiveSheet(), $report);
        $this->incomeStatement($book->createSheet(), $report);
        $this->journal($book->createSheet(), $report);
        $this->ledgers($book->createSheet(), $report);
        $this->receipts($book->createSheet(), $report);
        $book->setActiveSheetIndex(0);

        (new Xlsx($book))->save($path);
    }

    private function balanceSheet(Worksheet $sheet, array $report): void
    {
        $accounting = $report['accounting'];
        $sheet->setTitle('Bilanz');
        $row = $this->heading($sheet, $report, 'Bilanz per ' . $accounting->period_end->format('d.m.Y'));
        // The opening is the end of the year before: "31.12.2025 | 31.12.2024"
        $row = $this->columns($sheet, $row, ['Nr.', 'Konto', $accounting->period_end->format('d.m.Y'), $accounting->period_start->copy()->subDay()->format('d.m.Y')]);

        $row = $this->section($sheet, $row, 'Aktiven', $report['sections']['asset']);
        $row = $this->total($sheet, $row, 'Total Aktiven', $report['total_assets'], $report['sections']['asset']['compare']) + 1;

        $row = $this->section($sheet, $row, 'Passiven', $report['sections']['liability'], false);
        $row = $this->line($sheet, $row, '', 'Jahresergebnis (' . AnnualReport::resultLabel($report['result']) . ')', $report['result'], null);
        $row = $this->total($sheet, $row, 'Total Passiven', $report['total_liabilities'], $report['sections']['liability']['compare']);

        $this->finish($sheet, 4);
    }

    private function incomeStatement(Worksheet $sheet, array $report): void
    {
        $accounting = $report['accounting'];
        $sheet->setTitle('Erfolgsrechnung');
        $row = $this->heading($sheet, $report, 'Erfolgsrechnung ' . $accounting->period_start->format('d.m.Y') . ' – ' . $accounting->period_end->format('d.m.Y'));
        $headers = ['Nr.', 'Konto', $accounting->name];
        if ($report['previous']) $headers[] = $report['previous']->name;
        $row = $this->columns($sheet, $row, $headers);

        $row = $this->section($sheet, $row, 'Ertrag', $report['sections']['income']);
        $row = $this->section($sheet, $row + 1, 'Aufwand', $report['sections']['expense']);
        $this->total($sheet, $row + 1, AnnualReport::resultLabel($report['result']), $report['result'], $report['previous_result']);

        $this->finish($sheet, count($headers));
    }

    private function journal(Worksheet $sheet, array $report): void
    {
        $sheet->setTitle('Journal');
        $row = $this->heading($sheet, $report, 'Buchungsjournal');
        $row = $this->columns($sheet, $row, ['Beleg', 'Datum', 'Text', 'Soll', 'Haben', 'Betrag', 'Referenz', 'Belegdateien']);

        /** @var Booking $booking */
        foreach ($report['journal'] as $booking) {
            $sheet->fromArray([
                $booking->receipt_number,
                $booking->booking_date->format('d.m.Y'),
                $booking->description,
                $booking->debitAccount?->number . ' ' . $booking->debitAccount?->name,
                $booking->creditAccount?->number . ' ' . $booking->creditAccount?->name,
                (float) $booking->amount,
                $booking->reference,
                implode("\n", array_map(fn ($r) => $r['path'] ?? "fehlt: {$r['title']}", $report['receipts'][$booking->id] ?? [])),
            ], null, "A{$row}");
            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode(self::MONEY);
            $row++;
        }
        $sheet->setCellValue("E{$row}", 'Total');
        $sheet->setCellValue("F{$row}", round($report['journal']->sum(fn ($b) => (float) $b->amount), 2));
        $sheet->getStyle("E{$row}:F{$row}")->getFont()->setBold(true);
        $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode(self::MONEY);

        $this->finish($sheet, 8);
        $sheet->setAutoFilter('A4:H' . max(4, $row - 1));
    }

    /** One block per account: opening, every booking with its counter account, running balance, closing. */
    private function ledgers(Worksheet $sheet, array $report): void
    {
        $sheet->setTitle('Kontoblätter');
        $row = $this->heading($sheet, $report, 'Kontoblätter');
        $byAccount = [];
        foreach ($report['journal'] as $booking) {
            $byAccount[$booking->debit_account_id][] = [$booking, 'debit'];
            $byAccount[$booking->credit_account_id][] = [$booking, 'credit'];
        }

        /** @var Account $account */
        foreach ($report['accounting']->accounts->where('is_header', false) as $account) {
            $entries = $byAccount[$account->id] ?? [];
            $balance = (float) $account->opening_balance;
            if (!$entries && abs($balance) < 0.005) continue;

            $sheet->setCellValue("A{$row}", "{$account->number} {$account->name}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
            $row = $this->columns($sheet, $row + 1, ['Datum', 'Beleg', 'Text', 'Gegenkonto', 'Soll', 'Haben', 'Saldo']);

            $growsWithDebit = in_array($account->type, ['asset', 'expense']);
            $sheet->fromArray([null, null, 'Eröffnung', null, null, null, $balance], null, "A{$row}");
            $this->money($sheet, "G{$row}");
            $row++;

            foreach ($entries as [$booking, $side]) {
                $amount = (float) $booking->amount;
                $balance += ($side === 'debit') === $growsWithDebit ? $amount : -$amount;
                $counter = $side === 'debit' ? $booking->creditAccount : $booking->debitAccount;
                $sheet->fromArray([
                    $booking->booking_date->format('d.m.Y'), $booking->receipt_number, $booking->description,
                    $counter ? "{$counter->number} {$counter->name}" : '',
                    $side === 'debit' ? $amount : null, $side === 'credit' ? $amount : null, round($balance, 2),
                ], null, "A{$row}");
                $this->money($sheet, "E{$row}:G{$row}");
                $row++;
            }

            $sheet->fromArray([null, null, 'Schlusssaldo', null, null, null, round($balance, 2)], null, "A{$row}");
            $sheet->getStyle("C{$row}:G{$row}")->getFont()->setBold(true);
            $this->money($sheet, "G{$row}");
            $row += 2;
        }

        $this->finish($sheet, 7, freeze: false);
    }

    private function receipts(Worksheet $sheet, array $report): void
    {
        $sheet->setTitle('Belege');
        $row = $this->heading($sheet, $report, 'Belegverzeichnis');
        $row = $this->columns($sheet, $row, ['Beleg', 'Datum', 'Buchung', 'Betrag', 'Herkunft', 'Datei im ZIP']);

        foreach ($report['journal'] as $booking) {
            foreach ($report['receipts'][$booking->id] ?? [] as $receipt) {
                $sheet->fromArray([
                    $booking->receipt_number, $booking->booking_date->format('d.m.Y'), $booking->description,
                    (float) $booking->amount, $receipt['source'], $receipt['path'] ?? "Datei fehlt: {$receipt['title']}",
                ], null, "A{$row}");
                $this->money($sheet, "D{$row}");
                $row++;
            }
        }
        foreach ($report['statements'] ?? [] as $statement) {
            $sheet->fromArray(['', '', $statement['label'], null, 'Bankauszug', $statement['path']], null, "A{$row}");
            $row++;
        }

        $this->finish($sheet, 6);
    }

    // --- building blocks ---------------------------------------------------

    private function heading(Worksheet $sheet, array $report, string $title): int
    {
        $accounting = $report['accounting'];
        $sheet->setCellValue('A1', $accounting->accountable_name . ' · ' . $accounting->name);
        $sheet->setCellValue('A2', $title . ' · Beträge in ' . $accounting->currency);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        return 4;
    }

    private function columns(Worksheet $sheet, int $row, array $headers): int
    {
        $sheet->fromArray($headers, null, "A{$row}");
        $last = chr(ord('A') + count($headers) - 1);
        $style = $sheet->getStyle("A{$row}:{$last}{$row}");
        $style->getFont()->setBold(true);
        $style->getBorders()->getBottom()->setBorderStyle('thin');

        return $row + 1;
    }

    private function section(Worksheet $sheet, int $row, string $title, array $section, bool $withTotal = true): int
    {
        $sheet->setCellValue("A{$row}", $title);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $row++;

        foreach ($section['groups'] as $group) {
            // A header named like its section ("Ertrag" under Ertrag) needs no title of its own
            $groupTitle = $group['title'] !== $title ? $group['title'] : null;
            if ($groupTitle) {
                $sheet->setCellValue("B{$row}", $groupTitle);
                $sheet->getStyle("B{$row}")->getFont()->setBold(true);
                $row++;
            }
            foreach ($group['lines'] as $line) {
                $row = $this->line($sheet, $row, $line['number'], $line['name'], $line['amount'], $line['compare']);
            }
            if ($groupTitle && count($section['groups']) > 1) {
                $row = $this->total($sheet, $row, 'Total ' . $groupTitle, $group['total'], $group['compare'], false);
            }
        }

        return $withTotal ? $this->total($sheet, $row, 'Total ' . $title, $section['total'], $section['compare']) : $row;
    }

    private function line(Worksheet $sheet, int $row, string $number, string $name, float $amount, ?float $compare): int
    {
        $sheet->fromArray([$number, $name, $amount, $compare], null, "A{$row}");
        $this->money($sheet, "C{$row}:D{$row}");

        return $row + 1;
    }

    private function total(Worksheet $sheet, int $row, string $label, float $amount, ?float $compare, bool $strong = true): int
    {
        $sheet->fromArray([null, $label, $amount, $compare], null, "A{$row}");
        $this->money($sheet, "C{$row}:D{$row}");
        $style = $sheet->getStyle("B{$row}:D{$row}");
        $style->getFont()->setBold($strong)->setItalic(!$strong);
        $style->getBorders()->getTop()->setBorderStyle('thin');

        return $row + 1;
    }

    private function money(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getNumberFormat()->setFormatCode(self::MONEY);
    }

    private function finish(Worksheet $sheet, int $columns, bool $freeze = true): void
    {
        for ($i = 0; $i < $columns; $i++) {
            $sheet->getColumnDimension(chr(ord('A') + $i))->setAutoSize(true);
        }
        // Title and column headers stay in view
        if ($freeze) $sheet->freezePane('A5');
    }
}
