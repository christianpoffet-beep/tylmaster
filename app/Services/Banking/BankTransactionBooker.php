<?php

namespace App\Services\Banking;

use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\Booking;
use App\Models\BookingRule;
use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Books a movement out of the bank inbox - and takes it back again.
 *
 * Money in:  bank (Soll) an counter account (Haben).
 * Money out: counter account (Soll) an bank (Haben).
 */
class BankTransactionBooker
{
    /**
     * @param array{match?: ?string, account_id?: mixed, description?: ?string, splits?: array, remember?: mixed, pattern?: ?string} $input
     *   match: "booking:ID" links a booking that is already there, "invoice:ID" books the payment of an invoice,
     *   "expense:ID" the payment of a recorded expense; otherwise account_id - or splits
     *   [[account_id, amount], ...] - is the counter account.
     */
    public function book(BankTransaction $transaction, array $input): void
    {
        $this->guard($transaction, BankTransaction::OPEN);

        DB::transaction(function () use ($transaction, $input) {
            [$kind, $id] = array_pad(explode(':', (string) ($input['match'] ?? ''), 2), 2, null);

            match ($kind) {
                'booking' => $this->linkBooking($transaction, (int) $id),
                'invoice' => $this->bookInvoicePayment($transaction, (int) $id, $input),
                'expense' => $this->bookExpense($transaction, (int) $id, $input),
                default => $this->bookOnAccounts($transaction, $input),
            };

            $transaction->update(['status' => BankTransaction::BOOKED]);
        });
    }

    public function ignore(BankTransaction $transaction): void
    {
        $this->guard($transaction, BankTransaction::OPEN);
        $transaction->update(['status' => BankTransaction::IGNORED]);
    }

    /** Back into the inbox: bookings the import made go, bookings it only linked stay (unlinked). */
    public function reopen(BankTransaction $transaction): void
    {
        $this->guard($transaction, null);

        DB::transaction(function () use ($transaction) {
            foreach ($transaction->bookings()->get() as $booking) {
                if ($booking->from_bank_import) {
                    $booking->delete();
                    continue;
                }

                $booking->update(['bank_transaction_id' => null]);
                // Linking marked its invoice paid; without any payment it is open again
                if ($booking->invoice_id && $booking->invoice_role === Booking::ROLE_CLAIM
                    && !Booking::where('invoice_id', $booking->invoice_id)->where('invoice_role', Booking::ROLE_PAYMENT)->exists()) {
                    Invoice::whereKey($booking->invoice_id)->where('status', 'paid')->update(['status' => 'open']);
                }
            }

            $transaction->update(['status' => BankTransaction::OPEN]);
        });
    }

    private function linkBooking(BankTransaction $transaction, int $bookingId): void
    {
        $booking = Booking::where('accounting_id', $transaction->accounting_id)->whereNull('bank_transaction_id')->find($bookingId);
        $side = $transaction->is_incoming ? 'debit_account_id' : 'credit_account_id';

        if (!$booking || (int) $booking->{$side} !== (int) $transaction->account_id
            || abs((float) $booking->amount - $transaction->abs_amount) >= 0.005) {
            throw new BankStatementException('Die vorgeschlagene Buchung passt nicht mehr zu dieser Bewegung.');
        }

        $booking->update(['bank_transaction_id' => $transaction->id]);

        if ($booking->invoice && $booking->invoice->status !== 'paid') {
            $booking->invoice->update(['status' => 'paid']);
        }
    }

    private function bookInvoicePayment(BankTransaction $transaction, int $invoiceId, array $input): void
    {
        $invoice = Invoice::whereIn('accounting_id', $transaction->accounting->openItemAccountingIds())
            ->where('status', '!=', 'paid')->find($invoiceId);
        if (!$invoice || abs((float) $invoice->amount - $transaction->abs_amount) >= 0.005) {
            throw new BankStatementException('Die vorgeschlagene Rechnung ist schon bezahlt oder hat einen anderen Betrag.');
        }

        $counter = (new BankTransactionMatcher($transaction->accounting))->invoiceCounterAccount($invoice, $transaction);
        if (!$counter) {
            throw new BankStatementException("Für Rechnung {$invoice->invoice_number} fehlt das Gegenkonto (Debitoren/Kreditoren).");
        }

        $this->createBooking($transaction, $counter, $transaction->abs_amount, [
            'description' => trim((string) ($input['description'] ?? '')) ?: BankTransactionMatcher::invoicePaymentText($invoice),
            'reference' => $invoice->invoice_number,
            'invoice_id' => $invoice->id,
            'invoice_role' => Booking::ROLE_PAYMENT,
            'project_id' => $invoice->project_id,
            'contact_id' => $invoice->contact_id,
            'organization_id' => $invoice->organization_id,
        ]);

        $invoice->update(['status' => 'paid']);
    }

    /**
     * A recorded expense is paid: "expense account an bank", and the expense
     * knows its booking. One left over from last year is booked in the year
     * it is paid and moves there, onto the same expense account.
     */
    private function bookExpense(BankTransaction $transaction, int $expenseId, array $input): void
    {
        $accounting = $transaction->accounting;
        $expense = Expense::awaitingBank()->whereIn('accounting_id', $accounting->openItemAccountingIds())->find($expenseId);
        if (!$expense || $transaction->is_incoming) {
            throw new BankStatementException('Die Ausgabe ist schon bezahlt oder gehört nicht zu dieser Bewegung.');
        }

        // In a foreign currency the bank's amount differs anyway; in the same one it must match
        if ($expense->currency === $transaction->accounting->currency
            && abs((float) $expense->amount - $transaction->abs_amount) >= 0.005) {
            throw new BankStatementException(sprintf(
                'Die Ausgabe lautet auf %s, die Bewegung auf %s.',
                number_format((float) $expense->amount, 2, '.', "'"),
                number_format($transaction->abs_amount, 2, '.', "'")
            ));
        }

        $account = $accounting->sameAccount($expense->account);
        if (!$account || (int) $account->id === (int) $transaction->account_id) {
            throw new BankStatementException($expense->account && (int) $expense->accounting_id !== (int) $accounting->id
                ? "Konto {$expense->account->number} der Ausgabe fehlt im Kontenplan von «{$accounting->name}»."
                : 'Die Ausgabe hat kein gültiges Aufwandkonto.');
        }

        $booking = $this->createBooking($transaction, $account->id, $transaction->abs_amount, [
            'description' => trim((string) ($input['description'] ?? '')) ?: $expense->description,
            'contact_id' => $expense->contact_id,
        ]);
        $expense->update(['booking_id' => $booking->id, 'accounting_id' => $accounting->id, 'account_id' => $account->id]);
    }

    private function bookOnAccounts(BankTransaction $transaction, array $input): void
    {
        $lines = collect($input['splits'] ?? [])
            ->filter(fn ($line) => !empty($line['account_id']) || (float) ($line['amount'] ?? 0) != 0.0)
            ->map(fn ($line) => ['account_id' => (int) ($line['account_id'] ?? 0), 'amount' => round((float) ($line['amount'] ?? 0), 2)])
            ->values();

        $split = $lines->count() > 0;
        if (!$split) {
            $lines = collect([['account_id' => (int) ($input['account_id'] ?? 0), 'amount' => $transaction->abs_amount]]);
        }

        $accounts = Account::where('accounting_id', $transaction->accounting_id)->where('is_header', false)
            ->whereIn('id', $lines->pluck('account_id'))->get()->keyBy('id');

        foreach ($lines as $line) {
            if (!$accounts->has($line['account_id'])) {
                throw new BankStatementException('Bitte ein Gegenkonto wählen.');
            }
            if ((int) $line['account_id'] === (int) $transaction->account_id) {
                throw new BankStatementException('Das Gegenkonto kann nicht das Bankkonto selbst sein.');
            }
            if ($line['amount'] <= 0) {
                throw new BankStatementException('Beim Aufteilen braucht jede Zeile einen positiven Betrag.');
            }
        }
        if (abs($lines->sum('amount') - $transaction->abs_amount) >= 0.005) {
            throw new BankStatementException(sprintf(
                'Die Aufteilung ergibt %s, die Bewegung ist aber %s.',
                number_format($lines->sum('amount'), 2, '.', "'"),
                number_format($transaction->abs_amount, 2, '.', "'")
            ));
        }

        $description = trim((string) ($input['description'] ?? '')) ?: $transaction->text;
        foreach ($lines as $line) {
            $booking = $this->createBooking($transaction, $line['account_id'], $line['amount'], ['description' => $description]);

            // A payment onto an expense account is an expense: it shows up under
            // "Ausgaben", where its receipt can be added later
            if (!$transaction->is_incoming && $accounts->get($line['account_id'])->type === 'expense') {
                Expense::create([
                    'description' => $booking->description,
                    'amount' => $line['amount'],
                    'currency' => $transaction->accounting->currency,
                    'expense_date' => $transaction->booking_date->toDateString(),
                    'accounting_id' => $transaction->accounting_id,
                    'account_id' => $line['account_id'],
                    'booking_id' => $booking->id,
                    'created_from_bank' => true,
                    'notes' => $transaction->details,
                ]);
            }
        }

        $pattern = trim((string) ($input['pattern'] ?? ''));
        if (!$split && !empty($input['remember']) && $pattern !== '') {
            $this->rememberRule($transaction, $pattern, $accounts->get($lines[0]['account_id']), $description);
        }
    }

    private function createBooking(BankTransaction $transaction, int $counterId, float $amount, array $extra): Booking
    {
        $extra['description'] = Str::limit($extra['description'] ?? $transaction->text, 250, '');

        return Booking::create($extra + [
            'accounting_id' => $transaction->accounting_id,
            'booking_date' => $transaction->booking_date->toDateString(),
            'debit_account_id' => $transaction->is_incoming ? $transaction->account_id : $counterId,
            'credit_account_id' => $transaction->is_incoming ? $counterId : $transaction->account_id,
            'amount' => $amount,
            'notes' => $transaction->details,
            'bank_transaction_id' => $transaction->id,
            'from_bank_import' => true,
        ]);
    }

    /** Same pattern and direction again: the rule now points at the newly chosen account. */
    private function rememberRule(BankTransaction $transaction, string $pattern, Account $account, string $description): void
    {
        $accounting = $transaction->accounting;
        $direction = $transaction->is_incoming ? 'in' : 'out';

        $rule = BookingRule::forOwnerOf($accounting)->where('pattern', $pattern)->where('direction', $direction)->first()
            ?? new BookingRule([
                'accountable_type' => $accounting->accountable_type,
                'accountable_id' => $accounting->accountable_id,
                'pattern' => Str::limit($pattern, 250, ''),
                'direction' => $direction,
            ]);

        $rule->account_number = $account->number;
        // A text of its own is worth keeping; the raw bank text is not
        $rule->description = $description !== $transaction->text ? Str::limit($description, 250, '') : null;
        $rule->save();
    }

    private function guard(BankTransaction $transaction, ?string $status): void
    {
        if ($transaction->accounting->is_closed) {
            throw new BankStatementException('Die Buchhaltung ist abgeschlossen.');
        }
        if ($status && $transaction->status !== $status) {
            throw new BankStatementException('Diese Bewegung ist schon erledigt.');
        }
    }
}
