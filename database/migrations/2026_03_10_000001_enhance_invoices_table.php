<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('invoice_template_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('accounting_id')->nullable()->after('invoice_template_id')->constrained()->nullOnDelete();
            $table->string('recipient_name')->nullable()->after('contact_id');
            $table->text('recipient_address')->nullable()->after('recipient_name');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 8, 3)->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_template_id');
            $table->dropConstrainedForeignId('accounting_id');
            $table->dropColumn(['recipient_name', 'recipient_address']);
        });
    }
};
