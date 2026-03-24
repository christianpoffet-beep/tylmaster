<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentPost extends Model
{
    use LogsActivity;

    protected $fillable = [
        'platform', 'title', 'caption', 'hashtags', 'image',
        'image_source_type', 'image_source_id',
        'status', 'scheduled_at', 'published_at', 'notes',
        'share_token',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public const PLATFORMS = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'twitter' => 'X / Twitter',
    ];

    public const STATUSES = [
        'draft' => 'Entwurf',
        'planned' => 'Geplant',
        'published' => 'Veröffentlicht',
    ];

    public function imageSource()
    {
        if ($this->image_source_type === 'photo') {
            return $this->belongsTo(Photo::class, 'image_source_id');
        }
        if ($this->image_source_type === 'artwork') {
            return $this->belongsTo(Artwork::class, 'image_source_id');
        }
        if ($this->image_source_type === 'artwork_logo') {
            return $this->belongsTo(ArtworkLogo::class, 'image_source_id');
        }
        return null;
    }

    public function getEffectiveImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }

        if ($this->image_source_type === 'photo') {
            $photo = Photo::find($this->image_source_id);
            return $photo?->photo_url;
        }
        if ($this->image_source_type === 'artwork') {
            $artwork = Artwork::find($this->image_source_id);
            return $artwork?->artwork_url;
        }
        if ($this->image_source_type === 'artwork_logo') {
            $logo = ArtworkLogo::find($this->image_source_id);
            return $logo?->url;
        }

        return null;
    }

    public function getImageSourceLabelAttribute(): ?string
    {
        if (!$this->image_source_type) return null;

        if ($this->image_source_type === 'photo') {
            $photo = Photo::find($this->image_source_id);
            return $photo ? 'Foto: ' . $photo->display_title : null;
        }
        if ($this->image_source_type === 'artwork') {
            $artwork = Artwork::find($this->image_source_id);
            return $artwork ? 'Artwork: ' . $artwork->title : null;
        }
        if ($this->image_source_type === 'artwork_logo') {
            $logo = ArtworkLogo::with('artwork')->find($this->image_source_id);
            return $logo ? 'Logo: ' . ($logo->artwork->title ?? '') . ' – ' . $logo->original_name : null;
        }

        return null;
    }

    public function generateShareToken(): string
    {
        $this->share_token = Str::random(48);
        $this->save();
        return $this->share_token;
    }

    public function revokeShareToken(): void
    {
        $this->share_token = null;
        $this->save();
    }

    public function getShareUrlAttribute(): ?string
    {
        return $this->share_token
            ? url('/preview/content/' . $this->share_token)
            : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
            'planned' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300',
            'published' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function getPlatformLabelAttribute(): string
    {
        return self::PLATFORMS[$this->platform] ?? ucfirst($this->platform);
    }
}
