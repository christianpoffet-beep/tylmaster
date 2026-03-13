<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use LogsActivity;

    protected $fillable = [
        'contract_number', 'title', 'type', 'status', 'start_date', 'end_date', 'terms',
        'has_zession', 'zession_amount', 'zession_currency', 'zession_notes',
        'territory', 'rights', 'rights_label_a', 'rights_label_b',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'has_zession' => 'boolean',
        'zession_amount' => 'decimal:2',
        'territory' => 'array',
        'rights' => 'array',
    ];

    /**
     * Territory presets for quick selection.
     */
    public const TERRITORY_PRESETS = [
        'world' => ['label' => 'Weltweit', 'countries' => ['ALL']],
        'europe' => ['label' => 'Europa', 'countries' => ['AL','AD','AT','BY','BE','BA','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IS','IE','IT','XK','LV','LI','LT','LU','MT','MD','MC','ME','NL','MK','NO','PL','PT','RO','RU','SM','RS','SK','SI','ES','SE','CH','UA','GB','VA']],
        'usa' => ['label' => 'USA', 'countries' => ['US']],
        'gsa' => ['label' => 'GSA (DACH)', 'countries' => ['DE','AT','CH']],
        'uk' => ['label' => 'UK', 'countries' => ['GB']],
        'nordics' => ['label' => 'Nordics', 'countries' => ['DK','FI','IS','NO','SE']],
        'benelux' => ['label' => 'Benelux', 'countries' => ['BE','NL','LU']],
    ];

    /**
     * Get human-readable territory display.
     */
    public function getTerritoryDisplayAttribute(): string
    {
        if (empty($this->territory)) {
            return '';
        }

        if (in_array('ALL', $this->territory)) {
            return 'Weltweit';
        }

        // Check if it matches a preset
        foreach (self::TERRITORY_PRESETS as $key => $preset) {
            if ($preset['countries'] === ['ALL']) continue;
            $presetCountries = $preset['countries'];
            sort($presetCountries);
            $territory = $this->territory;
            sort($territory);
            if ($presetCountries === $territory) {
                return $preset['label'];
            }
        }

        return count($this->territory) . ' Länder';
    }

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
