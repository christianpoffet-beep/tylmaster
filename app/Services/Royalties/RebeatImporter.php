<?php

namespace App\Services\Royalties;

use App\Models\RoyaltyImport;
use Illuminate\Support\Facades\DB;

/**
 * Stores the lines of a Rebeat export and assigns them to tracks.
 *
 * Lines carry Rebeat's own ID, so a line already imported (overlapping
 * exports) is skipped. Lines up to config('royalties.settled_until') were
 * settled by hand before the tool and are marked as such.
 */
class RebeatImporter
{
    public const SOURCE = 'rebeat';

    /** Rows per INSERT - stays below SQLite's limit of bound values */
    private const BATCH = 500;

    public function __construct(private RoyaltyMatcher $matcher)
    {
    }

    public function import(string $path, string $fileName, ?string $storedPath = null): RoyaltyImport
    {
        $reader = new RebeatReader($path);
        $settledUntil = (string) config('royalties.settled_until');

        return DB::transaction(function () use ($reader, $fileName, $storedPath, $settledUntil) {
            $import = RoyaltyImport::create([
                'source' => self::SOURCE,
                'file_name' => $fileName,
                'file_path' => $storedPath,
                'currency' => 'EUR',
            ]);

            $read = 0;
            $new = 0;
            $batch = [];
            $flush = function () use (&$batch, &$new) {
                if ($batch) {
                    $new += DB::table('royalty_lines')->insertOrIgnore($batch);
                    $batch = [];
                }
            };

            foreach ($reader->rows() as $row) {
                $read++;
                $batch[] = $row + [
                    'royalty_import_id' => $import->id,
                    'source' => self::SOURCE,
                    'presettled' => $settledUntil !== '' && $row['period'] <= $settledUntil,
                ];
                if (count($batch) >= self::BATCH) {
                    $flush();
                }
            }
            $flush();

            if ($read === 0) {
                throw new RoyaltyImportException('Die Datei enthält keine Nutzungszeilen.');
            }

            $stats = $import->lines()->selectRaw('min(period) as period_from, max(period) as period_to, sum(case when presettled then 1 else 0 end) as presettled')->first();
            $import->update([
                'rows_new' => $new,
                'rows_duplicate' => $read - $new,
                'rows_presettled' => (int) $stats->presettled,
                'period_from' => $stats->period_from,
                'period_to' => $stats->period_to,
            ]);

            $this->matcher->apply(self::SOURCE, $import);

            return $import;
        });
    }
}
