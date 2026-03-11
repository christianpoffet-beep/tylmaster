<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Track extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title', 'isrc', 'genre', 'duration_seconds', 'status',
        'audio_file_path', 'release_id',
    ];

    public function release()
    {
        return $this->belongsTo(Release::class);
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'track_contact')->withPivot('role');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class);
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) return '--:--';
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
