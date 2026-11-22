<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reading bank statements (CSV from the e-banking) into an accounting.
 *
 * - accounts.iban: the bank account a statement belongs to, remembered on the
 *   first import so a file never lands on the wrong account.
 * - bank_imports: one row per uploaded file, with the bank's opening and
 *   closing balance for the reconciliation.
 * - bank_transactions: the inbox. Each movement waits there until it is booked
 *   or ignored; the fingerprint keeps overlapping exports from doubling up.
 * - booking_rules: "text contains X -> account Y", per owner of the accounting
 *   (contact or organization) so they carry over from year to year. The
 *   account is kept as its number because every year has its own accounts.
 * - bookings.bank_transaction_id / from_bank_import: which movement a booking
 *   settles, and whether the import created it (or only linked one that was
 *   already there).
 * - bookings.invoice_role: "claim" or "payment". Until now that was read off
 *   the description ("(Eingang)", "(Zahlung)"), which a changed text breaks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('iban', 34)->nullable()->after('name');
        });

        Schema::create('bank_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('format', 30);
            $table->string('iban', 34)->nullable();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->date('statement_from')->nullable();
            $table->date('statement_to')->nullable();
            $table->decimal('opening_balance', 12, 2)->nullable();
            $table->decimal('closing_balance', 12, 2)->nullable();
            $table->unsignedInteger('rows_new')->default(0);
            $table->unsignedInteger('rows_duplicate')->default(0);
            $table->unsignedInteger('rows_outside')->default(0);
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_import_id')->nullable()->constrained()->nullOnDelete();
            $table->date('booking_date');
            $table->date('value_date')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2)->nullable();
            $table->string('text');
            $table->text('details')->nullable();
            $table->string('fingerprint', 64);
            $table->string('status', 20)->default('open'); // open, booked, ignored
            $table->timestamps();

            $table->unique(['accounting_id', 'fingerprint']);
            $table->index(['accounting_id', 'status']);
        });

        Schema::create('booking_rules', function (Blueprint $table) {
            $table->id();
            $table->morphs('accountable');
            $table->string('pattern');
            $table->string('direction', 3)->default('any'); // in, out, any
            $table->string('account_number', 10);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('invoice_role', 10)->nullable()->after('invoice_id');
            $table->foreignId('bank_transaction_id')->nullable()->after('invoice_role')
                ->constrained()->nullOnDelete();
            $table->boolean('from_bank_import')->default(false)->after('bank_transaction_id');
        });

        // Same reading as the old code: a payment says so in its description
        DB::table('bookings')->whereNotNull('invoice_id')
            ->where(fn ($q) => $q->where('description', 'like', '%(Eingang)%')->orWhere('description', 'like', '%(Zahlung)%'))
            ->update(['invoice_role' => 'payment']);
        DB::table('bookings')->whereNotNull('invoice_id')->whereNull('invoice_role')
            ->update(['invoice_role' => 'claim']);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_transaction_id');
            $table->dropColumn(['invoice_role', 'from_bank_import']);
        });
        Schema::dropIfExists('booking_rules');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_imports');
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('iban');
        });
    }
};
