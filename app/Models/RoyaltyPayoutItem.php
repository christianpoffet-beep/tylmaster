<?php

namespace App\Models;

use App\Support\Iban;
use Illuminate\Database\Eloquent\Model;

/** One transfer of a payout run, with the bank details as they were then. */
class RoyaltyPayoutItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'royalty_payout_id', 'organization_id', 'contact_id', 'name', 'iban', 'street', 'zip', 'city', 'country', 'amount', 'booking_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payout()
    {
        return $this->belongsTo(RoyaltyPayout::class, 'royalty_payout_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /** Goes into the payment file: an IBAN that checks out (not a QR-IBAN) */
    public function getIsTransferableAttribute(): bool
    {
        return Iban::isValid($this->iban) && !Iban::isQrIban($this->iban);
    }
}
