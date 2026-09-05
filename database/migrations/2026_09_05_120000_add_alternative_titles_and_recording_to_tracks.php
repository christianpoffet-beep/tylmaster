<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->json('alternative_titles')->nullable()->after('version');
            $table->string('recording_location')->nullable()->after('musical_key');
            $table->string('recording_years', 50)->nullable()->after('recording_location');
        });
    }

    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn(['alternative_titles', 'recording_location', 'recording_years']);
        });
    }
};
