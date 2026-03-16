<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_track', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->foreignId('track_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('track_number')->nullable();
            $table->unsignedSmallInteger('disc_number')->nullable()->default(1);
            $table->string('role', 50)->nullable();
            $table->timestamps();
            $table->unique(['release_id', 'track_id']);
        });

        // Migrate existing 1:N data to N:M pivot
        $tracks = DB::table('tracks')->whereNotNull('release_id')->orderBy('id')->get();
        $releaseCounters = [];
        foreach ($tracks as $track) {
            $rid = $track->release_id;
            if (!isset($releaseCounters[$rid])) {
                $releaseCounters[$rid] = 0;
            }
            $releaseCounters[$rid]++;
            DB::table('product_track')->insert([
                'release_id' => $rid,
                'track_id' => $track->id,
                'track_number' => $releaseCounters[$rid],
                'disc_number' => 1,
                'role' => 'main',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Drop old release_id FK and column
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropForeign(['release_id']);
            $table->dropColumn('release_id');
        });
    }

    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->foreignId('release_id')->nullable()->constrained()->nullOnDelete();
        });

        // Migrate back: pick first release for each track
        $pivots = DB::table('product_track')->orderBy('track_id')->orderBy('id')->get();
        $seen = [];
        foreach ($pivots as $pivot) {
            if (!in_array($pivot->track_id, $seen)) {
                DB::table('tracks')->where('id', $pivot->track_id)->update(['release_id' => $pivot->release_id]);
                $seen[] = $pivot->track_id;
            }
        }

        Schema::dropIfExists('product_track');
    }
};
