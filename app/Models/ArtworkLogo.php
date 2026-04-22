<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ArtworkLogo extends Model
{
    use LogsActivity;

    protected $fillable = [
        'artwork_id', 'file_path', 'file_size', 'mime_type', 'original_name',
        'comment', 'is_primary', 'purpose', 'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
