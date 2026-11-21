<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A new fiscal year opens from the end of the previous one: the accounting
 * remembers that year (to compare the opening balances with its closing
 * balances later on) and the equity account that took its profit or loss.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accountings', function (Blueprint $table) {
            $table->foreignId('previous_accounting_id')->nullable()->after('chart_template_id')
                ->constrained('accountings')->nullOnDelete();
            // No constraint: accounts cascade from accountings, a key back would make a cycle
            $table->unsignedBigInteger('profit_account_id')->nullable()->after('previous_accounting_id');
        });
    }

    public function down(): void
    {
        Schema::table('accountings', function (Blueprint $table) {
            $table->dropColumn('profit_account_id');
            $table->dropConstrainedForeignId('previous_accounting_id');
        });
    }
};
