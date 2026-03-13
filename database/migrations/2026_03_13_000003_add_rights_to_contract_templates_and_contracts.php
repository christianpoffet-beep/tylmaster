<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->json('rights')->nullable()->after('default_parties');
            $table->string('rights_label_a', 50)->nullable()->after('rights'); // e.g. "Urheber", "Künstler"
            $table->string('rights_label_b', 50)->nullable()->after('rights_label_a'); // e.g. "Verlag", "Label"
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->json('rights')->nullable()->after('territory');
            $table->string('rights_label_a', 50)->nullable()->after('rights');
            $table->string('rights_label_b', 50)->nullable()->after('rights_label_a');
        });
    }

    public function down(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropColumn(['rights', 'rights_label_a', 'rights_label_b']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['rights', 'rights_label_a', 'rights_label_b']);
        });
    }
};
