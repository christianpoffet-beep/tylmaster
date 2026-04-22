<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artwork_credits', function (Blueprint $table) {
            $table->string('ipi_number', 50)->nullable()->after('creditable_id');
        });

        Schema::table('artwork_credits', function (Blueprint $table) {
            $table->dropUnique('artwork_credits_unique');
        });

        Schema::table('artwork_credits', function (Blueprint $table) {
            $table->unique(['artwork_id', 'role', 'creditable_type', 'creditable_id', 'ipi_number'], 'artwork_credits_unique');
        });
    }

    public function down(): void
    {
        Schema::table('artwork_credits', function (Blueprint $table) {
            $table->dropUnique('artwork_credits_unique');
        });

        Schema::table('artwork_credits', function (Blueprint $table) {
            $table->dropColumn('ipi_number');
        });

        Schema::table('artwork_credits', function (Blueprint $table) {
            $table->unique(['artwork_id', 'role', 'creditable_type', 'creditable_id'], 'artwork_credits_unique');
        });
    }
};
