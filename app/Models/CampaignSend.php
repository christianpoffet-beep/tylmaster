<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignSend extends Model
{
    protected $fillable = [
        'campaign_id', 'recipient_type', 'recipient_id',
        'email', 'name', 'status', 'brevo_message_id', 'error', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function recipient()
    {
        return $this->morphTo();
    }
}
