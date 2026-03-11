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

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function linkedTasks()
    {
        return $this->morphToMany(Task::class, 'taskable');
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
