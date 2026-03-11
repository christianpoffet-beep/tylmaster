<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class MusicSubmission extends Model
{
    use LogsActivity;

    protected $fillable = [
        // Original fields
        'artist_name', 'email', 'track_title', 'genre', 'message',
        'file_path', 'status', 'contact_id',
        // Contact details
        'first_name', 'last_name', 'phone', 'street', 'zip', 'city', 'country',
        'iban', 'account_holder', 'bank_name',
        // Release details
        'project_name', 'subgenre', 'explicit', 'release_date', 'upc',
        'year_composition', 'year_recording', 'other_credits',
        // Media & Bio
        'cover_image_path', 'bio_short', 'bio_long', 'website',
        'spotify_link', 'instagram', 'social_other',
        // Contract
        'contract_excluded_countries', 'contract_end_date',
        'contract_advance_interest', 'digital_signature', 'contract_sign_date',
        // Songs & Promo
        'songs_data', 'promo_photos',
        // Payment
        'calculated_price', 'song_count', 'payment_status', 'access_code',
        // Links
        'release_id', 'contract_id',
    ];

    protected $casts = [
        'release_date' => 'date',
        'contract_end_date' => 'date',
        'contract_sign_date' => 'date',
        'songs_data' => 'array',
        'promo_photos' => 'array',
        'calculated_price' => 'decimal:2',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function release()
    {
        return $this->belongsTo(Release::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: $this->artist_name;
    }

    public function getSongsCountAttribute(): int
    {
        return $this->song_count ?? count($this->songs_data ?? []);
    }

    public function tasks()
    {
        return $this->morphToMany(Task::class, 'taskable');
    }
}
