<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('notes')->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->after('contact_id')->constrained('organizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('contact_id');
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
