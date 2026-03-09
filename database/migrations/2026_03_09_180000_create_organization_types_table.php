<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('color', 60)->default('bg-gray-100 text-gray-600');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default types
        $types = [
            ['name' => 'Band', 'slug' => 'band', 'color' => 'bg-purple-100 text-purple-700', 'sort_order' => 1],
            ['name' => 'Label', 'slug' => 'label', 'color' => 'bg-blue-100 text-blue-700', 'sort_order' => 2],
            ['name' => 'Publishing', 'slug' => 'publishing', 'color' => 'bg-indigo-100 text-indigo-700', 'sort_order' => 3],
            ['name' => 'Location/Venue', 'slug' => 'venue', 'color' => 'bg-green-100 text-green-700', 'sort_order' => 4],
            ['name' => 'Veranstalter/Event/Festival', 'slug' => 'event_festival', 'color' => 'bg-yellow-100 text-yellow-700', 'sort_order' => 5],
            ['name' => 'Media', 'slug' => 'media', 'color' => 'bg-pink-100 text-pink-700', 'sort_order' => 6],
            ['name' => 'OMA-Kontakt', 'slug' => 'oma', 'color' => 'bg-gray-100 text-gray-600', 'sort_order' => 7],
        ];

        $now = now();
        foreach ($types as &$type) {
            $type['created_at'] = $now;
            $type['updated_at'] = $now;
        }

        DB::table('organization_types')->insert($types);
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_types');
    }
};
