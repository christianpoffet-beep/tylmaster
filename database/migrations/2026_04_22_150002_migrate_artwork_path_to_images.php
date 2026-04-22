<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $artworks = DB::table('artworks')->whereNotNull('artwork_path')->get();

        foreach ($artworks as $artwork) {
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

        // Mark first logo per artwork as primary for existing data
        $logosGrouped = DB::table('artwork_logos')
            ->orderBy('artwork_id')
            ->orderBy('id')
            ->get()
            ->groupBy('artwork_id');

        foreach ($logosGrouped as $artworkId => $logos) {
            $firstLogo = $logos->first();
            if ($firstLogo) {
                DB::table('artwork_logos')
                    ->where('id', $firstLogo->id)
                    ->update(['is_primary' => true]);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive: keep legacy columns untouched. Just purge migrated rows.
        DB::table('artwork_images')->delete();
        DB::table('artwork_logos')->update(['is_primary' => false]);
    }
};
