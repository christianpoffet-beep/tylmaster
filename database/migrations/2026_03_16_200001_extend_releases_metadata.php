<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            $table->string('product_type', 50)->nullable()->after('title');
            $table->string('catalog_number', 50)->nullable()->after('label');
            $table->json('territory')->nullable()->after('catalog_number');
            $table->text('description')->nullable()->after('territory');
        });
    }

    public function down(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'catalog_number', 'territory', 'description']);
        });
    }
};
