<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('debit_account_id')->nullable()->after('accounting_id')
                ->constrained('accounts')->nullOnDelete();
            $table->foreignId('credit_account_id')->nullable()->after('debit_account_id')
                ->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['debit_account_id']);
            $table->dropForeign(['credit_account_id']);
            $table->dropColumn(['debit_account_id', 'credit_account_id']);
        });
    }
};
