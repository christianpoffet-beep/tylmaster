<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('last_name');
            $table->string('nationality')->nullable()->after('country');
            $table->string('ahv_number', 16)->nullable()->after('nationality');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('legal_form')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['gender', 'nationality', 'ahv_number']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('legal_form');
        });
    }
};
