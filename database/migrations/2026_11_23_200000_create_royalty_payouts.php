<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payouts of balances to artists and labels: one run per transfer order
 * (the pain.001 file for the e-banking), one item per recipient with the
 * bank details as they were then. Each item is booked payables / bank and
 * lowers the recipient's balance (a ledger entry of the run).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_id')->nullable()->constrained()->nullOnDelete();
            $table->date('execution_date');
            $table->foreignId('bank_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('debtor_name');
            $table->string('debtor_iban', 34)->nullable();
            $table->string('message', 140)->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('royalty_payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('royalty_payout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('iban', 34)->nullable();
            $table->string('street')->nullable();
            $table->string('zip', 20)->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable();
            $table->decimal('amount', 12, 2);
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('royalty_ledger_entries', function (Blueprint $table) {
            $table->foreignId('royalty_payout_id')->nullable()->after('royalty_statement_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('royalty_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('royalty_payout_id');
        });
        Schema::dropIfExists('royalty_payout_items');
        Schema::dropIfExists('royalty_payouts');
    }
};
