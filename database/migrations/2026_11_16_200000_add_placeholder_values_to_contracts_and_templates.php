<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * «Vertragswerte»: the values a contract text refers to as {name} that the
     * contract data cannot provide itself (notice period, place of
     * jurisdiction …). A template carries the defaults, every contract its own
     * copy. Stored as a list of ['key' => …, 'value' => …].
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->json('placeholder_values')->nullable()->after('closing_note');
        });

        Schema::table('contract_templates', function (Blueprint $table) {
            $table->json('default_placeholder_values')->nullable()->after('default_closing_note');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('placeholder_values');
        });

        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropColumn('default_placeholder_values');
        });
    }
};
