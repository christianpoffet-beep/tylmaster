<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // Seed with default credit roles and instruments from Track model constants
        DB::table('settings')->insert([
            [
                'key' => 'credit_roles',
                'value' => json_encode(\App\Models\Track::CREDIT_ROLES),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'instruments',
                'value' => json_encode(\App\Models\Track::INSTRUMENTS),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
