<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use LogsActivity;

    protected $fillable = [
        'accounting_id', 'number', 'name', 'type',
        'is_header', 'opening_balance', 'sort_order',
    ];

    protected $casts = [
        'is_header' => 'boolean',
        'opening_balance' => 'decimal:2',
    ];

    public function accounting()
    {
        return $this->belongsTo(Accounting::class);
    }

    public function debitBookings()
    {
        return $this->hasMany(Booking::class, 'debit_account_id');
    }

    public function creditBookings()
    {
        return $this->hasMany(Booking::class, 'credit_account_id');
    }

    public function getBalanceAttribute(): float
    {
        $debits = $this->debitBookings()->sum('amount');
        $credits = $this->creditBookings()->sum('amount');

        // Assets & Expenses: increase with debit
        // Liabilities & Income: increase with credit
        if (in_array($this->type, ['asset', 'expense'])) {
            return (float) $this->opening_balance + $debits - $credits;
        }

        return (float) $this->opening_balance + $credits - $debits;
    }

    public function getDebitTotalAttribute(): float
    {
        return (float) $this->debitBookings()->sum('amount');
    }

    public function getCreditTotalAttribute(): float
    {
        return (float) $this->creditBookings()->sum('amount');
    }

    public function getHasBookingsAttribute(): bool
    {
        return $this->debitBookings()->exists() || $this->creditBookings()->exists();
    }
}
