<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Party preamble ("... vertreten durch ..., nachfolgend «Label»")
            $table->string('preamble_mode', 10)->default('auto')->after('subject');
            $table->text('preamble_text')->nullable()->after('preamble_mode');
            $table->boolean('show_parties_table')->default(true)->after('preamble_text');

            // Numbered clauses replacing the single free-text terms field
            $table->json('sections')->nullable()->after('terms');
            $table->boolean('auto_number_sections')->default(true)->after('sections');
            $table->text('closing_note')->nullable()->after('auto_number_sections');

            // Headline overrides so a contract can call things by its own names
            $table->string('subject_heading')->nullable()->after('subject');
            $table->string('relations_heading')->nullable()->after('relations_note');
        });

        Schema::table('contract_parties', function (Blueprint $table) {
            $table->string('role_label')->nullable()->after('share');
        });

        Schema::table('contract_templates', function (Blueprint $table) {
            $table->json('default_sections')->nullable()->after('default_terms');
            $table->text('default_closing_note')->nullable()->after('default_sections');
            $table->string('default_subject_heading')->nullable()->after('default_subject');
            $table->string('default_relations_heading')->nullable()->after('default_relations_note');
        });

        // Existing contracts that already carry a hand-typed party preamble
        // inside the subject must not suddenly print it twice.
        DB::table('contracts')
            ->where(function ($q) {
                $q->where('subject', 'like', '%nachfolgend%')
                  ->orWhere('subject', 'like', '%VERTRAGSPARTEIEN%');
            })
            ->update(['preamble_mode' => 'none']);
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'preamble_mode', 'preamble_text', 'show_parties_table',
                'sections', 'auto_number_sections', 'closing_note',
                'subject_heading', 'relations_heading',
            ]);
        });

        Schema::table('contract_parties', function (Blueprint $table) {
            $table->dropColumn('role_label');
        });

        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropColumn([
                'default_sections', 'default_closing_note',
                'default_subject_heading', 'default_relations_heading',
            ]);
        });
    }
};
