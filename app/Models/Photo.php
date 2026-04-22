<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Photo extends Model
{
    use LogsActivity;

    protected $fillable = [
        'photo_folder_id', 'file_path', 'file_size', 'mime_type',
        'original_name', 'public_slug', 'title', 'location',
        'photo_date', 'story', 'info', 'photographer',
        'graphic_artist', 'sort_order',
    ];

    protected $casts = [
        'photo_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Photo $photo) {
            if (empty($photo->public_slug)) {
                $photo->public_slug = static::generateUniquePublicSlug();
            }
        });
    }

    public static function generateUniquePublicSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(10));
        } while (static::where('public_slug', $slug)->exists());
        return $slug;
    }

    public function folder()
    {
        return $this->belongsTo(PhotoFolder::class, 'photo_folder_id');
    }

    public function credits()
    {
        return $this->hasMany(PhotoCredit::class);
    }

    public function creditsForRole(string $role)
    {
        return $this->credits()->where('role', $role)->with('creditable')->get();
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    public function getPublicUrlAttribute(): string
    {
        return url('/p/' . $this->folder->full_slug_path . '/' . $this->public_slug);
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: $this->original_name;
    }

    public function creditsDisplay(string $role): string
    {
        $names = $this->credits
            ->where('role', $role)
            ->map(fn ($c) => $c->display_name)
            ->filter(fn ($n) => $n && $n !== '-')
            ->values();

        if ($names->isNotEmpty()) {
            return $names->join(', ');
        }

        return match ($role) {
            'photographer' => (string) ($this->photographer ?? ''),
            'graphic_artist' => (string) ($this->graphic_artist ?? ''),
            default => '',
        };
    }
}
