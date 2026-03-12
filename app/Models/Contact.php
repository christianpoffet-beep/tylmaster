<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use LogsActivity;

    protected static function booted(): void
    {
        static::creating(function (Contact $contact) {
            $last = static::whereNotNull('ref_nr')->orderByRaw("CAST(SUBSTR(ref_nr, 2) AS INTEGER) DESC")->value('ref_nr');
            $nextNr = $last ? (int) substr($last, 1) + 1 : 1001;
            $contact->ref_nr = 'C' . $nextNr;
        });
    }

    protected $fillable = [
        'first_name', 'last_name', 'gender', 'birth_date', 'death_date',
        'email', 'secondary_emails',
        'phone', 'secondary_phones', 'street', 'zip', 'city', 'country',
        'nationality', 'ahv_number',
        'iban', 'bank_name', 'bank_zip', 'bank_city', 'bank_country', 'bic',
        'avatar_path', 'types', 'notes', 'ipis',
    ];

    protected $casts = [
        'types' => 'array',
        'secondary_emails' => 'array',
        'secondary_phones' => 'array',
        'ipis' => 'array',
        'birth_date' => 'date',
        'death_date' => 'date',
    ];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAllEmailsAttribute(): array
    {
        $emails = [];
        if ($this->email) {
            $emails[] = $this->email;
        }
        return array_merge($emails, $this->secondary_emails ?? []);
    }

    public function getAllPhonesAttribute(): array
    {
        $phones = [];
        if ($this->phone) {
            $phones[] = $this->phone;
        }
        return array_merge($phones, $this->secondary_phones ?? []);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class, 'contact_contract')->withPivot('role');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_contact');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function tracks()
    {
        return $this->belongsToMany(Track::class, 'track_contact')->withPivot('role');
    }

    public function releases()
    {
        return $this->belongsToMany(Release::class, 'release_contact')->withPivot('role');
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

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    public function photoFolders()
    {
        return $this->belongsToMany(PhotoFolder::class, 'contact_photo_folder');
    }

    public function accountings()
    {
        return $this->morphMany(Accounting::class, 'accountable');
    }
}
