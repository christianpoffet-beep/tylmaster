<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'accounting_id', 'booking_date', 'reference', 'description',
        'debit_account_id', 'credit_account_id', 'amount', 'notes',
        'project_id', 'contact_id', 'organization_id', 'invoice_id',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'amount' => 'decimal:2',
    ];

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
}
