<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * "Text contains X -> account Y" for the bank inbox. Belongs to the owner of
 * the accounting (contact or organization), not to one year: the account is
 * kept as its number and looked up in whichever accounting is being booked.
 */
class BookingRule extends Model
{
    use LogsActivity;

    public const DIRECTIONS = ['in' => 'Eingang', 'out' => 'Ausgang', 'any' => 'Beide'];

    protected $fillable = [
        'accountable_type', 'accountable_id', 'pattern', 'direction', 'account_number', 'description',
    ];

    public function accountable()
    {
        return $this->morphTo();
    }

    public function scopeForOwnerOf($query, Accounting $accounting)
    {
        return $query->where('accountable_type', $accounting->accountable_type)
            ->where('accountable_id', $accounting->accountable_id);
    }

    public function matches(BankTransaction $transaction): bool
    {
        if ($this->direction === 'in' && !$transaction->is_incoming) return false;
        if ($this->direction === 'out' && $transaction->is_incoming) return false;

        return $this->pattern !== '' && mb_stripos($transaction->search_text, $this->pattern) !== false;
    }

    protected function getActivityLogLabel(): string
    {
        return "Regel «{$this->pattern}» → {$this->account_number}";
    }
}
