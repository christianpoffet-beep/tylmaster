<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One movement on a bank statement. It waits in the inbox ("open") until it
 * is booked - by new bookings or by linking one that was already there - or
 * ignored.
 */
class BankTransaction extends Model
{
    public const OPEN = 'open';
    public const BOOKED = 'booked';
    public const IGNORED = 'ignored';

    protected $fillable = [
        'accounting_id', 'account_id', 'bank_import_id', 'booking_date', 'value_date',
        'amount', 'balance_after', 'text', 'details', 'fingerprint', 'status',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'value_date' => 'date',
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function accounting()
    {
        return $this->belongsTo(Accounting::class);
    }

    /** The bank account in the chart of accounts (e.g. 1003 Bank). */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function bankImport()
    {
        return $this->belongsTo(BankImport::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Bookings were removed in bulk (no model events): movements left without
     * any booking go back to the inbox instead of claiming to be booked.
     */
    public static function reopenUnbooked(iterable $ids): void
    {
        $ids = collect($ids)->filter()->unique()->values();
        if ($ids->isEmpty()) return;

        static::whereIn('id', $ids)->where('status', self::BOOKED)
            ->whereDoesntHave('bookings')
            ->update(['status' => self::OPEN]);
    }

    public function getIsIncomingAttribute(): bool
    {
        return (float) $this->amount > 0;
    }

    public function getAbsAmountAttribute(): float
    {
        return abs((float) $this->amount);
    }

    /** Text and details in one string, for matching rules and invoice numbers. */
    public function getSearchTextAttribute(): string
    {
        return trim($this->text . ' ' . $this->details);
    }

    /**
     * Who paid or got paid, as a starting point for a rule:
     * "Gutschrift REBEAT Digital GmbH EUR 54.46, Umrechnungskurs 0.9292" -> "REBEAT Digital GmbH".
     */
    public function getCounterpartyAttribute(): string
    {
        $text = preg_replace('/^(Gutschrift|Zahlung|Belastung|Lastschrift|Dauerauftrag|Einkauf|Bezug|Überweisung|Vergütung|eBill|TWINT)\s+/iu', '', trim($this->text));
        $text = preg_split('/,|\s(?:EUR|CHF|USD|GBP)\s|\s\d{1,2}\.\d{1,2}\.\d{2,4}/u', $text)[0];

        return trim($text) !== '' ? trim($text) : trim($this->text);
    }
}
