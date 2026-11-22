<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One movement on the balance of a contact or an organization: a credit from
 * a statement (+), a payout (-) or a correction. The balance is their sum.
 */
class RoyaltyLedgerEntry extends Model
{
    public const CREDIT = 'credit';
    public const PAYOUT = 'payout';
    public const CORRECTION = 'correction';

    protected $fillable = [
        'organization_id', 'contact_id', 'entry_date', 'type', 'amount', 'description', 'royalty_statement_id', 'royalty_payout_id', 'booking_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function statement()
    {
        return $this->belongsTo(RoyaltyStatement::class, 'royalty_statement_id');
    }

    public function payout()
    {
        return $this->belongsTo(RoyaltyPayout::class, 'royalty_payout_id');
    }

    /** Name of the contact or organization */
    public function getPartyNameAttribute(): string
    {
        return $this->organization?->primary_name ?? $this->contact?->full_name ?? '—';
    }
}
