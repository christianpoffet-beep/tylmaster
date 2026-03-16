<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            // product_type becomes JSON array (multiple types)
            // Keep existing column, we'll migrate data
            $table->string('title_language', 10)->nullable()->after('title');
            $table->string('ean_upc', 30)->nullable()->after('title_language');
            $table->string('label_name')->nullable()->after('ean_upc');
            $table->string('main_artist')->nullable()->after('catalog_number');
            $table->text('release_info')->nullable()->after('description');
            $table->text('release_info_internal')->nullable()->after('release_info');
        });

        // Migrate existing UPC data to ean_upc
        DB::table('releases')->whereNotNull('upc')->get()->each(function ($r) {
            DB::table('releases')->where('id', $r->id)->update(['ean_upc' => $r->upc]);
        });

        // Convert product_type string to JSON array
        DB::table('releases')->whereNotNull('product_type')->get()->each(function ($r) {
            DB::table('releases')->where('id', $r->id)->update([
                'product_type' => json_encode([$r->product_type]),
            ]);
        });

        // Create artwork-release pivot
        Schema::create('artwork_release', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->constrained()->cascadeOnDelete();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['artwork_id', 'release_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artwork_release');

        Schema::table('releases', function (Blueprint $table) {
            $table->dropColumn([
                'title_language', 'ean_upc', 'label_name', 'main_artist',
                'release_info', 'release_info_internal',
            ]);
        });
    }
};
