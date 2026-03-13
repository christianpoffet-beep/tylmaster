<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class AddressCircle extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'info'];

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'address_circle_organization');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'address_circle_project');
    }

    public function contactMembers()
    {
        return $this->morphedByMany(Contact::class, 'memberable', 'address_circle_members')->withPivot('email_override');
    }

    public function organizationMembers()
    {
        return $this->morphedByMany(Organization::class, 'memberable', 'address_circle_members')->withPivot('email_override');
    }

    public function getMemberCountAttribute(): int
    {
        return $this->contactMembers()->count() + $this->organizationMembers()->count();
    }
}
