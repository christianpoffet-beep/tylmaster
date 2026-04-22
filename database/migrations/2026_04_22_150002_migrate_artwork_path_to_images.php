<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: skip if artwork_images table doesn't exist yet (earlier migration failed)
        if (!Schema::hasTable('artwork_images')) {
            return;
        }

        $artworks = DB::table('artworks')->whereNotNull('artwork_path')->get();

        foreach ($artworks as $artwork) {
            // Skip if this artwork already has images (re-run safe)
            if (DB::table('artwork_images')->where('artwork_id', $artwork->id)->exists()) {
                continue;
            }

            $width = null;
            $height = null;
            $dpi = null;

            if ($artwork->artwork_path && Storage::disk('public')->exists($artwork->artwork_path)) {
                $fullPath = Storage::disk('public')->path($artwork->artwork_path);
                $info = @getimagesize($fullPath);
                if ($info) {
                    $width = $info[0] ?? null;
                    $height = $info[1] ?? null;
                }
                try {
                    $exif = @exif_read_data($fullPath);
                    if ($exif && isset($exif['XResolution'])) {
                        $xres = $exif['XResolution'];
                        if (is_string($xres) && str_contains($xres, '/')) {
                            [$num, $den] = explode('/', $xres, 2);
                            if ((int) $den > 0) {
                                $dpi = (int) ((int) $num / (int) $den);
                            }
                        } elseif (is_numeric($xres)) {
                            $dpi = (int) $xres;
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            DB::table('artwork_images')->insert([
                'artwork_id' => $artwork->id,
                'file_path' => $artwork->artwork_path,
                'file_size' => $artwork->artwork_file_size,
                'mime_type' => $artwork->artwork_mime_type,
                'original_name' => $artwork->artwork_original_name,
                'width' => $width,
                'height' => $height,
                'dpi' => $dpi,
                'purpose' => 'Master',
                'is_primary' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Mark first logo per artwork as primary for existing data (only where none is primary yet)
        if (Schema::hasColumn('artwork_logos', 'is_primary')) {
            $artworkIdsWithoutPrimary = DB::table('artwork_logos')
                ->select('artwork_id')
                ->groupBy('artwork_id')
                ->havingRaw('SUM(CASE WHEN is_primary = 1 THEN 1 ELSE 0 END) = 0')
                ->pluck('artwork_id');

            foreach ($artworkIdsWithoutPrimary as $artworkId) {
                $first = DB::table('artwork_logos')
                    ->where('artwork_id', $artworkId)
                    ->orderBy('id')
                    ->first();
                if ($first) {
                    DB::table('artwork_logos')
                        ->where('id', $first->id)
                        ->update(['is_primary' => true]);
                }
            }
        }
    }

    public function down(): void
    {
        // Non-destructive: keep legacy columns untouched. Just purge migrated rows.
        if (Schema::hasTable('artwork_images')) {
            DB::table('artwork_images')->delete();
        }
        if (Schema::hasColumn('artwork_logos', 'is_primary')) {
            DB::table('artwork_logos')->update(['is_primary' => false]);
        }
    }
};
