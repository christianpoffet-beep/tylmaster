<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Zession (advance payment / assignment)
            $table->boolean('has_zession')->default(false)->after('terms');
            $table->decimal('zession_amount', 12, 2)->nullable()->after('has_zession');
            $table->string('zession_currency', 3)->default('CHF')->after('zession_amount');
            $table->text('zession_notes')->nullable()->after('zession_currency');

            // Territory
            $table->json('territory')->nullable()->after('zession_notes');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['has_zession', 'zession_amount', 'zession_currency', 'zession_notes', 'territory']);
        });
    }
};
