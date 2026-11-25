<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How the linked songs appear in the contract PDF: only the title, the title
 * with the people involved, or the full credits. Empty means the full
 * credits, as every contract printed them so far.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('track_details', 10)->nullable()->after('logo_as_watermark');
        });
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->string('track_details', 10)->nullable()->after('logo_as_watermark');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', fn (Blueprint $table) => $table->dropColumn('track_details'));
        Schema::table('contract_templates', fn (Blueprint $table) => $table->dropColumn('track_details'));
    }
};
