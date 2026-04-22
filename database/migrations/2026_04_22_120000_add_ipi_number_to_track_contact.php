<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('track_contact', 'ipi_number')) {
            Schema::table('track_contact', function (Blueprint $table) {
                $table->string('ipi_number', 50)->nullable()->after('instrument');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('track_contact', 'ipi_number')) {
            Schema::table('track_contact', function (Blueprint $table) {
                $table->dropColumn('ipi_number');
            });
        }
    }
};
