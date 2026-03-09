<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_templates', function (Blueprint $table) {
            $table->foreignId('recipient_contact_id')->nullable()->after('organization_id')->constrained('contacts')->nullOnDelete();
            $table->foreignId('recipient_organization_id')->nullable()->after('recipient_contact_id')->constrained('organizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recipient_organization_id');
            $table->dropConstrainedForeignId('recipient_contact_id');
        });
    }
};
