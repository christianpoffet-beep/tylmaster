<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Audio of tracks and submissions: on the private "audio" disk
 * (storage/app/private/audio). The player streams it through an admin route.
 */
class AudioStorage extends PrivateStorage
{
    public const DISK = 'audio';

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
        return static::moveAll(collect(Storage::disk('public')->allFiles('tracks'))
            ->merge(static::column('track_audio_files', 'file_path'))
            ->merge(static::column('music_submissions', 'file_path'))
            ->merge(static::column('tracks', 'audio_file_path')));
    }
}
