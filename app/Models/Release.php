<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title', 'upc', 'release_date', 'label', 'cover_image_path',
    ];

    protected $casts = [
        'release_date' => 'date',
    ];

    public function tracks()
    {
        return $this->hasMany(Track::class);
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'release_contact')->withPivot('role');
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class);
    }

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class);
    }
}
