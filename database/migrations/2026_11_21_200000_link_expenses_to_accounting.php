<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Expenses join the accounting.
 *
 * - accounting_id / account_id: where the cost is booked (an expense account
 *   such as 4008 Webhosting). The account takes over the free "category".
 * - booking_id: the booking that pays it. Paid by bank, the bank inbox makes
 *   that booking; paid in cash or privately, paid_from_account_id names the
 *   account (Kasse, Eigenkapital) and the expense books itself.
 * - created_from_bank: made automatically while booking a bank movement.
 * - notes: the old category texts are kept there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('accounting_id')->nullable()->after('contact_id')->constrained()->nullOnDelete();
            $table->foreignId('account_id')->nullable()->after('accounting_id')->constrained()->nullOnDelete();
            $table->foreignId('paid_from_account_id')->nullable()->after('account_id')->constrained('accounts')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->after('paid_from_account_id')->constrained()->nullOnDelete();
            $table->boolean('created_from_bank')->default(false)->after('booking_id');
            $table->text('notes')->nullable()->after('created_from_bank');
        });

        DB::table('expenses')->whereNotNull('category')->where('category', '!=', '')->orderBy('id')
            ->each(fn ($expense) => DB::table('expenses')->where('id', $expense->id)
                ->update(['notes' => 'Kategorie: ' . $expense->category]));

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('category', 100)->nullable();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_id');
            $table->dropConstrainedForeignId('paid_from_account_id');
            $table->dropConstrainedForeignId('account_id');
            $table->dropConstrainedForeignId('accounting_id');
            $table->dropColumn(['created_from_bank', 'notes']);
        });
    }
};
