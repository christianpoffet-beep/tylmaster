<?php

namespace App\Services;

use App\Models\Track;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Carries chosen metadata of one track over to other tracks.
 *
 * The values are always read from the source track itself; the request only
 * says which of them to take. Selected IDs that do not belong to the source
 * are ignored, so nothing can be attached that the source does not carry.
 *
 * Single fields overwrite the target (empty source values are skipped, as in
 * the paste button of the form). Lists - alternative titles, bands, labels,
 * publishers, credits, products, projects, contracts - are either added to
 * what the target has, or replace it. Replacing only touches the lists that
 * have something selected, and products keep their track number on the
 * target; a newly linked product gets none, since that number belongs to the
 * source track.
 */
class TrackMetadataTransfer
{
    public const FIELDS = [
        'title', 'version', 'status', 'genre', 'duration_seconds', 'description',
        'bpm', 'musical_key', 'language', 'recording_location', 'recording_years',
    ];

    private const ORGANIZATION_LISTS = ['bands' => 'band', 'labels' => 'label', 'publishers' => 'publishing'];

    /** One credit is a person in a role - the same person can hold several. */
    public static function creditKey($contact): string
    {
        return implode('|', [
            $contact->id,
            $contact->pivot->role,
            $contact->pivot->instrument ?? '',
            $contact->pivot->ipi_number ?? '',
        ]);
    }

    /** True when the selection would change anything at all. */
    public static function selectsSomething(array $selection): bool
    {
        foreach (['fields', 'bands', 'labels', 'publishers', 'credits', 'releases', 'projects', 'contracts'] as $key) {
            if (!empty($selection[$key])) return true;
        }

        return !empty($selection['alternative_titles']);
    }

    /** @return int how many tracks were updated */
    public function apply(Track $source, array $selection, Collection $targets, bool $replace): int
    {
        $source->loadMissing(['organizations', 'contacts', 'releases', 'projects', 'contracts']);

        $values = collect($selection['fields'] ?? [])
            ->intersect(self::FIELDS)
            ->mapWithKeys(fn ($field) => [$field => $source->getAttribute($field)])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        $alternativeTitles = !empty($selection['alternative_titles']) ? ($source->alternative_titles ?? []) : [];

        $organizations = [];
        foreach (self::ORGANIZATION_LISTS as $list => $type) {
            $organizations[$type] = $this->ownIds($source->organizations->where('type', $type), $selection[$list] ?? []);
        }

        $creditKeys = array_map('strval', $selection['credits'] ?? []);
        $credits = $source->contacts->filter(fn ($contact) => in_array(self::creditKey($contact), $creditKeys, true))->values();
        $releases = $source->releases->whereIn('id', $this->ownIds($source->releases, $selection['releases'] ?? []))->values();
        $projectIds = $this->ownIds($source->projects, $selection['projects'] ?? []);
        $contractIds = $this->ownIds($source->contracts, $selection['contracts'] ?? []);

        DB::transaction(function () use ($targets, $values, $alternativeTitles, $organizations, $credits, $releases, $projectIds, $contractIds, $replace) {
            foreach ($targets as $target) {
                if ($values) {
                    $target->update($values);
                }

                if ($alternativeTitles) {
                    $titles = $replace ? $alternativeTitles : array_merge($target->alternative_titles ?? [], $alternativeTitles);
                    $target->update(['alternative_titles' => array_values(array_unique($titles))]);
                }

                foreach ($organizations as $type => $ids) {
                    if ($ids) $this->syncOrganizations($target, $type, $ids, $replace);
                }

                if ($credits->isNotEmpty()) $this->syncCredits($target, $credits, $replace);
                if ($releases->isNotEmpty()) $this->syncReleases($target, $releases, $replace);

                if ($projectIds) {
                    $replace ? $target->projects()->sync($projectIds) : $target->projects()->syncWithoutDetaching($projectIds);
                }
                if ($contractIds) {
                    $replace ? $target->contracts()->sync($contractIds) : $target->contracts()->syncWithoutDetaching($contractIds);
                }
            }
        });

        return $targets->count();
    }

    /** The selected IDs that the source really carries. */
    private function ownIds(Collection $own, array $selected): array
    {
        return $own->pluck('id')->intersect(array_map('intval', $selected))->values()->all();
    }

    /** Bands, labels and publishers share one relation - replacing one type leaves the others alone. */
    private function syncOrganizations(Track $target, string $type, array $ids, bool $replace): void
    {
        if ($replace) {
            $obsolete = $target->organizations()->where('type', $type)->pluck('organizations.id')->diff($ids)->all();
            if ($obsolete) $target->organizations()->detach($obsolete);
        }

        $target->organizations()->syncWithoutDetaching($ids);
    }

    private function syncCredits(Track $target, Collection $credits, bool $replace): void
    {
        if ($replace) {
            $target->contacts()->detach();
        }

        $existing = $replace ? [] : $target->contacts()->get()->map(fn ($contact) => self::creditKey($contact))->all();

        foreach ($credits as $credit) {
            if (in_array(self::creditKey($credit), $existing, true)) continue;

            $target->contacts()->attach($credit->id, [
                'role' => $credit->pivot->role,
                'instrument' => $credit->pivot->instrument,
                'ipi_number' => $credit->pivot->ipi_number,
            ]);
        }
    }

    private function syncReleases(Track $target, Collection $releases, bool $replace): void
    {
        $current = $target->releases()->pluck('releases.id')->all();

        if ($replace) {
            $obsolete = array_diff($current, $releases->pluck('id')->all());
            if ($obsolete) $target->releases()->detach($obsolete);
        }

        foreach ($releases as $release) {
            if (in_array($release->id, $current)) continue; // keeps the target's own track number

            $target->releases()->attach($release->id, [
                'track_number' => null,
                'disc_number' => $release->pivot->disc_number ?? 1,
                'role' => $release->pivot->role ?? 'main',
            ]);
        }
    }
}
