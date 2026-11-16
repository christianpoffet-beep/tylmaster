<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Files that must not be reachable from the web: a private disk outside the
 * web root, without URLs. The admin hands them out to logged-in users only.
 *
 * They used to lie on the "public" disk, where anyone with the link got them
 * under /storage/... moveAll() brings them over, keeping their relative path,
 * and a file that still turns up on the public disk moves over the first
 * time it is asked for.
 *
 * Each kind of file names its own disk: AudioStorage, DocumentStorage.
 */
abstract class PrivateStorage
{
    public const DISK = '';

    public static function disk(): Filesystem
    {
        return Storage::disk(static::DISK);
    }

    /** Absolute path of a stored file, or null when there is none. */
    public static function path(?string $relative): ?string
    {
        if (!static::isSafe($relative)) return null;

        if (static::disk()->exists($relative) || static::moveFromPublic($relative)) {
            return static::disk()->path($relative);
        }

        return null;
    }

    public static function delete(?string $relative): void
    {
        if (!static::isSafe($relative)) return;

        static::disk()->delete($relative);
        Storage::disk('public')->delete($relative);
    }

    /** File responses are "public" by default; these may sit in no cache at all. */
    public static function privately(BinaryFileResponse $response): BinaryFileResponse
    {
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    /** @return int how many of the given files were moved off the public disk */
    protected static function moveAll(iterable $paths): int
    {
        $moved = 0;
        foreach (collect($paths)->filter(fn ($path) => static::isSafe($path))->unique() as $path) {
            if (static::moveFromPublic($path)) $moved++;
        }

        return $moved;
    }

    /** Moves one file from the public disk over; true when it is now on the private disk. */
    protected static function moveFromPublic(string $relative): bool
    {
        $public = Storage::disk('public');
        if (!$public->exists($relative)) return false;

        // Moved before, and a copy was left behind: the public one goes
        if (static::disk()->exists($relative)) {
            $public->delete($relative);

            return true;
        }

        $source = $public->path($relative);
        $target = static::disk()->path($relative);
        File::ensureDirectoryExists(dirname($target));

        // Same file system: a rename, instant even for big files. Else copy, then delete.
        if (!@rename($source, $target)) {
            if (!@copy($source, $target)) return false;
            $public->delete($relative);
        }

        return true;
    }

    protected static function column(string $table, string $column, ?callable $scope = null): array
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) return [];

        $query = DB::table($table)->whereNotNull($column)->where($column, '!=', '');
        if ($scope) $scope($query);

        return $query->pluck($column)->all();
    }

    protected static function isSafe(?string $relative): bool
    {
        return is_string($relative) && $relative !== '' && !str_contains($relative, '..') && !str_starts_with($relative, '/');
    }
}
