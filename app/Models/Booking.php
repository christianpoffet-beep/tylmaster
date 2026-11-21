<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use LogsActivity;

    protected $fillable = [
        'accounting_id', 'booking_date', 'reference', 'description',
        'debit_account_id', 'credit_account_id', 'amount', 'notes',
        'project_id', 'contact_id', 'organization_id', 'invoice_id',
        'invoice_role', 'bank_transaction_id', 'from_bank_import',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'amount' => 'decimal:2',
        'from_bank_import' => 'boolean',
    ];

    /** The booking an invoice makes when it is issued ("Debitoren an Ertrag"). */
    public const ROLE_CLAIM = 'claim';

    /** The booking that settles it ("Bank an Debitoren"). */
    public const ROLE_PAYMENT = 'payment';

    protected static function booted(): void
    {
        // A booking that goes away hands its bank movement back to the inbox,
        // and a payment that goes away leaves its invoice unpaid again.
        static::deleted(function (Booking $booking) {
            if ($booking->bank_transaction_id) {
                $stillBooked = static::where('bank_transaction_id', $booking->bank_transaction_id)->exists();
                if (!$stillBooked) {
                    BankTransaction::whereKey($booking->bank_transaction_id)
                        ->where('status', BankTransaction::BOOKED)
                        ->update(['status' => BankTransaction::OPEN]);
                }
            }

            if ($booking->invoice_id && $booking->invoice_role === self::ROLE_PAYMENT) {
                $otherPayment = static::where('invoice_id', $booking->invoice_id)
                    ->where('invoice_role', self::ROLE_PAYMENT)->exists();
                if (!$otherPayment) {
                    Invoice::whereKey($booking->invoice_id)->where('status', 'paid')->update(['status' => 'open']);
                }
            }
        });
    }

    public function accounting()
    {
        return $this->belongsTo(Accounting::class);
    }

    public function debitAccount()
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class);
    }
}
