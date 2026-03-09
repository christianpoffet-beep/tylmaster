<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('company_name');
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('zip', 10);
            $table->string('city');
            $table->string('country', 2)->default('CH');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('iban', 34);
            $table->string('bank_name')->nullable();
            $table->string('bic')->nullable();
            $table->string('vat_number')->nullable();
            $table->text('footer_text')->nullable();
            $table->smallInteger('payment_terms_days')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_templates');
    }
};
