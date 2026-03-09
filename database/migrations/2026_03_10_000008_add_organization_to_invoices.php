<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('contact_id')->constrained()->nullOnDelete();
            $table->dropColumn(['recipient_name', 'recipient_address']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->string('recipient_name')->nullable()->after('contact_id');
            $table->text('recipient_address')->nullable()->after('recipient_name');
        });
    }
};
