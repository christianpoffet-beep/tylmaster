<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use LogsActivity;

    protected $fillable = [
        'accounting_id', 'number', 'name', 'iban', 'type',
        'is_header', 'opening_balance', 'sort_order',
    ];

    protected $casts = [
        'is_header' => 'boolean',
        'opening_balance' => 'decimal:2',
    ];

    public function accounting()
    {
        return $this->belongsTo(Accounting::class);
    }

    public function debitBookings()
    {
        return $this->hasMany(Booking::class, 'debit_account_id');
    }

    public function creditBookings()
    {
        return $this->hasMany(Booking::class, 'credit_account_id');
    }

    /** Debit/credit sums and booking count handed in by loadTotals(); null = ask the database. */
    protected ?array $preloadedTotals = null;

    /**
     * Sums for many accounts in two queries. Afterwards balance, debit_total,
     * credit_total and has_bookings of these accounts need no query of their
     * own - a chart of 60 accounts was 200-400 queries per page before.
     *
     * @param  iterable<Account>  $accounts
     */
    public static function loadTotals(iterable $accounts): void
    {
        $accounts = collect($accounts);
        if ($accounts->isEmpty()) return;

        $ids = $accounts->pluck('id');
        $sums = fn (string $side) => Booking::whereIn($side, $ids)->groupBy($side)
            ->selectRaw("{$side} as account_id, SUM(amount) as total, COUNT(*) as bookings")
            ->toBase()->get()->keyBy('account_id');
        $debits = $sums('debit_account_id');
        $credits = $sums('credit_account_id');

        foreach ($accounts as $account) {
            $debit = $debits[$account->id] ?? null;
            $credit = $credits[$account->id] ?? null;
            $account->preloadedTotals = [
                'debit' => (float) ($debit->total ?? 0),
                'credit' => (float) ($credit->total ?? 0),
                'bookings' => (int) ($debit->bookings ?? 0) + (int) ($credit->bookings ?? 0),
            ];
        }
    }

    public function getBalanceAttribute(): float
    {
        $debits = $this->debit_total;
        $credits = $this->credit_total;

        // Assets & Expenses: increase with debit
        // Liabilities & Income: increase with credit
        if (in_array($this->type, ['asset', 'expense'])) {
            return (float) $this->opening_balance + $debits - $credits;
        }

        return (float) $this->opening_balance + $credits - $debits;
    }

    public function getDebitTotalAttribute(): float
    {
        return $this->preloadedTotals['debit'] ?? (float) $this->debitBookings()->sum('amount');
    }

    public function getCreditTotalAttribute(): float
    {
        return $this->preloadedTotals['credit'] ?? (float) $this->creditBookings()->sum('amount');
    }

    public function getHasBookingsAttribute(): bool
    {
        if ($this->preloadedTotals !== null) {
            return $this->preloadedTotals['bookings'] > 0;
        }

        return $this->debitBookings()->exists() || $this->creditBookings()->exists();
    }
}
