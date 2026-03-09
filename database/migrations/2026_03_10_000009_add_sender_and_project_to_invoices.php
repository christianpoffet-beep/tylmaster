<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('sender_organization_id')
                ->nullable()
                ->after('organization_id')
                ->constrained('organizations')
                ->nullOnDelete();

            $table->foreignId('sender_contact_id')
                ->nullable()
                ->after('sender_organization_id')
                ->constrained('contacts')
                ->nullOnDelete();

            $table->foreignId('project_id')
                ->nullable()
                ->after('accounting_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['sender_organization_id']);
            $table->dropForeign(['sender_contact_id']);
            $table->dropForeign(['project_id']);
            $table->dropColumn(['sender_organization_id', 'sender_contact_id', 'project_id']);
        });
    }
};
