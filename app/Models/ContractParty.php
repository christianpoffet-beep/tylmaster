<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractParty extends Model
{
    protected $fillable = [
        'contract_id', 'organization_id', 'contact_id', 'share', 'sort_order',
    ];

    protected $casts = [
        'share' => 'decimal:2',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function getNameAttribute(): string
    {
        if ($this->organization) {
            $name = $this->organization->primary_name;
            if ($this->contact) {
                $name .= ' (AP: ' . $this->contact->full_name . ')';
            }
            return $name;
        }
        if ($this->contact) {
            return $this->contact->full_name;
        }
        return '—';
    }
}
