<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Artwork extends Model
{
    protected $fillable = [
        'title', 'artwork_path', 'artwork_file_size',
        'artwork_mime_type', 'artwork_original_name',
        'photographer', 'artwork_by', 'logo_by', 'design_by', 'yoc',
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function logos()
    {
        return $this->hasMany(ArtworkLogo::class);
    }

    public function credits()
    {
        return $this->hasMany(ArtworkCredit::class);
    }

    public function creditsForRole(string $role)
    {
        return $this->credits()->where('role', $role)->with('creditable')->get();
    }

    public function getArtworkUrlAttribute(): ?string
    {
        return $this->artwork_path ? Storage::url($this->artwork_path) : null;
    }
}
