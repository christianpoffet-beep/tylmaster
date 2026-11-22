<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One uploaded accounting export of a distributor (Rebeat). */
class RoyaltyImport extends Model
{
    public const SOURCES = ['rebeat' => 'Rebeat'];

    protected $fillable = [
        'source', 'file_name', 'file_path', 'period_from', 'period_to',
        'rows_new', 'rows_duplicate', 'rows_presettled', 'currency', 'confirmed_at',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(RoyaltyLine::class);
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    public function getIsConfirmedAttribute(): bool
    {
        return $this->confirmed_at !== null;
    }
}
