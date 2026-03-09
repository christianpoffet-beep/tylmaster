<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtworkCredit extends Model
{
    protected $fillable = [
        'artwork_id', 'role', 'creditable_type', 'creditable_id',
    ];

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }

    public function creditable()
    {
        return $this->morphTo();
    }

    public function getDisplayNameAttribute(): string
    {
        if (!$this->creditable) {
            return '-';
        }

        return match ($this->creditable_type) {
            Contact::class => $this->creditable->full_name,
            Organization::class => $this->creditable->primary_name,
            default => '-',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->creditable_type) {
            Contact::class => 'Kontakt',
            Organization::class => 'Organisation',
            default => '-',
        };
    }
}
