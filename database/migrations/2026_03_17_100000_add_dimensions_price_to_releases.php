<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            $table->decimal('width', 8, 2)->nullable()->after('release_info_internal');
            $table->decimal('height', 8, 2)->nullable()->after('width');
            $table->decimal('depth', 8, 2)->nullable()->after('height');
            $table->decimal('weight', 8, 2)->nullable()->after('depth');
            $table->decimal('price', 10, 2)->nullable()->after('weight');
            $table->string('currency', 5)->nullable()->default('CHF')->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            $table->dropColumn(['width', 'height', 'depth', 'weight', 'price', 'currency']);
        });
    }
};
