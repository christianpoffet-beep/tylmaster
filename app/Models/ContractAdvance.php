<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * An advance or a cost the label paid for the artists of a contract
 * (mastering, pressing, money in advance). It is recouped from the artists'
 * shares of this contract in the statements before anything is credited.
 */
class ContractAdvance extends Model
{
    use LogsActivity;

    protected $fillable = ['contract_id', 'date', 'description', 'amount', 'currency'];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
