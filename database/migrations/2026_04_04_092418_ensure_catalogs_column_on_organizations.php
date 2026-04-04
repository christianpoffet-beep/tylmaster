<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('organizations', 'catalogs')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->json('catalogs')->nullable()->after('type');
            });
        }
    }

    public function down(): void
    {
        // handled by original migration
    }
};
