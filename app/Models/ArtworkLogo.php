<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ArtworkLogo extends Model
{
    use LogsActivity;

    protected $fillable = [
        'artwork_id', 'file_path', 'file_size', 'mime_type', 'original_name', 'comment',
    ];

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}
