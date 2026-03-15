<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->text('biography')->nullable()->after('notes');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('biography');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('biography');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
