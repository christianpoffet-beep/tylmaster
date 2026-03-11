<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use LogsActivity;

    protected $fillable = [
        'project_id', 'title', 'description', 'is_completed', 'due_date', 'priority',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'due_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function contacts()
    {
        return $this->morphedByMany(Contact::class, 'taskable');
    }

    public function contracts()
    {
        return $this->morphedByMany(Contract::class, 'taskable');
    }

    public function documents()
    {
        return $this->morphedByMany(Document::class, 'taskable');
    }

    public function uploadedDocuments()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function tracks()
    {
        return $this->morphedByMany(Track::class, 'taskable');
    }

    public function releases()
    {
        return $this->morphedByMany(Release::class, 'taskable');
    }

    public function projects()
    {
        return $this->morphedByMany(Project::class, 'taskable');
    }

    public function submissions()
    {
        return $this->morphedByMany(MusicSubmission::class, 'taskable');
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'high' => 'Hoch',
            'medium' => 'Mittel',
            'low' => 'Tief',
            default => '-',
        };
    }

    public function isOverdue(): bool
    {
        return $this->due_date && !$this->is_completed && $this->due_date->isPast();
    }
}
