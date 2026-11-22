<?php

namespace App\Services\Royalties;

use App\Models\Organization;
use App\Models\Release;
use App\Models\RoyaltyImport;
use App\Models\RoyaltyLine;
use App\Models\RoyaltyMatchRule;
use App\Models\Track;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Assigns usage lines to tracks.
 *
 * A "group" is everything a distributor reports under one ISRC - or, for the
 * few lines without one (album downloads), under one artist and title.
 * A group goes to the track assigned to it by hand earlier (a rule), else to
 * the one track carrying its ISRC. What stays open is assigned in the matcher;
 * that assignment becomes a rule for the next imports.
 */
class RoyaltyMatcher
{
    /** Assigns the open lines (of one import, or all of them); returns how many lines got a track. */
    public function apply(string $source, ?RoyaltyImport $import = null): int
    {
        $rules = RoyaltyMatchRule::where('source', $source)->get()->keyBy('match_key');
        $isrcs = $this->isrcIndex();
        $assigned = 0;

        $groups = RoyaltyLine::where('source', $source)
            ->whereNull('track_id')->whereNull('release_id')
            ->when($import, fn ($q) => $q->where('royalty_import_id', $import->id))
            ->select('isrc', 'artist', 'title')->distinct()->get();

        foreach ($groups as $group) {
            $key = self::key($group->isrc, $group->artist, $group->title);
            if ($rule = $rules->get($key)) {
                $target = ['track_id' => $rule->track_id, 'release_id' => $rule->release_id, 'match_type' => RoyaltyLine::MATCH_RULE];
            } elseif ($group->isrc && count($isrcs[$group->isrc] ?? []) === 1) {
                $target = ['track_id' => $isrcs[$group->isrc][0], 'release_id' => null, 'match_type' => RoyaltyLine::MATCH_ISRC];
            } else {
                continue;
            }

            $assigned += self::linesOf($source, $group->isrc, $group->artist, $group->title)
                ->whereNull('track_id')->whereNull('release_id')
                ->when($import, fn ($q) => $q->where('royalty_import_id', $import->id))
                ->update($target);
        }

        return $assigned;
    }

    /**
     * Assigns a group by hand - to a track or a product, or (both null) back
     * to "open" - and remembers it for the next imports.
     */
    public function assign(string $source, ?string $isrc, ?string $artist, ?string $title, ?int $trackId, ?int $releaseId): int
    {
        $key = self::key($isrc, $artist, $title);

        return DB::transaction(function () use ($source, $isrc, $artist, $title, $trackId, $releaseId, $key) {
            if ($trackId || $releaseId) {
                RoyaltyMatchRule::updateOrCreate(
                    ['source' => $source, 'match_key' => $key],
                    ['track_id' => $trackId, 'release_id' => $trackId ? null : $releaseId]
                );
            } else {
                RoyaltyMatchRule::where('source', $source)->where('match_key', $key)->delete();
            }

            $count = self::linesOf($source, $isrc, $artist, $title)->update([
                'track_id' => $trackId,
                'release_id' => $trackId ? null : $releaseId,
                'match_type' => ($trackId || $releaseId) ? RoyaltyLine::MATCH_MANUAL : null,
            ]);

            // Removed by hand: the ISRC may still point to a track
            if (!$trackId && !$releaseId) {
                $this->apply($source);
            }

            return $count;
        });
    }

    /**
     * Tracks (for lines with an ISRC) or products (album lines) whose title
     * is close to the reported one, best first; the band counts too.
     *
     * @return list<array{type: string, id: int, title: string, artist: string, isrc: ?string, score: int}>
     */
    public function suggestions(?string $isrc, ?string $artist, ?string $title, int $limit = 3): array
    {
        if (!$title) return [];

        $candidates = $isrc
            ? $this->tracks()->map(fn (Track $t) => [
                'type' => 'track', 'id' => $t->id, 'title' => $t->display_title, 'compare' => [$t->title, ...($t->alternative_titles ?? [])],
                'artist' => $t->organizations->where('type', 'band')->pluck('primary_name')->join(', '), 'isrc' => $t->isrc_formatted,
            ])
            : $this->releases()->map(fn (Release $r) => [
                'type' => 'release', 'id' => $r->id, 'title' => $r->title, 'compare' => [$r->title],
                'artist' => $r->organizations->where('type', 'band')->pluck('primary_name')->join(', ') ?: (string) $r->main_artist, 'isrc' => null,
            ]);

        return $candidates
            ->map(function ($candidate) use ($artist, $title) {
                $score = max(array_map(fn ($c) => self::titleScore($title, (string) $c), $candidate['compare']));
                if ($score >= 60 && $artist && $candidate['artist'] && self::titleScore($artist, $candidate['artist']) >= 80) {
                    $score += 15;
                }
                unset($candidate['compare']);

                return $candidate + ['score' => min(100, $score)];
            })
            ->filter(fn ($c) => $c['score'] >= 60)
            ->sortByDesc('score')->take($limit)->values()->all();
    }

    /**
     * Creates the track a group stands for: title and ISRC from the
     * distributor, linked to the band and the label of the same name.
     */
    public function createTrack(string $source, string $isrc, ?string $artist, string $title, ?string $label): Track
    {
        return DB::transaction(function () use ($source, $isrc, $artist, $title, $label) {
            $track = Track::create(['title' => $title, 'isrc' => Track::normalizeIsrc($isrc), 'status' => 'released']);

            $organizations = Organization::whereIn('type', ['band', 'label'])->get();
            $names = fn (?string $text) => collect(preg_split('#\s*/\s*#', (string) $text, -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn ($n) => mb_strtolower(trim($n)));
            $bands = $names($artist);
            $labels = $names($label);
            $ids = $organizations->filter(function (Organization $org) use ($bands, $labels) {
                $own = collect($org->names ?? [])->map(fn ($n) => mb_strtolower(trim($n)));
                $wanted = $org->type === 'band' ? $bands : $labels;

                return $own->intersect($wanted)->isNotEmpty();
            })->pluck('id');
            $track->organizations()->sync($ids->all());

            $this->assign($source, $isrc, $artist, $title, $track->id, null);

            return $track;
        });
    }

    /** The key of a group and of its rule */
    public static function key(?string $isrc, ?string $artist, ?string $title): string
    {
        return $isrc
            ? 'isrc:' . $isrc
            : 'title:' . mb_strtolower(trim((string) $artist)) . '|' . mb_strtolower(trim((string) $title));
    }

    /** The lines of a group, over all imports */
    public static function linesOf(string $source, ?string $isrc, ?string $artist, ?string $title): Builder
    {
        $query = RoyaltyLine::where('source', $source);

        return $isrc
            ? $query->where('isrc', $isrc)
            : $query->whereNull('isrc')->where('artist', $artist)->where('title', $title);
    }

    /**
     * 0-100: how close two titles are, ignoring case, accents and punctuation.
     * The same words count ("Song (Remastered)" contains "Song"); spelling only
     * counts as a typo ("Malestorm"/"Maelstrom"), so "Erster Song" is no match
     * for "Fremder Song".
     */
    public static function titleScore(string $a, string $b): int
    {
        $clean = fn ($s) => trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower(Str::ascii($s))));
        [$a, $b] = [$clean($a), $clean($b)];
        if ($a === '' || $b === '') return 0;
        if ($a === $b || str_replace(' ', '', $a) === str_replace(' ', '', $b)) return 100;

        [$short, $long] = strlen($a) <= strlen($b) ? [$a, $b] : [$b, $a];
        if (str_contains(" {$long} ", " {$short} ")) return 90;

        $wordsA = array_unique(explode(' ', $a));
        $wordsB = array_unique(explode(' ', $b));
        $words = (int) round(100 * count(array_intersect($wordsA, $wordsB)) / max(count($wordsA), count($wordsB)));

        $typo = 0;
        if (strlen($a) <= 255 && strlen($b) <= 255) {
            $typo = (int) round(100 * (1 - levenshtein($a, $b) / max(strlen($a), strlen($b))));
        }

        return max($words, $typo >= 85 ? $typo : 0);
    }

    /** @return array<string, list<int>> ISRC => tracks carrying it (on the track or one of its versions) */
    private function isrcIndex(): array
    {
        $index = [];
        $add = function (?string $isrc, int $trackId) use (&$index) {
            if (!$isrc = Track::normalizeIsrc($isrc)) return;
            if (!in_array($trackId, $index[$isrc] ?? [], true)) {
                $index[$isrc][] = $trackId;
            }
        };

        foreach (DB::table('tracks')->whereNotNull('isrc')->pluck('isrc', 'id') as $id => $isrc) {
            $add($isrc, (int) $id);
        }
        foreach (DB::table('track_audio_files')->whereNotNull('isrc')->get(['track_id', 'isrc']) as $file) {
            $add($file->isrc, (int) $file->track_id);
        }

        return $index;
    }

    private ?Collection $tracks = null;
    private ?Collection $releases = null;

    private function tracks(): Collection
    {
        return $this->tracks ??= Track::with('organizations')->get(['id', 'title', 'version', 'alternative_titles', 'isrc']);
    }

    private function releases(): Collection
    {
        return $this->releases ??= Release::with('organizations')->get(['id', 'title', 'main_artist']);
    }
}
