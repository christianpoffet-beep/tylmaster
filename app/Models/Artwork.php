<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Artwork extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title', 'artwork_path', 'artwork_file_size',
        'artwork_mime_type', 'artwork_original_name',
        'photographer', 'artwork_by', 'logo_by', 'design_by', 'yoc',
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function releases()
    {
        return $this->belongsToMany(Release::class, 'artwork_release')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function images()
    {
        return $this->hasMany(ArtworkImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function logos()
    {
        return $this->hasMany(ArtworkLogo::class)->orderBy('sort_order')->orderBy('id');
    }

    public function credits()
    {
        return $this->hasMany(ArtworkCredit::class);
    }

    public function creditsForRole(string $role)
    {
        return $this->credits()->where('role', $role)->with('creditable')->get();
    }

    public function getPrimaryImageAttribute(): ?ArtworkImage
    {
        if (!$this->relationLoaded('images')) {
            $this->load('images');
        }
        return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
    }

    public function getPrimaryLogoAttribute(): ?ArtworkLogo
    {
        if (!$this->relationLoaded('logos')) {
            $this->load('logos');
        }
        return $this->logos->firstWhere('is_primary', true) ?? $this->logos->first();
    }

    public function getArtworkUrlAttribute(): ?string
    {
        $primary = $this->primary_image;
        if ($primary && $primary->file_path) {
            return Storage::disk('public')->url($primary->file_path);
        }
        // Legacy fallback (pre-artwork_images migration)
        return $this->attributes['artwork_path']
            ? Storage::disk('public')->url($this->attributes['artwork_path'])
            : null;
    }

    public function hasAnyImage(): bool
    {
        return $this->images->isNotEmpty() || !empty($this->attributes['artwork_path']);
    }
}
