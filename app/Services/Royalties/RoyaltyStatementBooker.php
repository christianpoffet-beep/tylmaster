<?php

namespace App\Services\Royalties;

use App\Models\Account;
use App\Models\Accounting;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\RoyaltyLedgerEntry;
use App\Models\RoyaltyPayment;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementItem as Item;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Finalizes a statement - and takes it back.
 *
 * Finalizing keeps what the calculator found (items), credits each artist
 * and label on its balance and books, on the statement date:
 *   artists' shares        expense (6501)  / payables (2001), less what was booked flat before
 *   recouped advances      expense (6501)  / recouped (3004)
 *   per label              its account     / payables (2001)
 * The settled usage and the distributed payouts are marked. Undoing deletes
 * the bookings and credits and opens usage and payouts again.
 */
class RoyaltyStatementBooker
{
    public function __construct(private RoyaltyStatementCalculator $calculator)
    {
    }

    /** expense, payable, recouped: by the numbers of the chart (6501, 2001, 3004) */
    public static function defaultAccounts(?Accounting $accounting): array
    {
        if (!$accounting) return ['expense' => null, 'payable' => null, 'recouped' => null, 'labels' => []];
        $byNumber = $accounting->accounts()->where('is_header', false)->pluck('id', 'number');

        return [
            'expense' => $byNumber['6501'] ?? null,
            'payable' => $byNumber['2001'] ?? null,
            'recouped' => $byNumber['3004'] ?? null,
            'labels' => [],
        ];
    }

    /** The account of a label: the one chosen, else an expense account named after it, else the artists' one */
    public static function labelAccount(RoyaltyStatement $statement, Organization $label): ?int
    {
        $accounts = $statement->accounts ?? [];
        if (!empty($accounts['labels'][$label->id])) {
            return (int) $accounts['labels'][$label->id];
        }
        $named = $statement->accounting?->accounts()->where('is_header', false)->where('type', 'expense')->get()
            ->first(fn (Account $a) => str_contains(mb_strtolower($a->name), mb_strtolower($label->primary_name)));

        return $named?->id ?? ($accounts['expense'] ?? null);
    }

    /**
     * Per party: role, name, share, recouped, credit and the items behind it.
     *
     * @param Collection<int, Item> $items
     */
    public static function parties(Collection $items): Collection
    {
        $organizations = Organization::findMany($items->pluck('organization_id')->filter()->unique())->keyBy('id');
        $contacts = Contact::findMany($items->pluck('contact_id')->filter()->unique())->keyBy('id');

        return $items->groupBy(fn (Item $i) => $i->role . '|' . $i->organization_id . '|' . $i->contact_id)
            ->map(function (Collection $partyItems) use ($organizations, $contacts) {
                $first = $partyItems->first();
                $organization = $first->organization_id ? $organizations->get($first->organization_id) : null;
                $contact = $first->contact_id ? $contacts->get($first->contact_id) : null;
                $share = $partyItems->sum('amount');
                $recouped = $partyItems->sum('recouped');

                return (object) [
                    'role' => $first->role,
                    'organization' => $organization,
                    'contact' => $contact,
                    'name' => $organization?->primary_name ?? $contact?->full_name ?? 'ohne Vertrag / ohne Track',
                    'share' => $share,
                    'recouped' => $recouped,
                    'credit' => round($share - $recouped, 2),
                    'items' => $partyItems->sortByDesc('amount')->values(),
                ];
            })
            ->sortBy(fn ($p) => [array_search($p->role, [Item::ARTIST, Item::LABEL, Item::OWN, Item::NONE], true), -$p->share])
            ->values();
    }

    public function finalize(RoyaltyStatement $statement): RoyaltyStatement
    {
        if ($statement->is_final) {
            throw new RoyaltyStatementException('Diese Abrechnung ist schon abgeschlossen.');
        }
        $accounting = $statement->accounting;
        if (!$accounting) {
            throw new RoyaltyStatementException('Bitte eine Buchhaltung wählen, in die gebucht wird.');
        }
        if ($accounting->is_closed) {
            throw new RoyaltyStatementException("Die Buchhaltung «{$accounting->name}» ist abgeschlossen.");
        }
        if (!$statement->statement_date->betweenIncluded($accounting->period_start, $accounting->period_end)) {
            throw new RoyaltyStatementException("Das Datum der Abrechnung liegt nicht in der Periode von «{$accounting->name}».");
        }

        $result = $this->calculator->calculate($statement);
        if ($result['pot'] <= 0 || $result['usage'] <= 0) {
            throw new RoyaltyStatementException('Es gibt nichts zu verteilen: ' . implode(' ', $result['warnings']));
        }

        $accounts = ($statement->accounts ?? []) + self::defaultAccounts($accounting);
        $parties = self::parties($result['items']);
        $credited = $parties->whereIn('role', [Item::ARTIST, Item::LABEL])->filter(fn ($p) => abs($p->credit) >= 0.005);
        $recouped = round($result['items']->sum('recouped'), 2);

        $account = fn (?int $id, string $what) => ($id ? $accounting->accounts()->whereKey($id)->where('is_header', false)->first() : null)
            ?? throw new RoyaltyStatementException("Bitte das Konto für {$what} wählen (in «{$accounting->name}»).");
        $expense = $account($accounts['expense'] ?? null, 'die Anteile der Künstler:innen');
        $payable = $account($accounts['payable'] ?? null, 'die Schulden an Künstler:innen (Kreditoren)');
        $recoupedAccount = $recouped > 0 ? $account($accounts['recouped'] ?? null, 'verrechnete Vorschüsse') : null;

        return DB::transaction(function () use ($statement, $result, $parties, $credited, $recouped, $expense, $payable, $recoupedAccount, $account) {
            foreach ($result['items'] as $item) {
                $item->royalty_statement_id = $statement->id;
                $item->save();
            }

            $title = $statement->title;
            $bookings = [];
            $book = function (Account $debit, Account $credit, float $amount, string $description) use ($statement, &$bookings) {
                if (abs($amount) < 0.005) return;
                [$debit, $credit, $amount] = $amount > 0 ? [$debit, $credit, $amount] : [$credit, $debit, -$amount];
                $bookings[] = Booking::create([
                    'accounting_id' => $statement->accounting_id,
                    'booking_date' => $statement->statement_date->toDateString(),
                    'description' => mb_substr($description, 0, 250),
                    'debit_account_id' => $debit->id,
                    'credit_account_id' => $credit->id,
                    'amount' => round($amount, 2),
                ])->id;
            };

            foreach ($credited as $party) {
                RoyaltyLedgerEntry::create([
                    'organization_id' => $party->organization?->id,
                    'contact_id' => $party->organization ? null : $party->contact?->id,
                    'entry_date' => $statement->statement_date->toDateString(),
                    'type' => RoyaltyLedgerEntry::CREDIT,
                    'amount' => $party->credit,
                    'description' => "Abrechnung {$title}",
                    'royalty_statement_id' => $statement->id,
                ]);
            }

            $artists = round($credited->where('role', Item::ARTIST)->sum('credit'), 2);
            $preBooked = (float) $statement->pre_booked;
            $book($expense, $payable, $artists - $preBooked, "Künstlerabrechnung {$title}: Anteile Künstler:innen"
                . ($preBooked ? ' (abzüglich bereits pauschal gebuchter CHF ' . number_format($preBooked, 2, '.', "'") . ')' : ''));
            if ($recoupedAccount) {
                $book($expense, $recoupedAccount, $recouped, "Künstlerabrechnung {$title}: verrechnete Vorschüsse");
            }
            foreach ($credited->where('role', Item::LABEL) as $label) {
                $labelAccount = $account(self::labelAccount($statement, $label->organization), "das Label {$label->name}");
                $book($labelAccount, $payable, $label->credit, "Künstlerabrechnung {$title}: Anteil {$label->name}");
            }

            // What this statement settled: the open usage and payouts of its period
            RoyaltyStatementCalculator::openLines($statement)->update(['royalty_statement_id' => $statement->id]);
            RoyaltyStatementCalculator::openPayments($statement)->reorder()->update(['royalty_statement_id' => $statement->id]);
            $statement->update([
                'status' => RoyaltyStatement::FINAL,
                'finalized_at' => now(),
                'payments_amount' => $result['pot'],
                'usage_amount' => $result['usage'],
                'factor' => $result['factor'],
                'booking_ids' => $bookings,
            ]);

            return $statement;
        });
    }

    public function undo(RoyaltyStatement $statement): RoyaltyStatement
    {
        if (!$statement->is_final) {
            throw new RoyaltyStatementException('Diese Abrechnung ist noch nicht abgeschlossen.');
        }
        if ($statement->accounting?->is_closed) {
            throw new RoyaltyStatementException("Die Buchhaltung «{$statement->accounting->name}» ist abgeschlossen.");
        }
        // Paid out from these credits meanwhile: taking them back would leave negative balances
        $credited = $statement->ledgerEntries()->get(['organization_id', 'contact_id']);
        $paidSince = $credited->isNotEmpty() && RoyaltyLedgerEntry::where('type', RoyaltyLedgerEntry::PAYOUT)
            ->where('created_at', '>=', $statement->finalized_at)
            ->where(function ($q) use ($credited) {
                foreach ($credited as $entry) {
                    $q->orWhere(fn ($w) => $entry->organization_id
                        ? $w->where('organization_id', $entry->organization_id)
                        : $w->whereNull('organization_id')->where('contact_id', $entry->contact_id));
                }
            })->exists();
        if ($paidSince) {
            throw new RoyaltyStatementException('Seit dieser Abrechnung wurde ausbezahlt. Zuerst die Auszahlungen stornieren.');
        }

        return DB::transaction(function () use ($statement) {
            Booking::whereIn('id', $statement->booking_ids ?? [])->get()->each->delete();
            $statement->ledgerEntries()->delete();
            $statement->items()->delete();
            $statement->lines()->update(['royalty_statement_id' => null]);
            RoyaltyPayment::where('royalty_statement_id', $statement->id)->update(['royalty_statement_id' => null]);
            $statement->update(['status' => RoyaltyStatement::DRAFT, 'finalized_at' => null, 'booking_ids' => null,
                'payments_amount' => null, 'usage_amount' => null, 'factor' => null]);

            return $statement;
        });
    }
}
