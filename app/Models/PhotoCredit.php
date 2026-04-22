<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PhotoCredit extends Model
{
    use LogsActivity;

    protected $fillable = [
        'photo_id', 'role', 'creditable_type', 'creditable_id', 'ipi_number',
    ];

    public function photo()
    {
        return $this->belongsTo(Photo::class);
    }

    public function creditable()
    {
        return $this->morphTo();
    }

    public function getMatchedIpiAttribute(): ?array
    {
        if ($this->creditable_type !== Contact::class || !$this->ipi_number || !$this->creditable) {
            return null;
        }
        $match = collect($this->creditable->ipis ?? [])->firstWhere('number', $this->ipi_number);
        return $match ?: null;
    }

    public function getDisplayNameAttribute(): string
    {
        if (!$this->creditable) {
            return '-';
        }

        if ($this->creditable_type === Contact::class) {
            $ipi = $this->matched_ipi;
            if ($ipi && !empty($ipi['name'])) {
                return $ipi['name'];
            }
            return $this->creditable->full_name;
        }

        if ($this->creditable_type === Organization::class) {
            return $this->creditable->primary_name;
        }

        return '-';
    }
}
