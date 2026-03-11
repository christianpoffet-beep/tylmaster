<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use LogsActivity;

    protected $fillable = [
        'contract_number', 'title', 'type', 'status', 'start_date', 'end_date', 'terms',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function parties()
    {
        return $this->hasMany(ContractParty::class)->orderBy('sort_order');
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_contract')->withPivot('role');
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "VT-{$year}-";

        $last = static::where('contract_number', 'like', "{$prefix}%")
            ->orderByRaw("CAST(SUBSTR(contract_number, " . (strlen($prefix) + 1) . ") AS INTEGER) DESC")
            ->first();

        if ($last) {
            $nextSeq = (int) substr($last->contract_number, strlen($prefix)) + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_contract');
    }

    public function tracks()
    {
        return $this->belongsToMany(Track::class);
    }

    public function releases()
    {
        return $this->belongsToMany(Release::class);
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
}
