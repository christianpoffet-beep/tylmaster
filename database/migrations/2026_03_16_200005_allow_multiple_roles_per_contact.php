<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Recreate track_contact with id PK (SQLite can't add PK column)
        $existing = DB::table('track_contact')->get();
        Schema::dropIfExists('track_contact');
        Schema::create('track_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('artist');
            $table->string('instrument', 100)->nullable();
            $table->index(['track_id', 'contact_id']);
        });
        foreach ($existing as $row) {
            DB::table('track_contact')->insert([
                'track_id' => $row->track_id,
                'contact_id' => $row->contact_id,
                'role' => $row->role,
                'instrument' => $row->instrument ?? null,
            ]);
        }

        // Recreate release_contact with id PK
        $existing = DB::table('release_contact')->get();
        Schema::dropIfExists('release_contact');
        Schema::create('release_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('artist');
            $table->string('instrument', 100)->nullable();
            $table->index(['release_id', 'contact_id']);
        });
        foreach ($existing as $row) {
            DB::table('release_contact')->insert([
                'release_id' => $row->release_id,
                'contact_id' => $row->contact_id,
                'role' => $row->role,
                'instrument' => $row->instrument ?? null,
            ]);
        }
    }

    public function down(): void
    {
        // Keep first entry per pair, drop duplicates, restore composite PK
        $existing = DB::table('track_contact')->get();
        Schema::dropIfExists('track_contact');
        Schema::create('track_contact', function (Blueprint $table) {
            $table->foreignId('track_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('artist');
            $table->string('instrument', 100)->nullable();
            $table->primary(['track_id', 'contact_id']);
        });
        $seen = [];
        foreach ($existing as $row) {
            $key = $row->track_id . '-' . $row->contact_id;
            if (!in_array($key, $seen)) {
                DB::table('track_contact')->insert([
                    'track_id' => $row->track_id,
                    'contact_id' => $row->contact_id,
                    'role' => $row->role,
                    'instrument' => $row->instrument ?? null,
                ]);
                $seen[] = $key;
            }
        }

        $existing = DB::table('release_contact')->get();
        Schema::dropIfExists('release_contact');
        Schema::create('release_contact', function (Blueprint $table) {
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('artist');
            $table->string('instrument', 100)->nullable();
            $table->primary(['release_id', 'contact_id']);
        });
        $seen = [];
        foreach ($existing as $row) {
            $key = $row->release_id . '-' . $row->contact_id;
            if (!in_array($key, $seen)) {
                DB::table('release_contact')->insert([
                    'release_id' => $row->release_id,
                    'contact_id' => $row->contact_id,
                    'role' => $row->role,
                    'instrument' => $row->instrument ?? null,
                ]);
                $seen[] = $key;
            }
        }
    }
};
