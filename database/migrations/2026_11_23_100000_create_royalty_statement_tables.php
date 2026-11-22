<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The statements to artists and labels:
 * - royalty_payments: what the distributor paid out (CHF, with the EUR it
 *   was); a statement distributes the payments of its period.
 * - royalty_statements: one run (usually on 15 December) over a period of
 *   usage; a draft is computed live, a final one is booked.
 * - royalty_statement_items: what a final statement gave whom, per contract
 *   and track (or product), with the advances recouped from it.
 * - royalty_ledger_entries: the balance of each contact and organization -
 *   credits from statements, later payouts and corrections.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_statements', function (Blueprint $table) {
            $table->id();
            $table->string('source', 30)->default('rebeat');
            $table->string('title');
            $table->date('statement_date');
            $table->date('period_from');
            $table->date('period_to');
            $table->foreignId('accounting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 10)->default('draft');
            // Flat shares booked onto the payables before (2023-2025): only the difference is booked
            $table->decimal('pre_booked', 12, 2)->default(0);
            // Chosen accounts: expense, payable, recouped, labels {organization_id: account_id}
            $table->json('accounts')->nullable();
            $table->decimal('payments_amount', 12, 2)->nullable();
            $table->decimal('usage_amount', 14, 6)->nullable();
            $table->decimal('factor', 14, 8)->nullable();
            $table->json('booking_ids')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('royalty_payments', function (Blueprint $table) {
            $table->id();
            $table->string('source', 30)->default('rebeat');
            $table->date('paid_at');
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_foreign', 12, 2)->nullable();
            $table->string('currency_foreign', 3)->nullable();
            $table->decimal('rate', 12, 6)->nullable();
            $table->string('text')->nullable();
            // bank = from a bank import of an accounting, file = from an uploaded statement, manual
            $table->string('origin', 10);
            $table->foreignId('bank_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fingerprint', 64)->unique();
            $table->foreignId('royalty_statement_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('royalty_statement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('royalty_statement_id')->constrained()->cascadeOnDelete();
            // artist and label are credited; own stays income; none = no contract, stays too
            $table->string('role', 10);
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('release_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('track_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity')->default(0);
            $table->decimal('usage_amount', 14, 6)->default(0);
            $table->decimal('base_amount', 12, 4)->default(0);
            $table->decimal('percent', 6, 2)->default(0);
            $table->decimal('amount', 12, 4)->default(0);
            $table->decimal('recouped', 12, 4)->default(0);
            $table->index(['royalty_statement_id', 'role']);
        });

        Schema::create('royalty_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->date('entry_date');
            $table->string('type', 12); // credit, payout, correction
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->foreignId('royalty_statement_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'contact_id']);
        });

        Schema::table('royalty_lines', function (Blueprint $table) {
            $table->foreignId('royalty_statement_id')->nullable()->after('presettled')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('royalty_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('royalty_statement_id');
        });
        Schema::dropIfExists('royalty_ledger_entries');
        Schema::dropIfExists('royalty_statement_items');
        Schema::dropIfExists('royalty_payments');
        Schema::dropIfExists('royalty_statements');
    }
};
