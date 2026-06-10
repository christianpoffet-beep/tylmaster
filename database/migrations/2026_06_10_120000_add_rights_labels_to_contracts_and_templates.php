<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->json('rights_labels')->nullable()->after('rights_label_b');
        });
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->json('rights_labels')->nullable()->after('rights_label_b');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('rights_labels');
        });
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropColumn('rights_labels');
        });
    }
};