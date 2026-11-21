<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Accounting extends Model
{
    use LogsActivity;

    protected $fillable = [
        'accountable_type', 'accountable_id', 'name', 'currency',
        'period_start', 'period_end', 'status', 'chart_template_id', 'notes',
        'previous_accounting_id', 'profit_account_id',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function accountable()
    {
        return $this->morphTo();
    }

    public function accounts()
    {
        return $this->hasMany(Account::class)->orderBy('number');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class)->orderBy('booking_date');
    }

    public function bankTransactions()
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function bankImports()
    {
        return $this->hasMany(BankImport::class);
    }

    /** Rules of the owner (contact or organization) - shared by all of its accountings. */
    public function bookingRules()
    {
        return BookingRule::forOwnerOf($this);
    }

    public function chartTemplate()
    {
        return $this->belongsTo(ChartTemplate::class);
    }

    /** The year this one was opened from (Saldovortrag). */
    public function previousAccounting()
    {
        return $this->belongsTo(Accounting::class, 'previous_accounting_id');
    }

    /** The years opened from this one - normally just the next. */
    public function nextAccountings()
    {
        return $this->hasMany(Accounting::class, 'previous_accounting_id')->orderBy('period_start');
    }

    /** The equity account that took the previous year's profit or loss. */
    public function profitAccount()
    {
        return $this->belongsTo(Account::class, 'profit_account_id');
    }

    /**
     * Open invoices and expenses of the previous year are paid in this one:
     * the bank inbox looks for them in both.
     *
     * @return list<int>
     */
    public function openItemAccountingIds(): array
    {
        return array_values(array_filter([(int) $this->id, (int) $this->previous_accounting_id]));
    }

    /**
     * The accounting a payment on that day belongs to: this one, or the year
     * opened from it once the date lies past its end.
     */
    public function forDate(\DateTimeInterface|string $date): self
    {
        $date = \Illuminate\Support\Carbon::parse($date)->startOfDay();
        $accounting = $this;

        for ($i = 0; $i < 10 && $date->gt($accounting->period_end); $i++) {
            $next = $accounting->nextAccountings()->get()->last(fn (Accounting $a) => $date->gte($a->period_start));
            if (!$next) break;
            $accounting = $next;
        }

        return $accounting;
    }

    /** The account with the same number in this chart - how a year finds last year's accounts. */
    public function sameAccount(?Account $account): ?Account
    {
        if (!$account) return null;
        if ((int) $account->accounting_id === (int) $this->id) return $account;

        return $this->accounts()->where('is_header', false)->where('number', $account->number)->first();
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->status === 'closed';
    }

    public function getAccountableNameAttribute(): string
    {
        $entity = $this->accountable;
        if ($entity instanceof Contact) {
            return $entity->full_name;
        }
        if ($entity instanceof Organization) {
            return $entity->primary_name;
        }
        return '—';
    }

    public function applyTemplate(ChartTemplate $template): void
    {
        foreach ($template->accounts as $tplAccount) {
            $this->accounts()->create([
                'number' => $tplAccount->number,
                'name' => $tplAccount->name,
                'type' => $tplAccount->type,
                'is_header' => $tplAccount->is_header,
                'sort_order' => $tplAccount->sort_order,
            ]);
        }
    }
}
