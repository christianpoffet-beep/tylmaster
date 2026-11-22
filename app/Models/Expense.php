<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * A cost with its receipt. Booked on an expense account of an accounting:
 * paid by bank, the bank inbox books it (and recognises it by its amount);
 * paid in cash or privately (paid_from_account_id), it books itself.
 */
class Expense extends Model
{
    use LogsActivity;

    public const STATUS_LABELS = [
        'open' => 'Offen',
        'bank' => 'Bezahlt (Bank)',
        'direct' => 'Bezahlt (bar/privat)',
        'unbooked' => 'Ohne Buchhaltung',
    ];

    protected $fillable = [
        'description', 'amount', 'currency', 'expense_date', 'contact_id',
        'accounting_id', 'account_id', 'paid_from_account_id', 'booking_id',
        'created_from_bank', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'created_from_bank' => 'boolean',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function accounting()
    {
        return $this->belongsTo(Accounting::class);
    }

    /** The expense account (e.g. 4008 Webhosting). */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /** Paid in cash or privately: the account it came from (Kasse, Eigenkapital). */
    public function paidFromAccount()
    {
        return $this->belongsTo(Account::class, 'paid_from_account_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /** Waiting for its payment in the bank inbox. */
    public function scopeAwaitingBank($query)
    {
        return $query->whereNull('booking_id')->whereNull('paid_from_account_id')
            ->whereNotNull('accounting_id')->whereNotNull('account_id');
    }

    public function getStatusAttribute(): string
    {
        if ($this->booking_id) {
            return $this->paid_from_account_id ? 'direct' : 'bank';
        }
        if (!$this->accounting_id || !$this->account_id) {
            return 'unbooked';
        }

        return 'open';
    }

    protected function getActivityLogLabel(): string
    {
        return $this->description ?? "#{$this->getKey()}";
    }
}
