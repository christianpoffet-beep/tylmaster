<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ArtworkImage extends Model
{
    use LogsActivity;

    protected $fillable = [
        'artwork_id', 'file_path', 'file_size', 'mime_type', 'original_name',
        'width', 'height', 'dpi', 'purpose', 'is_primary', 'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
        'dpi' => 'integer',
        'sort_order' => 'integer',
    ];

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    public function getDimensionsLabelAttribute(): ?string
    {
        if ($this->width && $this->height) {
            return $this->width . ' × ' . $this->height . ' px';
        }
        return null;
    }

    public function getFileSizeMbAttribute(): ?string
    {
        if (!$this->file_size) return null;
        return number_format($this->file_size / 1024 / 1024, 2) . ' MB';
    }

    public function getFormatLabelAttribute(): string
    {
        if ($this->original_name) {
            $ext = pathinfo($this->original_name, PATHINFO_EXTENSION);
            if ($ext) return strtoupper($ext);
        }
        if ($this->mime_type) {
            return strtoupper(str_replace('image/', '', $this->mime_type));
        }
        return '?';
    }

    public function isImage(): bool
    {
        return $this->mime_type && str_starts_with($this->mime_type, 'image/');
    }
}
