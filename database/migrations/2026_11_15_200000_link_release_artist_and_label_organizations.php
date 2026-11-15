<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Hauptkünstler" and "Label Name" on products were free text; the form now
 * links a band and a label organization instead. This links each product to
 * the organizations its text names - the whole text, or each part of a list
 * like "A, B". Text that was fully matched gives way; text with a name that
 * has no organization stays, so the form can show it until someone links one.
 *
 * Only ever adds links, so it is safe to run again.
 */
return new class extends Migration
{
    private const COLUMNS = ['main_artist' => 'band', 'label_name' => 'label'];

    public function up(): void
    {
        $namesByType = [];
        foreach (DB::table('organizations')->whereIn('type', array_values(self::COLUMNS))->orderBy('id')->get(['id', 'type', 'names']) as $org) {
            foreach ((array) json_decode((string) $org->names, true) as $name) {
                $key = $this->key($name);
                if ($key !== '') $namesByType[$org->type][$key] ??= $org->id;
            }
        }

        foreach (DB::table('releases')->get(['id', 'main_artist', 'label_name']) as $release) {
            $clear = [];

            foreach (self::COLUMNS as $column => $type) {
                $text = trim((string) $release->{$column});
                if ($text === '') continue;

                $known = $namesByType[$type] ?? [];
                $parts = isset($known[$this->key($text)]) ? [$text] : preg_split('/\s*[,;\/]\s*/u', $text, -1, PREG_SPLIT_NO_EMPTY);
                $ids = array_map(fn ($part) => $known[$this->key($part)] ?? null, $parts);

                foreach (array_unique(array_filter($ids)) as $orgId) {
                    DB::table('organization_release')->insertOrIgnore(['organization_id' => $orgId, 'release_id' => $release->id]);
                }

                if (!in_array(null, $ids, true)) $clear[$column] = null;
            }

            if ($clear) DB::table('releases')->where('id', $release->id)->update($clear);
        }
    }

    public function down(): void
    {
        // The links stay; the cleared text was the same names.
    }

    private function key(mixed $name): string
    {
        return mb_strtolower(trim((string) $name));
    }
};
