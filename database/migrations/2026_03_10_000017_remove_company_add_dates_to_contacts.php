<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('company');
            $table->date('birth_date')->nullable()->after('last_name');
            $table->date('death_date')->nullable()->after('birth_date');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('company')->nullable()->after('last_name');
            $table->dropColumn(['birth_date', 'death_date']);
        });
    }
};
