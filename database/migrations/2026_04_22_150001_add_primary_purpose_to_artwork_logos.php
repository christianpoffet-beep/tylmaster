<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artwork_logos', function (Blueprint $table) {
            if (!Schema::hasColumn('artwork_logos', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('comment');
            }
            if (!Schema::hasColumn('artwork_logos', 'purpose')) {
                $table->string('purpose', 100)->nullable()->after('is_primary');
            }
            if (!Schema::hasColumn('artwork_logos', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('purpose');
            }
        });
    }

    public function down(): void
    {
        Schema::table('artwork_logos', function (Blueprint $table) {
            foreach (['is_primary', 'purpose', 'sort_order'] as $col) {
                if (Schema::hasColumn('artwork_logos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
