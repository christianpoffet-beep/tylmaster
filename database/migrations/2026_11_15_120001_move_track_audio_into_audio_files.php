<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Carries the single audio file of every existing track over into
 * track_audio_files, as that track's main version.
 *
 * The old columns on tracks (audio_file_path, bitrate, ...) stay untouched as
 * a safety net; the app no longer reads them. Re-run safe: a track that
 * already has audio files is skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('track_audio_files') || !Schema::hasColumn('tracks', 'audio_file_path')) {
            return;
        }

        $tracks = DB::table('tracks')->whereNotNull('audio_file_path')->where('audio_file_path', '!=', '')->get();

        foreach ($tracks as $track) {
            if (DB::table('track_audio_files')->where('track_id', $track->id)->exists()) {
                continue;
            }

            $disk = Storage::disk('public');
            $exists = $disk->exists($track->audio_file_path);

            DB::table('track_audio_files')->insert([
                'track_id' => $track->id,
                'file_path' => $track->audio_file_path,
                'original_name' => basename($track->audio_file_path),
                'file_size' => $exists ? $disk->size($track->audio_file_path) : null,
                'is_main' => true,
                'audio_format' => $track->audio_format ?: strtolower(pathinfo($track->audio_file_path, PATHINFO_EXTENSION)) ?: null,
                // The track's duration was read from this very file on upload.
                'duration_seconds' => $track->duration_seconds,
                'bitrate' => $track->bitrate,
                'sample_rate' => $track->sample_rate,
                'channels' => $track->channels,
                'sort_order' => 0,
                'created_at' => $track->updated_at ?? now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // The old columns were never cleared, so there is nothing to restore.
        if (Schema::hasTable('track_audio_files')) {
            DB::table('track_audio_files')->delete();
        }
    }
};
