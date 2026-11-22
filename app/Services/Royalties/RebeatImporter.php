<?php

namespace App\Services\Royalties;

use App\Models\RoyaltyImport;
use App\Services\DocumentStorage;
use Illuminate\Support\Facades\DB;

/**
 * Stores the lines of a Rebeat export and assigns them to tracks.
 *
 * Lines carry Rebeat's own ID, so a line already imported (overlapping
 * exports) is skipped. Lines up to config('royalties.settled_until') were
 * settled by hand before the tool and are marked as such.
 *
 * A large export arrives in pieces (the browser sends it that way, see the
 * matcher page): start() opens the import, addPiece() stores and reads one
 * piece, finish() assigns the tracks. import() does all three for one file.
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

        return DB::transaction(function () use ($reader, $fileName, $storedPath) {
            $import = $this->start($fileName, $storedPath);
            $this->read($import, $reader);

            return $this->finish($import);
        });
    }

    /**
     * Opens an import that is filled piece by piece. Imports left unfinished
     * by an earlier attempt go first - their lines would count as "already
     * there" otherwise.
     */
    public function start(string $fileName, ?string $storedPath = null): RoyaltyImport
    {
        RoyaltyImport::where('source', self::SOURCE)->whereNull('completed_at')->get()
            ->each(fn (RoyaltyImport $stale) => $this->discard($stale));

        return RoyaltyImport::create([
            'source' => self::SOURCE,
            'file_name' => $fileName,
            'file_path' => $storedPath,
            'currency' => 'EUR',
        ]);
    }

    /**
     * One piece of the export, in the order of the file: appended to the
     * stored file and read. The first piece must hold Rebeat's header; the
     * others are read with it.
     */
    public function addPiece(RoyaltyImport $import, string $content): void
    {
        $file = tempnam(sys_get_temp_dir(), 'rebeat');
        file_put_contents($file, $content);

        try {
            $stored = DocumentStorage::path($import->file_path);
            $header = $import->upload_pieces > 0 && $stored ? RebeatReader::headerOf($stored) : null;
            if ($import->upload_pieces > 0 && $header === null) {
                throw new RoyaltyImportException('Der Anfang der Datei fehlt. Bitte den Import entfernen und nochmals hochladen.');
            }
            $reader = new RebeatReader($file, $header);

            DB::transaction(function () use ($import, $reader, $content, $stored) {
                if ($stored) {
                    file_put_contents($stored, $content, FILE_APPEND);
                } elseif ($import->file_path) {
                    DocumentStorage::disk()->put($import->file_path, $content);
                }
                $this->read($import, $reader);
                $import->increment('upload_pieces');
            });
        } finally {
            @unlink($file);
        }
    }

    /** Counts what came in and assigns the tracks; the import is complete then. */
    public function finish(RoyaltyImport $import): RoyaltyImport
    {
        if ($import->rows_new + $import->rows_duplicate === 0) {
            throw new RoyaltyImportException('Die Datei enthält keine Nutzungszeilen.');
        }

        $stats = $import->lines()->selectRaw('min(period) as period_from, max(period) as period_to, sum(case when presettled then 1 else 0 end) as presettled')->first();
        $import->update([
            'rows_presettled' => (int) $stats->presettled,
            'period_from' => $stats->period_from,
            'period_to' => $stats->period_to,
            'completed_at' => now(),
        ]);

        $this->matcher->apply(self::SOURCE, $import);

        return $import;
    }

    /** An import and everything that came with it */
    public function discard(RoyaltyImport $import): void
    {
        $import->lines()->delete();
        DocumentStorage::delete($import->file_path);
        $import->delete();
    }

    private function read(RoyaltyImport $import, RebeatReader $reader): void
    {
        $settledUntil = (string) config('royalties.settled_until');
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

        $import->increment('rows_new', $new);
        $import->increment('rows_duplicate', $read - $new);
    }
}
