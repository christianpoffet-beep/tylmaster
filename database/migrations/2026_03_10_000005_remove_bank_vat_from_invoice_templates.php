<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_templates', function (Blueprint $table) {
            $table->dropColumn(['iban', 'bank_name', 'bic', 'vat_number']);
        });
    }

    public function down(): void
    {
        Schema::table('invoice_templates', function (Blueprint $table) {
            $table->string('iban', 34)->after('logo_path');
            $table->string('bank_name')->nullable()->after('iban');
            $table->string('bic')->nullable()->after('bank_name');
            $table->string('vat_number')->nullable()->after('bic');
        });
    }
};
