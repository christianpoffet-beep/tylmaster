<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('rights_label_b');
            $table->boolean('logo_in_header')->default(false)->after('logo_path');
            $table->boolean('logo_as_watermark')->default(false)->after('logo_in_header');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'logo_in_header', 'logo_as_watermark']);
        });
    }
};