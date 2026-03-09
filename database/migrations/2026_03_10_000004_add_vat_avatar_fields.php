<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('vat_number', 50)->nullable()->after('bic');
            $table->string('avatar_path')->nullable()->after('vat_number');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('bic');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['vat_number', 'avatar_path']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};
