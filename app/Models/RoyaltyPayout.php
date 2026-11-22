<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One payout run: the transfers of one order to the bank (pain.001). */
class RoyaltyPayout extends Model
{
    protected $fillable = [
        'accounting_id', 'execution_date', 'bank_account_id', 'payable_account_id',
        'debtor_name', 'debtor_iban', 'message', 'total',
    ];

    protected $casts = [
        'execution_date' => 'date',
        'total' => 'decimal:2',
    ];

    public function accounting()
    {
        return $this->belongsTo(Accounting::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function payableAccount()
    {
        return $this->belongsTo(Account::class, 'payable_account_id');
    }

    public function items()
    {
        return $this->hasMany(RoyaltyPayoutItem::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(RoyaltyLedgerEntry::class);
    }
}
