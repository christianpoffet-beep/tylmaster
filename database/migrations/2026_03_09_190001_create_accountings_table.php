<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accountings', function (Blueprint $table) {
            $table->id();
            $table->morphs('accountable'); // accountable_type + accountable_id
            $table->string('name');
            $table->string('currency', 3)->default('CHF');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('open'); // open, closed
            $table->foreignId('chart_template_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_id')->constrained()->cascadeOnDelete();
            $table->string('number', 10);
            $table->string('name');
            $table->string('type'); // asset, liability, income, expense
            $table->boolean('is_header')->default(false);
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_id')->constrained()->cascadeOnDelete();
            $table->date('booking_date');
            $table->string('reference')->nullable();
            $table->string('description');
            $table->foreignId('debit_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('credit_account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('accountings');
    }
};
