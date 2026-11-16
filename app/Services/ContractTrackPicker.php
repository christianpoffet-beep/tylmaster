<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\Track;
use Illuminate\Support\Collection;

/**
 * The data behind the track picker of contracts and contract templates.
 *
 * A contract is usually about a band, a label or a person, so a track has to
 * be found through all three, also when they are only known one step away:
 * the label and main artist of the track's products, and the members of its
 * bands, whose own credits are often not entered.
 */
class ContractTrackPicker
{
    /**
     * @param  iterable<int>  $selectedIds  the tracks already on the contract
     * @return array{selected: int[], tracks: array, bands: array, labels: array, people: array}
     */
    public static function config(iterable $selectedIds): array
    {
        $tracks = Track::with(array_merge(Track::SEARCH_RELATIONS, ['releases.organizations', 'organizations.contacts']))
            ->orderBy('title')
            ->get();

        $bands = collect();
        $labels = collect();
        $people = collect();

        $rows = $tracks->map(function (Track $track) use ($bands, $labels, $people) {
            $organizations = self::organizationsOf($track);
            $trackBands = $organizations->where('type', 'band')->values();
            $trackLabels = $organizations->where('type', 'label')->values();
            $members = $trackBands->flatMap(fn (Organization $band) => $band->contacts);
            $persons = $track->contacts->concat($members)->unique('id')->values();

            $trackBands->each(fn ($o) => $bands->put($o->id, $o->primary_name));
            $trackLabels->each(fn ($o) => $labels->put($o->id, $o->primary_name));
            $persons->each(fn (Contact $c) => $people->put($c->id, $c->full_name));

            // The track's own search, plus what it only reaches through its products and bands.
            $haystack = implode("\n", array_filter([
                $track->search_haystack,
                mb_strtolower($organizations->flatMap(fn ($o) => $o->names ?? [])->implode("\n")),
                mb_strtolower($members->map(fn ($c) => $c->full_name)->implode("\n")),
            ]));

            return [
                'id' => $track->id,
                'title' => $track->display_title,
                'bands' => $trackBands->map(fn ($o) => $o->primary_name)->implode(', '),
                'labels' => $trackLabels->map(fn ($o) => $o->primary_name)->implode(', '),
                'isrc' => $track->isrc_formatted,
                'bandIds' => $trackBands->pluck('id')->all(),
                'labelIds' => $trackLabels->pluck('id')->all(),
                'personIds' => $persons->pluck('id')->all(),
                'haystack' => $haystack,
            ];
        });

        $selected = collect($selectedIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        return [
            'selected' => $selected->filter(fn ($id) => $rows->contains('id', $id))->values()->all(),
            'tracks' => $rows->values()->all(),
            'bands' => self::options($bands),
            'labels' => self::options($labels),
            'people' => self::options($people),
        ];
    }

    /**
     * The track's own organizations and those of its products, each once.
     */
    protected static function organizationsOf(Track $track): Collection
    {
        return $track->organizations
            ->concat($track->releases->flatMap(fn ($release) => $release->organizations))
            ->unique('id')
            ->values();
    }

    /**
     * [id => name] as a list for a <select>, sorted by name.
     */
    protected static function options(Collection $names): array
    {
        return $names->filter()
            ->sortBy(fn ($name) => mb_strtolower($name))
            ->map(fn ($name, $id) => ['id' => $id, 'name' => $name])
            ->values()
            ->all();
    }
}
