<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContactType extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'slug', 'color', 'sort_order'];

    protected static function booted(): void
    {
        static::creating(function (ContactType $type) {
            if (empty($type->slug)) {
                $type->slug = Str::slug($type->name);
            }
        });

        static::updating(function (ContactType $type) {
            if ($type->isDirty('name') && !$type->isDirty('slug')) {
                $type->slug = Str::slug($type->name);
            }
        });
    }

    public function getUsageCountAttribute(): int
    {
        return Contact::whereJsonContains('types', $this->slug)->count();
    }
}
