<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One uploaded bank statement (CSV from the e-banking). */
class BankImport extends Model
{
    protected $fillable = [
        'accounting_id', 'account_id', 'format', 'iban', 'file_name', 'file_path',
        'statement_from', 'statement_to', 'opening_balance', 'closing_balance',
        'rows_new', 'rows_duplicate', 'rows_outside',
    ];

    protected $casts = [
        'statement_from' => 'date',
        'statement_to' => 'date',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
    ];

    public function accounting()
    {
        return $this->belongsTo(Accounting::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }
}
