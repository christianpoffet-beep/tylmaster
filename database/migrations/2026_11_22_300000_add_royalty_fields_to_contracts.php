<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the statements to the artists need from a contract:
 * - supersedes_id: an amendment is a new contract that replaces the old one
 *   from its start date; the statement takes the one valid in each month.
 * - contract_advances: advances and costs the label paid, recouped from the
 *   artists' shares of this contract before anything is credited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('supersedes_id')->nullable()->after('end_date')->constrained('contracts')->nullOnDelete();
        });

        Schema::create('contract_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('CHF');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_advances');
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supersedes_id');
        });
    }
};
