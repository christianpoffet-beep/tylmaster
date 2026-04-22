<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artwork_logos', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('comment');
            $table->string('purpose', 100)->nullable()->after('is_primary');
            $table->unsignedInteger('sort_order')->default(0)->after('purpose');
        });
    }

    public function down(): void
    {
        Schema::table('artwork_logos', function (Blueprint $table) {
            $table->dropColumn(['is_primary', 'purpose', 'sort_order']);
        });
    }
};
