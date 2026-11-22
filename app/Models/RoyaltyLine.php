<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One line of a distributor's accounting: a track (or a product) used in a
 * month, in a country, on a shop, with the amount credited for it.
 */
class RoyaltyLine extends Model
{
    public $timestamps = false;

    public const MATCH_ISRC = 'isrc';
    public const MATCH_RULE = 'rule';
    public const MATCH_MANUAL = 'manual';

    protected $fillable = [
        'royalty_import_id', 'source', 'external_id', 'track_id', 'release_id', 'match_type',
        'isrc', 'ean', 'artist', 'title', 'label', 'period', 'sales_date', 'billing_period',
        'country', 'shop', 'portal', 'sales_type', 'sales_category', 'stream_type',
        'quantity', 'amount_gross', 'amount', 'presettled',
    ];

    protected $casts = [
        'period' => 'date',
        'sales_date' => 'date',
        'billing_period' => 'date',
        'quantity' => 'integer',
        'amount_gross' => 'decimal:6',
        'amount' => 'decimal:6',
        'presettled' => 'boolean',
    ];

    public function import()
    {
        return $this->belongsTo(RoyaltyImport::class, 'royalty_import_id');
    }

    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    public function release()
    {
        return $this->belongsTo(Release::class);
    }
}
