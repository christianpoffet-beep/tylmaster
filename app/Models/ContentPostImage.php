<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ContentPostImage extends Model
{
    protected $fillable = [
        'content_post_id', 'source_type', 'source_id', 'file_path', 'sort_order',
    ];

    public function contentPost()
    {
        return $this->belongsTo(ContentPost::class);
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->source_type === 'upload' && $this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        if ($this->source_type === 'photo') {
            return Photo::find($this->source_id)?->photo_url;
        }
        if ($this->source_type === 'artwork') {
            return Artwork::find($this->source_id)?->artwork_url;
        }
        if ($this->source_type === 'artwork_logo') {
            return ArtworkLogo::find($this->source_id)?->url;
        }
        return null;
    }

    public function getLabelAttribute(): ?string
    {
        if ($this->source_type === 'upload') {
            return 'Upload: ' . basename($this->file_path ?? '');
        }
        if ($this->source_type === 'photo') {
            return 'Foto: ' . (Photo::find($this->source_id)?->display_title ?? '?');
        }
        if ($this->source_type === 'artwork') {
            return 'Artwork: ' . (Artwork::find($this->source_id)?->title ?? '?');
        }
        if ($this->source_type === 'artwork_logo') {
            $logo = ArtworkLogo::with('artwork')->find($this->source_id);
            return 'Logo: ' . ($logo?->artwork?->title ?? '') . ' – ' . ($logo?->original_name ?? '?');
        }
        return null;
    }
}
