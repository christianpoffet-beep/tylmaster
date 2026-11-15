<?php

use App\Services\AudioStorage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Audio files lay on the public disk - anyone with the link could play and
 * download them without logging in. This moves them to the private "audio"
 * disk (storage/app/private/audio); the admin serves them to logged-in users
 * only. Paths stay the same, so no record changes.
 *
 * Tests build their database with migrations; they must not move the real
 * files of the machine they run on, so there this does nothing (the moving
 * itself is tested on its own).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) return;

        $moved = AudioStorage::moveOutOfPublic();
        Log::info("Audio aus dem öffentlichen Speicher verschoben: {$moved} Dateien.");
    }

    public function down(): void
    {
        // The files stay protected; serving them from the public disk again is not wanted.
    }
};
