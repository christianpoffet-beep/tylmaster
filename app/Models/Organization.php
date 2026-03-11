<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use LogsActivity;

    protected $fillable = [
        'type', 'names', 'biography', 'websites',
        'street', 'zip', 'city', 'country', 'email', 'phone',
        'iban', 'bank_name', 'bank_zip', 'bank_city', 'bank_country', 'bic',
        'vat_number', 'avatar_path',
    ];

    protected $casts = [
        'names' => 'array',
        'websites' => 'array',
    ];

    public function getPrimaryNameAttribute(): string
    {
        return $this->names[0] ?? '';
    }

    public function getAllNamesAttribute(): string
    {
        return implode(', ', $this->names ?? []);
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function tracks()
    {
        return $this->belongsToMany(Track::class);
    }

    public function releases()
    {
        return $this->belongsToMany(Release::class);
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    public function photoFolders()
    {
        return $this->belongsToMany(PhotoFolder::class, 'organization_photo_folder');
    }

    public function accountings()
    {
        return $this->morphMany(Accounting::class, 'accountable');
    }
}
