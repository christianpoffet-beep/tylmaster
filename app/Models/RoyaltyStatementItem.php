<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** What a final statement gave a party, per contract and track (or product). */
class RoyaltyStatementItem extends Model
{
    public $timestamps = false;

    public const ARTIST = 'artist';
    public const LABEL = 'label';
    public const OWN = 'own';
    public const NONE = 'none';

    protected $fillable = [
        'royalty_statement_id', 'role', 'organization_id', 'contact_id', 'contract_id', 'release_id', 'track_id',
        'quantity', 'usage_amount', 'base_amount', 'percent', 'amount', 'recouped',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'usage_amount' => 'float',
        'base_amount' => 'float',
        'percent' => 'float',
        'amount' => 'float',
        'recouped' => 'float',
    ];

    public function statement()
    {
        return $this->belongsTo(RoyaltyStatement::class, 'royalty_statement_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
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
