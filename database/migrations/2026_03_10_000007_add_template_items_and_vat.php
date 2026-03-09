<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Template default items
        Schema::create('invoice_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_template_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 8, 3)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // VAT rate on template
        Schema::table('invoice_templates', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->nullable()->after('use_avatar_as_logo');
        });

        // VAT on invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('vat_rate');
        });

        Schema::table('invoice_templates', function (Blueprint $table) {
            $table->dropColumn('vat_rate');
        });

        Schema::dropIfExists('invoice_template_items');
    }
};
