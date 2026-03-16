<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('language', 10)->default('de');
            $table->json('default_product_types')->nullable();
            $table->text('default_release_info')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default templates
        DB::table('product_templates')->insert([
            [
                'name' => 'Musikprodukt',
                'slug' => 'musikprodukt',
                'language' => 'de',
                'default_product_types' => json_encode(['Album (LP)']),
                'default_release_info' => '',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Merchandise',
                'slug' => 'merchandise',
                'language' => 'de',
                'default_product_types' => json_encode(['T-Shirt']),
                'default_release_info' => "Merchandise-Produkt\n\nMaterial: \nGrössen: S, M, L, XL, XXL\nFarben: \nDruck: \nVerpackung: \nLieferzeit: ",
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_templates');
    }
};
