<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductTemplate extends Model
{
    protected $fillable = [
        'name', 'slug', 'language', 'default_product_types',
        'default_release_info', 'sort_order',
    ];

    protected $casts = [
        'default_product_types' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductTemplate $t) {
            if (empty($t->slug)) {
                $t->slug = Str::slug($t->name);
            }
        });

        static::updating(function (ProductTemplate $t) {
            if ($t->isDirty('name') && !$t->isDirty('slug')) {
                $t->slug = Str::slug($t->name);
            }
        });
    }
}
