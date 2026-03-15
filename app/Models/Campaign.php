<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name', 'type', 'status', 'language',
        'campaign_template_id', 'address_circle_id',
        'sender_name', 'sender_email', 'reply_to',
        'subject', 'body', 'notes',
        'scheduled_at', 'sent_at', 'recipients_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(CampaignTemplate::class, 'campaign_template_id');
    }

    public function addressCircle()
    {
        return $this->belongsTo(AddressCircle::class);
    }

    public function sends()
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Entwurf',
            'scheduled' => 'Geplant',
            'sending' => 'Wird gesendet',
            'sent' => 'Gesendet',
            'cancelled' => 'Abgebrochen',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
            'scheduled' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
            'sending' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300',
            'sent' => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
            'cancelled' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
            default => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
        };
    }
}
