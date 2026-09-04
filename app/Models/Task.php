<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use LogsActivity;

    protected $fillable = [
        'project_id', 'title', 'description', 'status', 'due_date', 'priority',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public const STATUSES = [
        'open' => 'Offen',
        'on_hold' => 'On Hold',
        'not_implemented' => 'Nicht umgesetzt',
        'completed' => 'Erledigt',
    ];

    /** Statuses that mean the task needs no further work. */
    public const CLOSED_STATUSES = ['completed', 'not_implemented'];

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

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
            'on_hold' => 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300',
            'not_implemented' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
            'completed' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300',
            default => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
        };
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /** Closed covers both "done" and "will not be done". */
    public function isClosed(): bool
    {
        return in_array($this->status, self::CLOSED_STATUSES, true);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->status === 'open' && $this->due_date->isPast();
    }
}
