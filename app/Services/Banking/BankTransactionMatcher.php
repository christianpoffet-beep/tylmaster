<?php

namespace App\Services\Banking;

use App\Models\Accounting;
use App\Models\BankTransaction;
use App\Models\Booking;
use App\Models\BookingRule;
use App\Models\Invoice;
use Illuminate\Support\Collection;

/**
 * Suggests how a bank movement is booked, in this order:
 *
 * 1. A booking that is already there: same amount on the bank side, close in
 *    time (an invoice booked straight onto the bank waits up to 120 days).
 *    Linking it - instead of booking again - is what keeps a movement from
 *    landing in the books twice.
 * 2. An open invoice of this accounting with the same amount; the invoice
 *    number in the payment text decides between several.
 * 3. A rule of the owner ("text contains X -> account Y").
 *
 * One matcher serves a whole page: a booking or invoice suggested for one
 * movement is not offered for the next one as well.
 */
class BankTransactionMatcher
{
    private Collection $accountsByNumber;
    private Collection $rules;
    private Collection $invoices;
    private Collection $bookings;
    private array $taken = ['booking' => [], 'invoice' => []];

    public function __construct(private Accounting $accounting)
    {
        $this->accountsByNumber = $accounting->accounts()->where('is_header', false)->get()->keyBy('number');
        // Longest pattern first: "METANET AG Hosting" beats "METANET"
        $this->rules = $accounting->bookingRules()->get()
            ->sortByDesc(fn (BookingRule $rule) => mb_strlen($rule->pattern))->values();
        $this->invoices = Invoice::where('accounting_id', $accounting->id)
            ->where('status', '!=', 'paid')->orderBy('invoice_date')->get();
        $this->bookings = $accounting->bookings()->whereNull('bank_transaction_id')
            ->with('invoice')->get();
    }

    /**
     * @return array{type: string, account_id: ?int, invoice_id: ?int, booking_id: ?int, description: string, label: string}|null
     */
    public function suggest(BankTransaction $transaction): ?array
    {
        return $this->existingBooking($transaction)
            ?? $this->openInvoice($transaction)
            ?? $this->rule($transaction);
    }

    private function existingBooking(BankTransaction $transaction): ?array
    {
        $side = $transaction->is_incoming ? 'debit_account_id' : 'credit_account_id';

        $best = $this->bookings
            ->filter(fn (Booking $b) => (int) $b->{$side} === (int) $transaction->account_id
                && abs((float) $b->amount - $transaction->abs_amount) < 0.005
                && !isset($this->taken['booking'][$b->id]))
            ->map(function (Booking $b) use ($transaction) {
                $days = (int) round($b->booking_date->diffInDays($transaction->booking_date, false));
                $mentioned = $b->invoice && self::mentions($transaction, $b->invoice->invoice_number);
                $close = abs($days) <= 10;
                // An invoice booked straight onto the bank is paid some time after its date
                $awaitedPayment = $b->invoice && $b->invoice->status !== 'paid' && $days >= -10 && $days <= 120;

                return ($close || $awaitedPayment) ? ['booking' => $b, 'rank' => [$mentioned ? 0 : 1, abs($days)]] : null;
            })
            ->filter()
            ->sortBy('rank')
            ->first();

        if (!$best) return null;

        $booking = $best['booking'];
        $this->taken['booking'][$booking->id] = true;

        return [
            'type' => 'booking',
            'account_id' => null,
            'invoice_id' => $booking->invoice_id,
            'booking_id' => $booking->id,
            'description' => $booking->description,
            'label' => 'Schon gebucht: «' . $booking->description . '» vom ' . $booking->booking_date->format('d.m.Y'),
        ];
    }

    private function openInvoice(BankTransaction $transaction): ?array
    {
        $type = $transaction->is_incoming ? 'outgoing' : 'incoming';

        $candidates = $this->invoices->filter(fn (Invoice $i) => $i->type === $type
            && abs((float) $i->amount - $transaction->abs_amount) < 0.005
            && !isset($this->taken['invoice'][$i->id]));

        $invoice = $candidates->first(fn (Invoice $i) => self::mentions($transaction, $i->invoice_number))
            ?? $candidates->first();
        if (!$invoice) return null;

        $counter = $this->invoiceCounterAccount($invoice, $transaction);
        if (!$counter) return null;

        $this->taken['invoice'][$invoice->id] = true;

        return [
            'type' => 'invoice',
            'account_id' => $counter,
            'invoice_id' => $invoice->id,
            'booking_id' => null,
            'description' => self::invoicePaymentText($invoice),
            'label' => "Rechnung {$invoice->invoice_number} über " . number_format((float) $invoice->amount, 2, '.', "'"),
        ];
    }

    private function rule(BankTransaction $transaction): ?array
    {
        $rule = $this->rules->first(fn (BookingRule $r) => $r->matches($transaction));
        if (!$rule) return null;

        $account = $this->accountsByNumber->get($rule->account_number);

        return [
            'type' => 'rule',
            'account_id' => $account?->id,
            'invoice_id' => null,
            'booking_id' => null,
            'description' => $rule->description ?: $transaction->text,
            'label' => $account
                ? "Regel «{$rule->pattern}»"
                : "Regel «{$rule->pattern}»: Konto {$rule->account_number} fehlt in diesem Kontenplan",
        ];
    }

    /** What the booker needs to follow a suggestion; null when it does not say where to book. */
    public static function bookingInput(?array $suggestion): ?array
    {
        if (!$suggestion) return null;

        $match = match ($suggestion['type']) {
            'booking' => 'booking:' . $suggestion['booking_id'],
            'invoice' => 'invoice:' . $suggestion['invoice_id'],
            default => '',
        };
        if ($match === '' && !$suggestion['account_id']) return null;

        return ['match' => $match, 'account_id' => $suggestion['account_id'], 'description' => $suggestion['description']];
    }

    /**
     * The account the payment clears: the receivable of an outgoing invoice,
     * the payable of an incoming one (1100 / 2001 when the invoice names none).
     */
    public function invoiceCounterAccount(Invoice $invoice, BankTransaction $transaction): ?int
    {
        $id = $invoice->type === 'outgoing' ? $invoice->debit_account_id : $invoice->credit_account_id;
        if (!$id) {
            $id = $this->accountsByNumber->get($invoice->type === 'outgoing' ? '1100' : '2001')?->id;
        }

        // Booked straight onto the bank already: nothing left to clear
        return $id && (int) $id !== (int) $transaction->account_id ? (int) $id : null;
    }

    public static function invoicePaymentText(Invoice $invoice): string
    {
        return "Rechnung {$invoice->invoice_number}" . ($invoice->type === 'outgoing' ? ' (Eingang)' : ' (Zahlung)');
    }

    /** Is the invoice number in the payment text? Spaces and dashes do not count ("RE 2026 0003"). */
    public static function mentions(BankTransaction $transaction, ?string $number): bool
    {
        $normalize = fn (?string $s) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $s));
        $needle = $normalize($number);

        return strlen($needle) >= 3 && str_contains($normalize($transaction->search_text), $needle);
    }
}
