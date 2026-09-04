<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tasks had a single is_completed flag, which cannot express "on hold" or
 * "not implemented". Replaced by a status column so there is one source of
 * truth instead of a flag plus a second field.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('status')->default('open')->after('description');
        });

        DB::table('tasks')->where('is_completed', true)->update(['status' => 'completed']);
        DB::table('tasks')->where('is_completed', false)->update(['status' => 'open']);

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('is_completed');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_completed')->default(false)->after('description');
        });

        // on_hold and not_implemented collapse back into "not completed"
        DB::table('tasks')->where('status', 'completed')->update(['is_completed' => true]);

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
