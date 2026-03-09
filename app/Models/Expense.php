<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'description', 'amount', 'currency', 'expense_date',
        'category', 'contact_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
