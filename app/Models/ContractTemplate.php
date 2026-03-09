<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContractTemplate extends Model
{
    protected $fillable = ['name', 'slug', 'contract_type_slug', 'default_terms', 'default_status', 'default_parties', 'sort_order'];

    protected $casts = [
        'default_parties' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (ContractTemplate $template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });

        static::updating(function (ContractTemplate $template) {
            if ($template->isDirty('name') && !$template->isDirty('slug')) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    public function contractType()
    {
        return ContractType::where('slug', $this->contract_type_slug)->first();
    }

    public function getTypeLabelAttribute(): string
    {
        $type = ContractType::where('slug', $this->contract_type_slug)->first();
        return $type ? $type->name : ucfirst($this->contract_type_slug);
    }

    public function getTypeColorAttribute(): string
    {
        $type = ContractType::where('slug', $this->contract_type_slug)->first();
        return $type ? $type->color : 'bg-gray-100 text-gray-600';
    }
}
