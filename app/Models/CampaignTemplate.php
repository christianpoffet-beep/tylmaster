<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CampaignTemplate extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name', 'slug', 'type', 'language', 'subject', 'body', 'notes', 'sort_order',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function getUsageCountAttribute(): int
    {
        return $this->campaigns()->count();
    }
}
