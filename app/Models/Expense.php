<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use LogsActivity;

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
