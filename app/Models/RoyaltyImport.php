<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One uploaded accounting export of a distributor (Rebeat). */
class RoyaltyImport extends Model
{
    public const SOURCES = ['rebeat' => 'Rebeat'];

    protected $fillable = [
        'source', 'file_name', 'file_path', 'period_from', 'period_to',
        'rows_new', 'rows_duplicate', 'rows_presettled', 'upload_pieces', 'currency',
        'completed_at', 'confirmed_at',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'completed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'rows_new' => 'integer',
        'rows_duplicate' => 'integer',
        'rows_presettled' => 'integer',
        'upload_pieces' => 'integer',
    ];

    public function lines()
    {
        return $this->hasMany(RoyaltyLine::class);
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    /** All pieces of the file are read (an upload that broke off is not) */
    public function getIsCompleteAttribute(): bool
    {
        return $this->completed_at !== null;
    }

    public function getIsConfirmedAttribute(): bool
    {
        return $this->confirmed_at !== null;
    }
}
