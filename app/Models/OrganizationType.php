<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrganizationType extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'sort_order'];

    protected static function booted(): void
    {
        static::creating(function (OrganizationType $type) {
            if (empty($type->slug)) {
                $type->slug = Str::slug($type->name);
            }
        });

        static::updating(function (OrganizationType $type) {
            if ($type->isDirty('name') && !$type->isDirty('slug')) {
                $type->slug = Str::slug($type->name);
            }
        });
    }

    public function organizations()
    {
        return Organization::where('type', $this->slug);
    }

    public function getOrganizationCountAttribute(): int
    {
        return Organization::where('type', $this->slug)->count();
    }
}
