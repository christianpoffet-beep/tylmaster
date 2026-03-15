<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ContentPost extends Model
{
    use LogsActivity;

    protected $fillable = [
        'platform', 'title', 'caption', 'hashtags', 'image',
        'status', 'scheduled_at', 'published_at', 'notes',
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
