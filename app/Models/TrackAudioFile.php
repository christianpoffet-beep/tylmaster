<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One version of a track's audio (master, radio edit, instrumental, ...).
 * Each version can carry its own ISRC; exactly one per track is the main
 * version, which the list views play and download.
 */
class TrackAudioFile extends Model
{
    use LogsActivity;

    protected $fillable = [
        'track_id', 'file_path', 'original_name', 'file_size',
        'title', 'isrc', 'notes', 'is_main',
        'audio_format', 'duration_seconds', 'bitrate', 'sample_rate', 'channels',
        'sort_order',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'file_size' => 'integer',
        'duration_seconds' => 'integer',
        'bitrate' => 'integer',
        'sample_rate' => 'integer',
        'channels' => 'integer',
        'sort_order' => 'integer',
    ];

    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /** What the version is called in lists: its title, else the uploaded file name. */
    public function getLabelAttribute(): string
    {
        return $this->title ?: ($this->original_name ?: basename($this->file_path));
    }

    public function getExtensionAttribute(): string
    {
        $ext = pathinfo($this->original_name ?: $this->file_path, PATHINFO_EXTENSION)
            ?: pathinfo($this->file_path, PATHINFO_EXTENSION);

        return strtolower($ext);
    }

    public function getIsrcFormattedAttribute(): ?string
    {
        return Track::formatIsrc($this->isrc);
    }

    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration_seconds) return null;

        return sprintf('%d:%02d', intdiv($this->duration_seconds, 60), $this->duration_seconds % 60);
    }

    public function getFileSizeLabelAttribute(): ?string
    {
        if (!$this->file_size) return null;

        return number_format($this->file_size / 1024 / 1024, 1, '.', "'") . ' MB';
    }

    /** "WAV · 1411 kbps · 44'100 Hz · Stereo · 3:45 · 42.1 MB" - whatever is known. */
    public function getTechnicalSummaryAttribute(): string
    {
        return collect([
            strtoupper($this->audio_format ?: $this->extension) ?: null,
            $this->bitrate ? $this->bitrate . ' kbps' : null,
            $this->sample_rate ? number_format($this->sample_rate, 0, '.', "'") . ' Hz' : null,
            $this->channels ? ($this->channels == 1 ? 'Mono' : 'Stereo') : null,
            $this->formatted_duration,
            $this->file_size_label,
        ])->filter()->join(' · ');
    }

    /** "nachtblau-radio-edit.wav" - track title plus version title. */
    public function getDownloadNameAttribute(): string
    {
        $base = Str::slug(trim(($this->track?->display_title ?? '') . ' ' . ($this->title ?? ''))) ?: 'audio';

        return $this->extension ? "{$base}.{$this->extension}" : $base;
    }

    /** What the global player needs to play this version. */
    public function playerPayload(): array
    {
        $track = $this->track;

        return [
            'title' => $track->display_title . ($this->title ? ' – ' . $this->title : ''),
            'artist' => $track->organizations->where('type', 'band')->pluck('primary_name')->join(', '),
            'url' => $this->url,
        ];
    }
}
