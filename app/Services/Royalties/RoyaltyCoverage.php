<?php

namespace App\Services\Royalties;

use App\Models\Contract;
use App\Models\Organization;
use App\Models\Release;
use App\Models\RoyaltyLine;
use App\Models\Track;
use Illuminate\Support\Collection;

/**
 * Which products the statements can settle: per product its usage (not yet
 * settled by hand) and the contract that splits it. A product of another
 * label without a contract goes 100% to that label; one of the own labels
 * (The yelling light and the labels it owns) needs a contract with a split
 * for streaming.
 */
class RoyaltyCoverage
{
    public const OK = 'ok';
    public const DRAFT = 'draft';
    public const NO_SPLIT = 'no_split';
    public const OTHER_LABEL = 'other_label';
    public const MISSING = 'missing';

    /** @var Collection<int, Organization> */
    private Collection $own;

    public function __construct()
    {
        $this->own = RoyaltySettings::ownOrganizations();
    }

    /**
     * @return array{products: Collection, loose_tracks: Collection, open_amount: float}
     */
    public function build(bool $all = false): array
    {
        $usage = RoyaltyLine::where('presettled', false)->whereNotNull('track_id')
            ->selectRaw('track_id, sum(amount) as amount, sum(quantity) as quantity')->groupBy('track_id')->get()->keyBy('track_id');
        $albumUsage = RoyaltyLine::where('presettled', false)->whereNotNull('release_id')
            ->selectRaw('release_id, sum(amount) as amount')->groupBy('release_id')->pluck('amount', 'release_id');

        $releases = Release::with(['tracks.contracts.parties.organization', 'tracks.contracts.parties.contact', 'organizations',
            'contracts.parties.organization', 'contracts.parties.contact'])->orderBy('title')->get();

        $products = $releases->map(function (Release $release) use ($usage, $albumUsage) {
            $amount = (float) ($albumUsage[$release->id] ?? 0) + $release->tracks->sum(fn (Track $t) => (float) ($usage[$t->id]->amount ?? 0));
            $contracts = $release->contracts->concat($release->tracks->flatMap->contracts)->unique('id')
                ->filter(fn (Contract $c) => $c->coversRecordings())->values();
            $labels = $release->organizations->where('type', 'label')->concat($release->tracks->flatMap->organizations->where('type', 'label'))->unique('id');
            $otherLabel = $labels->first(fn (Organization $label) => !$this->isOwn($label));

            return (object) [
                'release' => $release,
                'bands' => $release->organizations->where('type', 'band')->pluck('primary_name')->join(', ') ?: (string) $release->main_artist,
                'label' => $otherLabel,
                'amount' => $amount,
                'contracts' => $contracts,
                'status' => $this->status($contracts, $otherLabel),
            ];
        });

        if (!$all) {
            $products = $products->filter(fn ($p) => $p->amount > 0);
        }

        // Tracks with usage that belong to no product: they need a contract of their own
        $onProducts = $releases->flatMap->tracks->pluck('id')->flip();
        $looseTracks = Track::with(['contracts.parties.organization', 'contracts.parties.contact', 'organizations'])
            ->whereIn('id', $usage->keys()->reject(fn ($id) => $onProducts->has($id)))->get()
            ->map(function (Track $track) use ($usage) {
                $otherLabel = $track->organizations->where('type', 'label')->first(fn ($label) => !$this->isOwn($label));
                $contracts = $track->contracts->filter(fn (Contract $c) => $c->coversRecordings())->values();

                return (object) [
                    'track' => $track,
                    'amount' => (float) $usage[$track->id]->amount,
                    'contracts' => $contracts,
                    'label' => $otherLabel,
                    'status' => $this->status($contracts, $otherLabel),
                ];
            });

        return [
            'products' => $products->sortByDesc('amount')->values(),
            'loose_tracks' => $looseTracks->sortByDesc('amount')->values(),
            'open_amount' => (float) RoyaltyLine::where('presettled', false)->whereNull('track_id')->whereNull('release_id')->sum('amount'),
        ];
    }

    /** @return Collection<int, Organization> */
    public function own(): Collection
    {
        return $this->own;
    }

    /** One of the own labels - also under another record of the same name */
    public function isOwn(Organization $organization): bool
    {
        $name = mb_strtolower(trim($organization->primary_name));

        return $this->own->contains(fn (Organization $own) => $own->id === $organization->id
            || mb_strtolower(trim($own->primary_name)) === $name);
    }

    private function status(Collection $contracts, ?Organization $otherLabel): string
    {
        $settling = $contracts->filter(fn (Contract $c) => in_array($c->status, Contract::SETTLING_STATUSES, true));
        if ($settling->contains(fn (Contract $c) => $c->splitFor('streaming') !== null)) return self::OK;
        if ($settling->isNotEmpty()) return self::NO_SPLIT;
        if ($contracts->isNotEmpty()) return self::DRAFT;

        return $otherLabel ? self::OTHER_LABEL : self::MISSING;
    }
}
