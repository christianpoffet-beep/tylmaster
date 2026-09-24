<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A contract template only carried the texts, the parties and the rights.
 * Everything else a contract knows - logo, attached document, linked works,
 * advance payment, territory and the PDF layout switches - had to be set again
 * on every contract. These columns close that gap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            // Advance payment
            $table->boolean('default_has_zession')->default(false);
            $table->decimal('default_zession_amount', 12, 2)->nullable();
            $table->string('default_zession_currency', 3)->default('CHF');
            $table->text('default_zession_notes')->nullable();

            // Territory
            $table->json('default_territory')->nullable();

            // PDF layout switches
            $table->string('default_preamble_mode', 10)->default('auto');
            $table->text('default_preamble_text')->nullable();
            $table->boolean('default_show_parties_table')->default(true);
            $table->boolean('default_auto_number_sections')->default(true);

            // Linked works
            $table->json('default_project_ids')->nullable();
            $table->json('default_track_ids')->nullable();
            $table->json('default_release_ids')->nullable();

            // Logo
            $table->string('logo_path')->nullable();
            $table->boolean('logo_in_header')->default(false);
            $table->boolean('logo_as_watermark')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropColumn([
                'default_has_zession', 'default_zession_amount', 'default_zession_currency', 'default_zession_notes',
                'default_territory',
                'default_preamble_mode', 'default_preamble_text', 'default_show_parties_table', 'default_auto_number_sections',
                'default_project_ids', 'default_track_ids', 'default_release_ids',
                'logo_path', 'logo_in_header', 'logo_as_watermark',
            ]);
        });
    }
};
