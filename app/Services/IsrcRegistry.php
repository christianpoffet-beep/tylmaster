<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Track;
use App\Models\TrackAudioFile;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * The ISRCs this admin hands out, and the ones it already knows.
 *
 * An ISRC is CC-XXX-YY-NNNNN: country, registrant, the two-digit year of
 * assignment and a five-digit designation code that starts again at 00001
 * every year. New codes continue after the highest one in use this year -
 * on tracks and on their audio versions alike, whatever catalogue they
 * belong to - and never fall back into a gap, since a code once handed out
 * may already be out in the world.
 *
 * Setting "isrc_numbering" can override the defaults: prefix (country and
 * registrant), and a start number for one year, below which no code is
 * handed out (codes assigned before this admin numbered them).
 */
class IsrcRegistry
{
    public const DEFAULTS = ['prefix' => 'CH481', 'start_year' => '26', 'start_number' => 22];

    /** The next free code for this year, e.g. CH4812600022 (normalized, no dashes). */
    public function next(array $taken = [], ?CarbonInterface $date = null): string
    {
        $config = array_merge(self::DEFAULTS, (array) Setting::get('isrc_numbering', []));
        $year = ($date ?? now())->format('y');
        $stem = strtoupper($config['prefix']) . $year;

        $highest = $this->knownCodes()
            ->pluck('isrc')
            ->merge(array_map(fn ($code) => Track::normalizeIsrc(is_string($code) ? $code : null), $taken))
            ->map(fn ($code) => preg_match('/^' . preg_quote($stem, '/') . '(\d{5})$/', (string) $code, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;

        $next = $highest + 1;
        if ($year === (string) $config['start_year']) {
            $next = max($next, (int) $config['start_number']);
        }

        if ($next > 99999) {
            throw new \OverflowException("Für {$stem} sind alle 99'999 Nummern vergeben.");
        }

        return $stem . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Where else a code is in use: other tracks and their audio versions. The
     * track's own records are left out - its main version usually carries
     * the track's ISRC.
     *
     * @return Collection<int, array{label: string, url: string}>
     */
    public function duplicates(?string $isrc, ?int $exceptTrackId = null): Collection
    {
        $code = Track::normalizeIsrc($isrc);
        if (!$code) return collect();

        return $this->knownCodes()
            ->filter(fn ($entry) => $entry['isrc'] === $code && $entry['track']->id !== $exceptTrackId)
            ->map(fn ($entry) => [
                'label' => $entry['label'],
                'url' => route('admin.tracks.show', $entry['track']),
            ])
            // One entry per track; its own ISRC comes first and stands for its versions
            ->unique(fn ($entry) => $entry['url'])
            ->values();
    }

    /** Every ISRC on a track or an audio version, normalized, with where it sits. */
    private function knownCodes(): Collection
    {
        $tracks = Track::whereNotNull('isrc')->where('isrc', '!=', '')->get(['id', 'title', 'version', 'isrc'])
            ->map(fn (Track $track) => [
                'isrc' => Track::normalizeIsrc($track->isrc),
                'track' => $track,
                'label' => '«' . $track->display_title . '»',
            ]);

        $versions = TrackAudioFile::with('track:id,title,version')
            ->whereNotNull('isrc')->where('isrc', '!=', '')
            ->get(['id', 'track_id', 'title', 'original_name', 'file_path', 'isrc'])
            ->filter(fn (TrackAudioFile $file) => $file->track)
            ->map(fn (TrackAudioFile $file) => [
                'isrc' => Track::normalizeIsrc($file->isrc),
                'track' => $file->track,
                'label' => '«' . $file->track->display_title . '» – Audioversion «' . $file->label . '»',
            ]);

        return $tracks->concat($versions)->values();
    }
}
