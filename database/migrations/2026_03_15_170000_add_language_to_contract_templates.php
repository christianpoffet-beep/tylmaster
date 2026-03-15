<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->string('language', 2)->default('de')->after('contract_type_slug');
        });

        // Set existing templates to German
        \App\Models\ContractTemplate::query()->update(['language' => 'de']);
    }

    public function down(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};
