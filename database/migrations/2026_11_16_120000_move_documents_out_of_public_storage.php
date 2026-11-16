<?php

use App\Services\DocumentStorage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Contracts, invoices, QR codes, receipts and the other confidential
 * documents lay on the public disk - invoice PDFs even under guessable names
 * (documents/invoices/Rechnung-<number>.pdf). This moves them to the private
 * "documents" disk; the admin serves them to logged-in users only. Paths
 * stay the same, so no record changes. Photos, artworks, logos, covers,
 * avatars and branding stay public.
 *
 * Tests build their database with migrations; they must not move the real
 * files of the machine they run on (the moving itself is tested on its own).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) return;

        $moved = DocumentStorage::moveOutOfPublic();
        Log::info("Dokumente aus dem öffentlichen Speicher verschoben: {$moved} Dateien.");
    }

    public function down(): void
    {
        // The files stay protected; serving them from the public disk again is not wanted.
    }
};
