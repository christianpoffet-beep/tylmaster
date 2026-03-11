<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProjectType extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'slug', 'color', 'sort_order'];

    protected static function booted(): void
    {
        static::creating(function (ProjectType $type) {
            if (empty($type->slug)) {
                $type->slug = Str::slug($type->name);
            }
        });

        static::updating(function (ProjectType $type) {
            if ($type->isDirty('name') && !$type->isDirty('slug')) {
                $type->slug = Str::slug($type->name);
            }
        });
    }

    public function getUsageCountAttribute(): int
    {
        return Project::where('type', $this->slug)->count();
    }
}
