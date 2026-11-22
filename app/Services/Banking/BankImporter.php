<?php

namespace App\Services\Banking;

use App\Models\Account;
use App\Models\Accounting;
use App\Models\BankImport;
use App\Models\BankTransaction;
use App\Services\DocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Puts the movements of a statement into the inbox of an accounting.
 *
 * Nothing is booked here. Movements already in the inbox (overlapping
 * exports) and movements outside the period of the accounting are counted
 * and skipped. The file itself is kept as the receipt of the import.
 */
class BankImporter
{
    public function import(Accounting $accounting, Account $account, UploadedFile $file): BankImport
    {
        if ($accounting->is_closed) {
            throw new BankStatementException('Die Buchhaltung ist abgeschlossen.');
        }
        if ((int) $account->accounting_id !== (int) $accounting->id || $account->is_header || $account->type !== 'asset') {
            throw new BankStatementException('Bitte ein Aktivkonto dieser Buchhaltung als Bankkonto wählen.');
        }

        $statement = BankStatementReader::read($file->getRealPath());
        $this->checkIban($accounting, $account, $statement['iban']);

        $path = $file->storeAs(
            'bank-imports',
            now()->format('Ymd-His') . '-' . Str::random(6) . '.' . ($file->getClientOriginalExtension() ?: 'csv'),
            DocumentStorage::DISK
        );

        try {
            return $this->store($accounting, $account, $file, $statement, $path ?: null);
        } catch (\Throwable $e) {
            DocumentStorage::delete($path ?: null);
            throw $e;
        }
    }

    private function store(Accounting $accounting, Account $account, UploadedFile $file, array $statement, ?string $path): BankImport
    {
        return DB::transaction(function () use ($accounting, $account, $file, $statement, $path) {
            if ($statement['iban'] && !$account->iban) {
                $account->update(['iban' => $statement['iban']]);
            }

            $import = BankImport::create([
                'accounting_id' => $accounting->id,
                'account_id' => $account->id,
                'format' => $statement['format'],
                'iban' => $statement['iban'],
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'statement_from' => $statement['from'],
                'statement_to' => $statement['to'],
                'opening_balance' => $statement['opening_balance'],
                'closing_balance' => $statement['closing_balance'],
            ]);

            $from = $accounting->period_start->toDateString();
            $to = $accounting->period_end->toDateString();
            $known = $accounting->bankTransactions()->pluck('fingerprint')->flip();
            $counts = ['rows_new' => 0, 'rows_duplicate' => 0, 'rows_outside' => 0];

            foreach ($statement['rows'] as $row) {
                if ($row['booking_date'] < $from || $row['booking_date'] > $to) {
                    $counts['rows_outside']++;
                    continue;
                }
                if ($known->has($row['fingerprint'])) {
                    $counts['rows_duplicate']++;
                    continue;
                }

                BankTransaction::create($row + [
                    'accounting_id' => $accounting->id,
                    'account_id' => $account->id,
                    'bank_import_id' => $import->id,
                    'status' => BankTransaction::OPEN,
                    'text' => Str::limit($row['text'], 250, ''),
                ]);
                $known->put($row['fingerprint'], true);
                $counts['rows_new']++;
            }

            $import->update($counts);

            return $import;
        });
    }

    /** A statement goes onto the account it belongs to, never onto another one. */
    private function checkIban(Accounting $accounting, Account $account, ?string $iban): void
    {
        if (!$iban) return;

        $owner = $accounting->accounts()->where('iban', $iban)->where('id', '!=', $account->id)->first();
        if ($owner) {
            throw new BankStatementException(
                "Diese Datei gehört zum Konto {$owner->number} {$owner->name} (IBAN {$iban}). Bitte dieses Konto wählen."
            );
        }

        if ($account->iban && $account->iban !== $iban) {
            throw new BankStatementException(
                "Konto {$account->number} {$account->name} gehört zur IBAN {$account->iban}, die Datei aber zur IBAN {$iban}."
            );
        }
    }
}
