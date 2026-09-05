<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContentPost extends Model
{
    use LogsActivity;

    protected $fillable = [
        'platform', 'title', 'caption', 'hashtags',
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

    public function images()
    {
        return $this->hasMany(ContentPostImage::class)->orderBy('sort_order');
    }

    public function getImageUrlsAttribute(): array
    {
        return $this->images->map(fn ($img) => $img->url)->filter()->values()->all();
    }

    public function getEffectiveImageUrlAttribute(): ?string
    {
        return $this->images->first()?->url;
    }

    public function generateShareToken(): string
    {
        do {
            $token = Str::random(9);
        } while (static::where('share_token', $token)->exists());

        $this->share_token = $token;
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
            default => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
        };
    }

    public function getPlatformLabelAttribute(): string
    {
        return self::PLATFORMS[$this->platform] ?? ucfirst($this->platform);
    }

    /**
     * Caption + hashtags rendered for social-feed preview.
     * Highlights @mentions, #hashtags and URLs with platform-appropriate link colors.
     * Returns safe HTML (already escaped + wrapped).
     */
    public function renderCaptionHtml(): string
    {
        $text = trim((string) $this->caption);
        $tags = trim((string) $this->hashtags);

        $full = $text;
        if ($tags !== '') {
            $full .= ($text !== '' ? "\n\n" : '') . $tags;
        }

        $escaped = e($full);

        // URLs (http/https)
        $escaped = preg_replace_callback(
            '/(https?:\/\/[^\s<]+[^\s.,!?:;)"<])/iu',
            fn ($m) => '<a href="' . e($m[1]) . '" target="_blank" rel="noopener noreferrer" class="text-sky-400 hover:underline break-all">' . e($m[1]) . '</a>',
            $escaped
        );

        // #hashtags (Unicode-aware word chars)
        $escaped = preg_replace(
            '/(^|\s)(#[\p{L}\p{N}_]+)/u',
            '$1<span class="text-sky-400">$2</span>',
            $escaped
        );

        // @mentions
        $escaped = preg_replace(
            '/(^|\s)(@[\p{L}\p{N}_.]+)/u',
            '$1<span class="text-sky-400">$2</span>',
            $escaped
        );

        return nl2br($escaped);
    }
}
