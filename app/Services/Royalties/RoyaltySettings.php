<?php

namespace App\Services\Royalties;

use App\Models\Organization;
use App\Models\Setting;

/**
 * Settings of the statements to artists and labels.
 *
 * The own label is the party whose share stays income (it is not credited
 * to anyone). Chosen on the contract overview; until then the label whose
 * name contains "yelling light" and that has the most tracks.
 */
class RoyaltySettings
{
    public const OWN_ORGANIZATION = 'royalties.own_organization_id';

    public static function ownOrganization(): ?Organization
    {
        $id = Setting::get(self::OWN_ORGANIZATION);
        if ($id && $organization = Organization::find($id)) {
            return $organization;
        }

        return Organization::where('names', 'like', '%yelling light%')
            ->withCount('tracks')
            ->orderByRaw("case when type = 'label' then 0 else 1 end")
            ->orderByDesc('tracks_count')->orderBy('id')
            ->first();
    }

    public static function setOwnOrganization(Organization $organization): void
    {
        Setting::set(self::OWN_ORGANIZATION, $organization->id);
    }
}
