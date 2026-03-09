<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accounting extends Model
{
    protected $fillable = [
        'accountable_type', 'accountable_id', 'name', 'currency',
        'period_start', 'period_end', 'status', 'chart_template_id', 'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function accountable()
    {
        return $this->morphTo();
    }

    public function accounts()
    {
        return $this->hasMany(Account::class)->orderBy('number');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class)->orderBy('booking_date');
    }

    public function chartTemplate()
    {
        return $this->belongsTo(ChartTemplate::class);
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->status === 'closed';
    }

    public function getAccountableNameAttribute(): string
    {
        $entity = $this->accountable;
        if ($entity instanceof Contact) {
            return $entity->full_name;
        }
        if ($entity instanceof Organization) {
            return $entity->primary_name;
        }
        return '—';
    }

    public function applyTemplate(ChartTemplate $template): void
    {
        foreach ($template->accounts as $tplAccount) {
            $this->accounts()->create([
                'number' => $tplAccount->number,
                'name' => $tplAccount->name,
                'type' => $tplAccount->type,
                'is_header' => $tplAccount->is_header,
                'sort_order' => $tplAccount->sort_order,
            ]);
        }
    }
}
