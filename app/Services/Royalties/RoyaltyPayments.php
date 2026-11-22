<?php

namespace App\Services\Royalties;

use App\Models\BankTransaction;
use App\Models\RoyaltyPayment;
use App\Services\Banking\BankStatementReader;
use Illuminate\Support\Carbon;

/**
 * The payouts of Rebeat, as they reached the bank: "Gutschrift REBEAT
 * Digital GmbH EUR 54.46, Umrechnungskurs 0.9292" over CHF 50.60.
 *
 * Collected from the bank imports of the accountings, from bank statements
 * uploaded here (years without an accounting in the tool), or entered by
 * hand. The same payout from two sources counts once: it is identified by
 * its date and amounts.
 */
class RoyaltyPayments
{
    public const SOURCE = 'rebeat';

    private const PATTERN = '/rebeat/i';

    /** Payouts found in the bank imports of all accountings; returns how many were new */
    public function collectFromBank(): int
    {
        $new = 0;
        BankTransaction::where('text', 'like', '%rebeat%')->where('amount', '>', 0)->orderBy('booking_date')
            ->each(function (BankTransaction $transaction) use (&$new) {
                $new += (int) $this->store($transaction->booking_date, (float) $transaction->amount,
                    $transaction->text . ' ' . $transaction->details, 'bank', $transaction->id);
            });

        return $new;
    }

    /**
     * Payouts in an e-banking export (same format as the bank import); the
     * other movements of the file are ignored.
     *
     * @return array{new: int, found: int}
     */
    public function collectFromFile(string $path): array
    {
        $statement = BankStatementReader::read($path);
        $found = 0;
        $new = 0;
        foreach ($statement['rows'] as $row) {
            $text = trim($row['text'] . ' ' . ($row['details'] ?? ''));
            if ($row['amount'] <= 0 || !preg_match(self::PATTERN, $text)) continue;
            $found++;
            $new += (int) $this->store(Carbon::parse($row['booking_date']), (float) $row['amount'], $text, 'file');
        }

        return ['new' => $new, 'found' => $found];
    }

    public function addManually(\DateTimeInterface $date, float $amount, ?float $amountForeign, ?string $text): ?RoyaltyPayment
    {
        $created = $this->store(Carbon::instance($date), $amount, (string) $text, 'manual', null, $amountForeign);

        return $created ? RoyaltyPayment::latest('id')->first() : null;
    }

    /** "EUR 54.46, Umrechnungskurs 0.9292" */
    public static function parse(string $text): array
    {
        $eur = preg_match('/EUR\s*([\d\'’.,]+)/i', $text, $m) ? self::number($m[1]) : null;
        $rate = preg_match('/Umrechnungskurs\s*([\d.,]+)/i', $text, $m) ? self::number($m[1]) : null;

        return ['amount_foreign' => $eur, 'rate' => $rate];
    }

    /** A payout is the same when date, CHF and EUR are - wherever it came from */
    public static function fingerprint(\DateTimeInterface $date, float $amount, ?float $amountForeign): string
    {
        return hash('sha256', implode('|', [self::SOURCE, $date->format('Y-m-d'), number_format($amount, 2, '.', ''),
            $amountForeign !== null ? number_format($amountForeign, 2, '.', '') : '']));
    }

    private function store(\DateTimeInterface $date, float $amount, string $text, string $origin, ?int $bankTransactionId = null, ?float $amountForeign = null): bool
    {
        $parsed = self::parse($text);
        $amountForeign ??= $parsed['amount_foreign'];
        $fingerprint = self::fingerprint($date, $amount, $amountForeign);

        $existing = RoyaltyPayment::where('fingerprint', $fingerprint)->first();
        if ($existing) {
            // Seen in a file first, now in the bank import: link the transaction
            if ($bankTransactionId && !$existing->bank_transaction_id) {
                $existing->update(['bank_transaction_id' => $bankTransactionId]);
            }
            return false;
        }

        RoyaltyPayment::create([
            'source' => self::SOURCE,
            'paid_at' => $date->format('Y-m-d'),
            'amount' => round($amount, 2),
            'amount_foreign' => $amountForeign !== null ? round($amountForeign, 2) : null,
            'currency_foreign' => $amountForeign !== null ? 'EUR' : null,
            'rate' => $parsed['rate'] ?? ($amountForeign ? round($amount / $amountForeign, 6) : null),
            'text' => mb_substr(trim(preg_replace('/\s+/', ' ', $text)), 0, 255) ?: null,
            'origin' => $origin,
            'bank_transaction_id' => $bankTransactionId,
            'fingerprint' => $fingerprint,
        ]);

        return true;
    }

    private static function number(string $value): float
    {
        $value = str_replace(["'", '’', ' '], '', $value);
        if (str_contains($value, ',') && !str_contains($value, '.')) {
            $value = str_replace(',', '.', $value);
        }

        return (float) str_replace(',', '', $value);
    }
}
