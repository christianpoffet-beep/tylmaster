<?php

namespace App\Services\Royalties;

use App\Models\Organization;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Settings of the statements to artists and labels.
 *
 * The own labels - The yelling light and the labels it owns - are the
 * parties whose share stays income (it is not credited to anyone), and
 * their products need a contract with the artists. Chosen on the contract
 * overview; until then the label whose name contains "yelling light" and
 * that has the most tracks.
 */
class RoyaltySettings
{
    public const OWN_ORGANIZATIONS = 'royalties.own_organization_ids';

    /** Before several could be chosen: one id */
    private const OWN_ORGANIZATION_LEGACY = 'royalties.own_organization_id';

    /** @return Collection<int, Organization> the main one (The yelling light) first */
    public static function ownOrganizations(): Collection
    {
        $ids = Setting::get(self::OWN_ORGANIZATIONS) ?? array_filter([Setting::get(self::OWN_ORGANIZATION_LEGACY)]);
        $organizations = $ids ? Organization::whereIn('id', (array) $ids)->get() : collect();

        if ($organizations->isEmpty()) {
            return collect([self::guess()])->filter()->values();
        }

        return $organizations->sortBy(fn (Organization $o) => [str_contains(mb_strtolower($o->primary_name), 'yelling light') ? 0 : 1, $o->id])->values();
    }

    /** The main own label: the party a new artist contract is made with */
    public static function ownOrganization(): ?Organization
    {
        return self::ownOrganizations()->first();
    }

    /** @param iterable<int> $ids */
    public static function setOwnOrganizations(iterable $ids): void
    {
        Setting::set(self::OWN_ORGANIZATIONS, collect($ids)->map(fn ($id) => (int) $id)->unique()->values()->all());
    }

    private static function guess(): ?Organization
    {
        return Organization::where('names', 'like', '%yelling light%')
            ->withCount('tracks')
            ->orderByRaw("case when type = 'label' then 0 else 1 end")
            ->orderByDesc('tracks_count')->orderBy('id')
            ->first();
    }
}
