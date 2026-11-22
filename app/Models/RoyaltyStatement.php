<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A statement to artists and labels over a period of usage (usually made on
 * 15 December). A draft is computed live from the usage, the contracts and
 * the payments of the period; finalizing books it and credits the balances,
 * undoing takes all of that back.
 */
class RoyaltyStatement extends Model
{
    public const DRAFT = 'draft';
    public const FINAL = 'final';

    protected $fillable = [
        'source', 'title', 'statement_date', 'period_from', 'period_to', 'accounting_id', 'status', 'pre_booked',
        'accounts', 'payments_amount', 'usage_amount', 'factor', 'booking_ids', 'finalized_at', 'notes',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'pre_booked' => 'decimal:2',
        'accounts' => 'array',
        'payments_amount' => 'decimal:2',
        'usage_amount' => 'float',
        'factor' => 'float',
        'booking_ids' => 'array',
        'finalized_at' => 'datetime',
    ];

    public function accounting()
    {
        return $this->belongsTo(Accounting::class);
    }

    public function items()
    {
        return $this->hasMany(RoyaltyStatementItem::class);
    }

    public function payments()
    {
        return $this->hasMany(RoyaltyPayment::class);
    }

    public function lines()
    {
        return $this->hasMany(RoyaltyLine::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(RoyaltyLedgerEntry::class);
    }

    public function mails()
    {
        return $this->hasMany(RoyaltyStatementMail::class);
    }

    public function getIsFinalAttribute(): bool
    {
        return $this->status === self::FINAL;
    }
}
