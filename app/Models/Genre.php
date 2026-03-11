<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Genre extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'slug'];

    protected static function booted(): void
    {
        static::creating(function (Genre $genre) {
            if (empty($genre->slug)) {
                $genre->slug = Str::slug($genre->name);
            }
        });

        static::updating(function (Genre $genre) {
            if ($genre->isDirty('name')) {
                $genre->slug = Str::slug($genre->name);
            }
        });
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class);
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }
}
