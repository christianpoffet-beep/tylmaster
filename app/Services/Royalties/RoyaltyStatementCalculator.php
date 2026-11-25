<?php

namespace App\Services\Royalties;

use App\Models\Contract;
use App\Models\Organization;
use App\Models\Release;
use App\Models\RoyaltyLine;
use App\Models\RoyaltyPayment;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementItem as Item;
use App\Models\Track;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Computes a statement: what each party gets from the usage of a period.
 *
 * 1. The CHF that Rebeat paid out in the period is distributed over the
 *    usage of the period by its EUR (the "factor"): exchange rate, fees and
 *    taxes are carried by everyone alike.
 * 2. Each track and month goes by the contract valid in that month (status
 *    active, expired or terminated; start and end date) and its split for
 *    streaming. Several valid contracts: a label deal first, else the newest.
 * 3. No contract: a product of another label goes 100% to that label; one of
 *    the own labels stays with The yelling light (and is reported).
 * 4. The shares of the own labels stay income; the others are credited -
 *    after the advances of their contract are recouped from them.
 */
class RoyaltyStatementCalculator
{
    public function __construct(private RoyaltyCoverage $coverage)
    {
    }

    /** The usage a draft statement would settle: confirmed imports, not settled yet, in the period */
    public static function openLines(RoyaltyStatement $statement): Builder
    {
        return RoyaltyLine::query()
            ->whereNull('royalty_statement_id')
            ->where('presettled', false)
            ->whereBetween('period', self::bounds($statement))
            ->whereHas('import', fn ($q) => $q->whereNotNull('confirmed_at')->whereNotNull('completed_at'));
    }

    /** The payouts a draft statement distributes: paid in the period, not distributed yet */
    public static function openPayments(RoyaltyStatement $statement): Builder
    {
        return RoyaltyPayment::whereNull('royalty_statement_id')
            ->whereBetween('paid_at', [$statement->period_from->toDateString(), self::bounds($statement)[1]])
            ->orderBy('paid_at');
    }

    /** First month of the period up to the end of its last day (dates may be stored with a time) */
    private static function bounds(RoyaltyStatement $statement): array
    {
        return [$statement->period_from->copy()->startOfMonth()->toDateString(), $statement->period_to->toDateString() . ' 23:59:59'];
    }

    /**
     * @return array{payments: Collection, pot: float, usage: float, factor: float, items: Collection<int, Item>, warnings: list<string>, unconfirmed: int}
     */
    public function calculate(RoyaltyStatement $statement): array
    {
        $payments = self::openPayments($statement)->get();
        $pot = round((float) $payments->sum('amount'), 2);

        $groups = self::openLines($statement)
            ->selectRaw('track_id, release_id, period, sum(amount) as eur, sum(quantity) as quantity')
            ->groupBy('track_id', 'release_id', 'period')->toBase()->get();
        $usage = (float) $groups->sum('eur');
        $factor = $usage > 0 ? $pot / $usage : 0.0;

        $tracks = Track::with(['contracts.parties.organization', 'contracts.parties.contact', 'organizations',
            'releases.contracts.parties.organization', 'releases.contracts.parties.contact', 'releases.organizations'])
            ->findMany($groups->pluck('track_id')->filter()->unique())->keyBy('id');
        $releases = Release::with(['contracts.parties.organization', 'contracts.parties.contact', 'organizations',
            'tracks.contracts.parties.organization', 'tracks.contracts.parties.contact', 'tracks.organizations'])
            ->findMany($groups->pluck('release_id')->filter()->unique())->keyBy('id');

        $items = [];
        $warnings = ['missing' => [], 'several' => [], 'split' => []];
        $unassigned = 0.0;

        $add = function (string $role, ?Organization $organization, ?int $contactId, ?Contract $contract, ?int $trackId, ?int $releaseId,
                         object $group, float $base, float $percent) use (&$items) {
            $key = implode('|', [$role, $organization?->id, $contactId, $contract?->id, $trackId, $releaseId]);
            $items[$key] ??= ['role' => $role, 'organization_id' => $organization?->id, 'contact_id' => $contactId, 'contract_id' => $contract?->id,
                'track_id' => $trackId, 'release_id' => $releaseId, 'quantity' => 0, 'usage_amount' => 0.0, 'base_amount' => 0.0,
                'percent' => $percent, 'amount' => 0.0, 'recouped' => 0.0];
            // Usage and its CHF in full (as the statement shows them), the share of it for this party
            $items[$key]['quantity'] += (int) $group->quantity;
            $items[$key]['usage_amount'] += (float) $group->eur;
            $items[$key]['base_amount'] += $base;
            $items[$key]['amount'] += $base * $percent / 100;
        };

        foreach ($groups as $group) {
            $base = (float) $group->eur * $factor;
            $month = Carbon::parse($group->period)->startOfMonth();
            $track = $group->track_id ? $tracks->get($group->track_id) : null;
            $release = !$track && $group->release_id ? $releases->get($group->release_id) : null;

            if (!$track && !$release) {
                $unassigned += (float) $group->eur;
                $add(Item::NONE, null, null, null, null, null, $group, $base, 100);
                continue;
            }

            $name = $track ? $track->display_title : $release->title;
            $contract = $this->contractFor($track, $release, $month, $warnings, $name);

            if (!$contract) {
                $label = $this->otherLabel($track, $release);
                if ($label) {
                    $add(Item::LABEL, $label, null, null, $track?->id, $release?->id, $group, $base, 100);
                } else {
                    $warnings['missing'][$name] = ($warnings['missing'][$name] ?? 0) + (float) $group->eur;
                    $add(Item::NONE, null, null, null, $track?->id, $release?->id, $group, $base, 100);
                }
                continue;
            }

            $split = $contract->splitFor('streaming');
            $total = array_sum(array_column($split, 'percent'));
            foreach ($split as ['party' => $party, 'percent' => $percent]) {
                if ($percent <= 0) continue;
                $organization = $party->organization;
                $role = $organization && $this->coverage->isOwn($organization) ? Item::OWN
                    : ($organization && $organization->type === 'label' ? Item::LABEL : Item::ARTIST);
                $add($role, $organization, $organization ? null : $party->contact_id, $contract, $track?->id, $release?->id, $group, $base, $percent);
            }
            if (abs($total - 100) > 0.01) {
                $warnings['split'][$contract->contract_number ?: $contract->title] = $total;
                if ($total < 100) {
                    $add(Item::OWN, $this->coverage->own()->first(), null, $contract, $track?->id, $release?->id, $group, $base, 100 - $total);
                }
            }
        }

        $items = new \Illuminate\Database\Eloquent\Collection(array_map(fn ($item) => new Item($item), array_values($items)));
        $this->recoup($statement, $items);

        return [
            'payments' => $payments,
            'pot' => $pot,
            'usage' => $usage,
            'factor' => $factor,
            'items' => $items->values(),
            'warnings' => array_merge($this->warnings($warnings, $unassigned, $payments->isEmpty(), $usage), $this->plausibility($payments, $usage, $factor)),
            'unconfirmed' => RoyaltyLine::whereNull('royalty_statement_id')->where('presettled', false)
                ->whereBetween('period', self::bounds($statement))
                ->whereHas('import', fn ($q) => $q->whereNull('confirmed_at'))->count(),
        ];
    }

    /**
     * Open advances of a contract: entered up to the statement date, less what
     * final statements recouped already.
     */
    public static function openAdvances(Contract $contract, \DateTimeInterface $until): float
    {
        $advances = (float) $contract->advances()->where('date', '<=', Carbon::instance($until)->toDateString())->sum('amount');
        $recouped = (float) DB::table('royalty_statement_items')
            ->join('royalty_statements', 'royalty_statements.id', '=', 'royalty_statement_items.royalty_statement_id')
            ->where('royalty_statements.status', RoyaltyStatement::FINAL)
            ->where('royalty_statement_items.contract_id', $contract->id)
            ->sum('royalty_statement_items.recouped');

        return max(0, round($advances - $recouped, 2));
    }

    /** Advances come off the credited shares of their contract, pro rata */
    private function recoup(RoyaltyStatement $statement, Collection $items): void
    {
        $byContract = $items->filter(fn (Item $i) => $i->contract_id && in_array($i->role, [Item::ARTIST, Item::LABEL], true))->groupBy('contract_id');
        foreach ($byContract as $contractId => $contractItems) {
            $open = self::openAdvances(Contract::find($contractId), $statement->statement_date);
            $total = $contractItems->sum('amount');
            if ($open <= 0 || $total <= 0) continue;

            $recoup = min($open, $total);
            foreach ($contractItems as $item) {
                $item->recouped = $item->amount * $recoup / $total;
            }
        }
    }

    private function contractFor(?Track $track, ?Release $release, Carbon $month, array &$warnings, string $name): ?Contract
    {
        $candidates = $track
            ? $track->contracts->concat($track->releases->flatMap->contracts)
            : $release->contracts->concat($release->tracks->flatMap->contracts);

        $valid = $candidates->unique('id')
            ->filter(fn (Contract $c) => $c->coversRecordings() && $c->settlesBetween($month, $month->copy()->endOfMonth()) && $c->splitFor('streaming') !== null)
            ->sortBy(fn (Contract $c) => [$c->type === 'label' ? 0 : 1, -($c->start_date?->timestamp ?? 0), -$c->id])
            ->values();

        if ($valid->count() > 1) {
            $warnings['several'][$name] = $valid->first()->contract_number ?: $valid->first()->title;
        }

        return $valid->first();
    }

    /** The label of another label's track or product (not one of the own labels) */
    private function otherLabel(?Track $track, ?Release $release): ?Organization
    {
        $labels = $track
            ? $track->organizations->concat($track->releases->flatMap->organizations)
            : $release->organizations->concat($release->tracks->flatMap->organizations);

        return $labels->where('type', 'label')->unique('id')->first(fn (Organization $label) => !$this->coverage->isOwn($label));
    }

    /** @return list<string> */
    /**
     * Payouts and usage of the same period should be about the same in EUR.
     * If the usage gives a rate far off the one the bank applied, usage is
     * probably missing (or a payout belongs to another period).
     *
     * @return list<string>
     */
    private function plausibility(Collection $payments, float $usage, float $factor): array
    {
        $withEur = $payments->filter(fn ($p) => (float) $p->amount_foreign > 0);
        $paidEur = (float) $withEur->sum('amount_foreign');
        if ($paidEur <= 0 || $usage <= 0) return [];

        $bankRate = (float) $withEur->sum('amount') / $paidEur;
        if (abs($factor / $bankRate - 1) <= 0.15) return [];

        $fmt = fn ($v, $d = 2) => number_format($v, $d, '.', "'");

        return ["1 EUR Nutzung ergibt hier CHF {$fmt($factor, 4)}, die Bank hat die Rebeat-Zahlungen aber zu CHF {$fmt($bankRate, 4)} umgerechnet. "
            . "Rebeat hat im Zeitraum EUR {$fmt($paidEur)} ausbezahlt, eingelesen ist Nutzung von EUR {$fmt($usage)} – "
            . 'sind die Nutzungsdaten des ganzen Zeitraums eingelesen und bestätigt?'];
    }

    private function warnings(array $warnings, float $unassigned, bool $noPayments, float $usage): array
    {
        $eur = fn ($v) => 'EUR ' . number_format($v, 2, '.', "'");
        $out = [];
        if ($noPayments) {
            $out[] = 'Im Zeitraum sind keine Rebeat-Zahlungen erfasst – es gibt nichts zu verteilen.';
        }
        if ($usage <= 0) {
            $out[] = 'Im Zeitraum gibt es keine bestätigte, offene Nutzung.';
        }
        if ($unassigned > 0.005) {
            $out[] = "{$eur($unassigned)} Nutzung ohne Track – ihr Anteil bleibt bei The yelling light. Im Rebeat-Matcher zuordnen.";
        }
        if ($warnings['missing']) {
            arsort($warnings['missing']);
            $list = collect($warnings['missing'])->map(fn ($v, $name) => "{$name} ({$eur($v)})")->take(8)->join(', ');
            $more = count($warnings['missing']) > 8 ? ' und ' . (count($warnings['missing']) - 8) . ' weitere' : '';
            $out[] = count($warnings['missing']) . " Titel ohne gültigen Vertrag – ihr Anteil bleibt bei The yelling light: {$list}{$more}.";
        }
        foreach ($warnings['several'] as $name => $taken) {
            $out[] = "«{$name}»: mehrere gültige Verträge, genommen wurde {$taken}.";
        }
        foreach ($warnings['split'] as $contract => $total) {
            $out[] = "Vertrag {$contract}: der Streaming-Split ergibt " . rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.') . ' % statt 100 %.';
        }

        return $out;
    }
}
