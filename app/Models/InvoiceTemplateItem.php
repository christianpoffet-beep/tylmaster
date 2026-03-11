<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class InvoiceTemplateItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'invoice_template_id', 'description', 'quantity', 'unit_price', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
    ];

    public function template()
    {
        return $this->belongsTo(InvoiceTemplate::class, 'invoice_template_id');
    }

    public function getTotalAttribute(): float
    {
        return round($this->quantity * $this->unit_price, 2);
    }
}
