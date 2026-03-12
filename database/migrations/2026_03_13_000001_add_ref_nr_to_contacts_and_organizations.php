<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('ref_nr', 20)->nullable()->unique()->after('id');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('ref_nr', 20)->nullable()->unique()->after('id');
        });

        // Assign ref_nr to existing contacts (C1001, C1002, ...)
        $contacts = DB::table('contacts')->orderBy('created_at')->orderBy('id')->get();
        $nr = 1001;
        foreach ($contacts as $contact) {
            DB::table('contacts')->where('id', $contact->id)->update(['ref_nr' => 'C' . $nr]);
            $nr++;
        }

        // Assign ref_nr to existing organizations (O1001, O1002, ...)
        $organizations = DB::table('organizations')->orderBy('created_at')->orderBy('id')->get();
        $nr = 1001;
        foreach ($organizations as $org) {
            DB::table('organizations')->where('id', $org->id)->update(['ref_nr' => 'O' . $nr]);
            $nr++;
        }
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('ref_nr');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('ref_nr');
        });
    }
};
