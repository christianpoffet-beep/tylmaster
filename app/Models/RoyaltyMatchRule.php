<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * "Lines with this ISRC (or this artist and title) belong to that track":
 * an assignment made once in the matcher, applied to every later import.
 */
class RoyaltyMatchRule extends Model
{
    protected $fillable = ['source', 'match_key', 'track_id', 'release_id'];

    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    public function release()
    {
        return $this->belongsTo(Release::class);
    }
}
