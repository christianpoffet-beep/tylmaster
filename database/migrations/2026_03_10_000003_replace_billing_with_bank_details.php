<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contacts: remove billing address, add bank details
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['billing_company', 'billing_street', 'billing_zip', 'billing_city', 'billing_country']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('iban', 34)->nullable()->after('country');
            $table->string('bank_name')->nullable()->after('iban');
            $table->string('bank_zip', 20)->nullable()->after('bank_name');
            $table->string('bank_city')->nullable()->after('bank_zip');
            $table->string('bank_country')->nullable()->after('bank_city');
            $table->string('bic', 11)->nullable()->after('bank_country');
        });

        // Organizations: remove billing address, add bank details
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['billing_company', 'billing_street', 'billing_zip', 'billing_city', 'billing_country']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('iban', 34)->nullable()->after('country');
            $table->string('bank_name')->nullable()->after('iban');
            $table->string('bank_zip', 20)->nullable()->after('bank_name');
            $table->string('bank_city')->nullable()->after('bank_zip');
            $table->string('bank_country')->nullable()->after('bank_city');
            $table->string('bic', 11)->nullable()->after('bank_country');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['iban', 'bank_name', 'bank_zip', 'bank_city', 'bank_country', 'bic']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('billing_company')->nullable();
            $table->string('billing_street')->nullable();
            $table->string('billing_zip', 20)->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_country')->nullable();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['iban', 'bank_name', 'bank_zip', 'bank_city', 'bank_country', 'bic']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('billing_company')->nullable();
            $table->string('billing_street')->nullable();
            $table->string('billing_zip', 20)->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_country')->nullable();
        });
    }
};
