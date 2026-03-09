<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add billing address to contacts
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('billing_company')->nullable()->after('country');
            $table->string('billing_street')->nullable()->after('billing_company');
            $table->string('billing_zip')->nullable()->after('billing_street');
            $table->string('billing_city')->nullable()->after('billing_zip');
            $table->string('billing_country')->nullable()->after('billing_city');
        });

        // Add address + billing address to organizations
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('street')->nullable()->after('websites');
            $table->string('zip')->nullable()->after('street');
            $table->string('city')->nullable()->after('zip');
            $table->string('country')->nullable()->after('city');
            $table->string('email')->nullable()->after('country');
            $table->string('phone')->nullable()->after('email');
            $table->string('billing_company')->nullable()->after('phone');
            $table->string('billing_street')->nullable()->after('billing_company');
            $table->string('billing_zip')->nullable()->after('billing_street');
            $table->string('billing_city')->nullable()->after('billing_zip');
            $table->string('billing_country')->nullable()->after('billing_city');
        });

        // Simplify invoice_templates: remove address fields, add contact/organization FK
        Schema::table('invoice_templates', function (Blueprint $table) {
            $table->foreignId('contact_id')->nullable()->after('slug')->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->after('contact_id')->constrained()->nullOnDelete();
            $table->dropColumn([
                'company_name', 'address_line1', 'address_line2',
                'zip', 'city', 'country',
                'email', 'phone', 'website',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('invoice_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_id');
            $table->dropConstrainedForeignId('organization_id');
            $table->string('company_name')->after('slug');
            $table->string('address_line1')->after('company_name');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('zip', 10)->after('address_line2');
            $table->string('city')->after('zip');
            $table->string('country', 2)->default('CH')->after('city');
            $table->string('email')->nullable()->after('country');
            $table->string('phone')->nullable()->after('email');
            $table->string('website')->nullable()->after('phone');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'street', 'zip', 'city', 'country', 'email', 'phone',
                'billing_company', 'billing_street', 'billing_zip', 'billing_city', 'billing_country',
            ]);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn([
                'billing_company', 'billing_street', 'billing_zip', 'billing_city', 'billing_country',
            ]);
        });
    }
};
