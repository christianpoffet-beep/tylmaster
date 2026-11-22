<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Large exports arrive in pieces: an import is complete once its last piece
 * is read. Imports from before were read in one go and are complete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('royalty_imports', function (Blueprint $table) {
            $table->unsignedInteger('upload_pieces')->default(0)->after('rows_presettled');
            $table->timestamp('completed_at')->nullable()->after('upload_pieces');
        });

        DB::table('royalty_imports')->update(['completed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('royalty_imports', function (Blueprint $table) {
            $table->dropColumn(['upload_pieces', 'completed_at']);
        });
    }
};
