<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PhotoFolder extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name', 'slug', 'parent_id', 'share_token', 'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (PhotoFolder $folder) {
            if (empty($folder->slug)) {
                $folder->slug = static::generateUniqueSlug($folder->name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        if (!$slug) $slug = 'folder';
        $original = $slug;
        $counter = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }
        return $slug;
    }

    public function parent()
    {
        return $this->belongsTo(PhotoFolder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PhotoFolder::class, 'parent_id');
    }

    public function photos()
    {
        return $this->hasMany(Photo::class)->orderBy('sort_order');
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_photo_folder');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_photo_folder');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'photo_folder_project');
    }

    public function generateShareToken(): string
    {
        $this->share_token = Str::random(64);
        $this->save();
        return $this->share_token;
    }

    public function revokeShareToken(): void
    {
        $this->share_token = null;
        $this->save();
    }

    public function getFullSlugPathAttribute(): string
    {
        $segments = collect();
        $current = $this;
        while ($current) {
            $segments->prepend($current->slug);
            $current = $current->parent;
        }
        return $segments->implode('/');
    }

    public function getBreadcrumbsAttribute(): array
    {
        $crumbs = [];
        $current = $this;
        while ($current) {
            array_unshift($crumbs, $current);
            $current = $current->parent;
        }
        return $crumbs;
    }

    public function getShareUrlAttribute(): ?string
    {
        return $this->share_token ? url('/gallery/' . $this->share_token) : null;
    }
}
