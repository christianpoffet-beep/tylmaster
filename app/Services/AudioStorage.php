<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Where the audio of tracks and submissions lives: on the "audio" disk,
 * outside the web root (storage/app/private/audio). Nothing there has a
 * public URL - the admin hands files out to logged-in users only.
 *
 * Until November 2026 the files lay on the "public" disk, reachable without
 * login under /storage/... moveOutOfPublic() brings them over, keeping their
 * relative path. A file that still turns up on the public disk (dropped
 * there by something else) moves over the first time it is asked for.
 */
class AudioStorage
{
    public const DISK = 'audio';

    public static function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    /** Absolute path of a stored audio file, or null when there is none. */
    public static function path(?string $relative): ?string
    {
        if (!self::isSafe($relative)) return null;

        if (self::disk()->exists($relative) || self::moveFromPublic($relative)) {
            return self::disk()->path($relative);
        }

        return null;
    }

    /** Downloads are "public" by default; these may sit in no cache at all. */
    public static function privately(BinaryFileResponse $response): BinaryFileResponse
    {
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public static function delete(?string $relative): void
    {
        if (!self::isSafe($relative)) return;

        self::disk()->delete($relative);
        Storage::disk('public')->delete($relative);
    }

    /**
     * Moves every audio file off the public disk: everything under tracks/
     * (the uploads of the track form, files of deleted tracks included) and
     * whatever a version, a submission or the former single-file column of a
     * track points at.
     *
     * @return int how many files were moved
     */
    public static function moveOutOfPublic(): int
    {
        $public = Storage::disk('public');

        $paths = collect($public->allFiles('tracks'))
            ->merge(self::column('track_audio_files', 'file_path'))
            ->merge(self::column('music_submissions', 'file_path'))
            ->merge(self::column('tracks', 'audio_file_path'))
            ->filter(fn ($path) => self::isSafe($path))
            ->unique();

        $moved = 0;
        foreach ($paths as $path) {
            if (self::moveFromPublic($path)) $moved++;
        }

        return $moved;
    }

    /** Moves one file from the public disk over; true when it is now on the audio disk. */
    private static function moveFromPublic(string $relative): bool
    {
        $public = Storage::disk('public');
        if (!$public->exists($relative)) return false;

        // Moved before, and a copy was left behind: the public one goes
        if (self::disk()->exists($relative)) {
            $public->delete($relative);

            return true;
        }

        $source = $public->path($relative);
        $target = self::disk()->path($relative);
        File::ensureDirectoryExists(dirname($target));

        // Same file system: a rename, instant even for big files. Else copy, then delete.
        if (!@rename($source, $target)) {
            if (!@copy($source, $target)) return false;
            $public->delete($relative);
        }

        return true;
    }

    private static function column(string $table, string $column): array
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) return [];

        return DB::table($table)->whereNotNull($column)->where($column, '!=', '')->pluck($column)->all();
    }

    private static function isSafe(?string $relative): bool
    {
        return is_string($relative) && $relative !== '' && !str_contains($relative, '..') && !str_starts_with($relative, '/');
    }
}
