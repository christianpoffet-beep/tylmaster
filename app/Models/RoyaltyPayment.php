<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A payout of the distributor (Rebeat), in CHF as it reached the bank, with the EUR it was. */
class RoyaltyPayment extends Model
{
    public const ORIGINS = ['bank' => 'Bankimport', 'file' => 'Kontoauszug', 'manual' => 'von Hand'];

    protected $fillable = [
        'source', 'paid_at', 'amount', 'amount_foreign', 'currency_foreign', 'rate', 'text',
        'origin', 'bank_transaction_id', 'fingerprint', 'royalty_statement_id',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount' => 'decimal:2',
        'amount_foreign' => 'decimal:2',
        'rate' => 'decimal:6',
    ];

    public function statement()
    {
        return $this->belongsTo(RoyaltyStatement::class, 'royalty_statement_id');
    }

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class);
    }
}
