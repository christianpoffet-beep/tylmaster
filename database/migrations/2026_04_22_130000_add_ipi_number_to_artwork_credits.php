<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('artwork_credits', 'ipi_number')) {
            Schema::table('artwork_credits', function (Blueprint $table) {
                $table->string('ipi_number', 50)->nullable()->after('creditable_id');
            });
        }

        // Drop the old unique index if it still exists in its original shape.
        // Wrapped in try/catch because different MySQL/SQLite versions throw
        // different errors when the index shape doesn't match.
        try {
            Schema::table('artwork_credits', function (Blueprint $table) {
                $table->dropUnique('artwork_credits_unique');
            });
        } catch (\Throwable $e) {
            // already dropped or missing — ignore
        }

        try {
            Schema::table('artwork_credits', function (Blueprint $table) {
                $table->unique(
                    ['artwork_id', 'role', 'creditable_type', 'creditable_id', 'ipi_number'],
                    'artwork_credits_unique'
                );
            });
        } catch (\Throwable $e) {
            // unique already present — ignore
        }
    }

    public function down(): void
    {
        try {
            Schema::table('artwork_credits', function (Blueprint $table) {
                $table->dropUnique('artwork_credits_unique');
            });
        } catch (\Throwable $e) {
        }

        if (Schema::hasColumn('artwork_credits', 'ipi_number')) {
            Schema::table('artwork_credits', function (Blueprint $table) {
                $table->dropColumn('ipi_number');
            });
        }

        try {
            Schema::table('artwork_credits', function (Blueprint $table) {
                $table->unique(
                    ['artwork_id', 'role', 'creditable_type', 'creditable_id'],
                    'artwork_credits_unique'
                );
            });
        } catch (\Throwable $e) {
        }
    }
};
