<?php

namespace App\Services\Reports;

use App\Models\Accounting;
use App\Models\Booking;
use App\Models\Document;
use App\Services\DocumentStorage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The journal with receipt numbers (1, 2, 3 ... in booking order) and the
 * receipts behind each booking: files attached to the booking itself, to the
 * expense it paid and to its invoice. In the ZIP they are named after their
 * number and date ("Belege/0007_2025-03-14_Quittung-Saiten.pdf"), so the
 * journal and the folder sort the same way. The bank statements read into the
 * accounting go along as well.
 *
 * A file attached to several bookings (an invoice: claim and payment) is
 * packed once, under the first booking, and listed for each of them.
 *
 * @phpstan-type Receipt array{path: ?string, file: ?string, title: string, source: string}
 */
class AnnualReportReceipts
{
    /**
     * @return array{journal: Collection<int, Booking>, receipts: array<int, list<Receipt>>, statements: list<array{label: string, path: string, file: string}>, files: int, missing: int, without: int}
     */
    public function collect(Accounting $accounting): array
    {
        $journal = $accounting->bookings()->reorder()->orderBy('booking_date')->orderBy('id')
            ->with(['debitAccount', 'creditAccount', 'documents', 'expense.documents', 'invoice.documents'])
            ->get();

        $packed = [];   // document id => Receipt (without source)
        $receipts = [];
        foreach ($journal->values() as $index => $booking) {
            $number = $index + 1;
            $booking->setAttribute('receipt_number', $number);

            foreach ($this->documentsOf($booking) as [$document, $source]) {
                $packed[$document->id] ??= $this->pack($document, $number, $booking);
                $receipts[$booking->id][] = $packed[$document->id] + ['source' => $source];
            }
        }

        $statements = [];
        foreach ($accounting->bankImports()->orderBy('statement_from')->get() as $import) {
            $file = DocumentStorage::path($import->file_path);
            if (!$file || !is_file($file)) continue;
            $statements[] = [
                'label' => trim("Bankauszug {$import->file_name} " . ($import->statement_from && $import->statement_to
                    ? $import->statement_from->format('d.m.Y') . ' – ' . $import->statement_to->format('d.m.Y') : '')),
                'path' => 'Bankauszuege/' . self::fileName(pathinfo($import->file_name, PATHINFO_FILENAME)) . '.' . (pathinfo($import->file_name, PATHINFO_EXTENSION) ?: 'csv'),
                'file' => $file,
            ];
        }

        return [
            'journal' => $journal,
            'receipts' => $receipts,
            'statements' => $statements,
            'files' => collect($packed)->whereNotNull('file')->count(),
            'missing' => collect($packed)->whereNull('file')->count(),
            'without' => $journal->filter(fn (Booking $b) => empty($receipts[$b->id]))->count(),
        ];
    }

    /** @return list<array{0: Document, 1: string}> */
    private function documentsOf(Booking $booking): array
    {
        $documents = [];
        foreach ($booking->documents as $document) {
            $documents[] = [$document, 'Buchung'];
        }
        foreach ($booking->expense?->documents ?? [] as $document) {
            $documents[] = [$document, 'Ausgabe «' . $booking->expense->description . '»'];
        }
        foreach ($booking->invoice?->documents ?? [] as $document) {
            $documents[] = [$document, 'Rechnung ' . $booking->invoice->invoice_number];
        }

        return $documents;
    }

    /** @return array{path: ?string, file: ?string, title: string} */
    private function pack(Document $document, int $number, Booking $booking): array
    {
        $file = $document->absolutePath();
        $file = $file && is_file($file) ? $file : null;
        $extension = strtolower(pathinfo($document->file_path ?? '', PATHINFO_EXTENSION) ?: pathinfo($document->title ?? '', PATHINFO_EXTENSION) ?: 'pdf');
        $title = pathinfo($document->title ?: 'Beleg', PATHINFO_FILENAME);

        return [
            'path' => $file ? sprintf('Belege/%04d_%s_%s.%s', $number, $booking->booking_date->format('Y-m-d'), self::fileName($title), $extension) : null,
            'file' => $file,
            'title' => $document->title ?: 'Beleg',
        ];
    }

    /** Plain ASCII, so the names look the same in every unzip tool ("Quittung Räume" -> "Quittung-Raeume"). */
    public static function fileName(string $text, int $max = 60): string
    {
        $name = trim(preg_replace('/[^A-Za-z0-9._-]+/', '-', Str::ascii($text, 'de')), '-.');

        return Str::limit($name !== '' ? $name : 'Datei', $max, '');
    }
}
