<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A statement sent to a party by e-mail (or a test mail, or a failed attempt). */
class RoyaltyStatementMail extends Model
{
    public const SENT = 'sent';
    public const FAILED = 'failed';
    public const TEST = 'test';

    protected $fillable = ['royalty_statement_id', 'organization_id', 'contact_id', 'email', 'status', 'message_id', 'error'];

    public function statement()
    {
        return $this->belongsTo(RoyaltyStatement::class, 'royalty_statement_id');
    }
}
