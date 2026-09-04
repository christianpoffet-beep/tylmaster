<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name', 'type', 'description', 'status', 'deadline',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'project_contact');
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class, 'project_contract');
    }

    public function tracks()
    {
        return $this->belongsToMany(Track::class);
    }

    public function releases()
    {
        return $this->belongsToMany(Release::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function linkedTasks()
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    /**
     * Every task belonging to this project, no matter which way it was
     * attached: as its main project (project_id) or through the taskable
     * link. Sorted open first, then on hold, then the closed ones, and by
     * due date within each group with undated tasks last.
     */
    public function getAllTasksAttribute()
    {
        $rank = ['open' => 0, 'on_hold' => 1];

        return $this->tasks
            ->merge($this->linkedTasks)
            ->unique('id')
            ->sortBy(fn ($task) => sprintf(
                '%d-%s',
                $rank[$task->status] ?? 2,
                $task->due_date?->format('Y-m-d') ?? '9999-99-99'
            ))
            ->values();
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class);
    }

    public function artworks()
    {
        return $this->belongsToMany(Artwork::class);
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    public function photoFolders()
    {
        return $this->belongsToMany(PhotoFolder::class, 'photo_folder_project');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
