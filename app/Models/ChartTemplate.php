<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChartTemplate extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'organization_type_slug',
    ];

    protected static function booted(): void
    {
        static::creating(function (ChartTemplate $tpl) {
            if (empty($tpl->slug)) {
                $tpl->slug = static::generateUniqueSlug($tpl->name);
            }
        });

        static::updating(function (ChartTemplate $tpl) {
            if ($tpl->isDirty('name')) {
                $tpl->slug = static::generateUniqueSlug($tpl->name, $tpl->id);
            }
        });
    }

    public static function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        if (!$slug) $slug = 'vorlage';
        $original = $slug;
        $counter = 1;
        while (static::where('slug', $slug)->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = $original . '-' . $counter++;
        }
        return $slug;
    }

    public function accounts()
    {
        return $this->hasMany(ChartTemplateAccount::class)->orderBy('number');
    }

    public function getUsageCountAttribute(): int
    {
        return Accounting::where('chart_template_id', $this->id)->count();
    }
}
