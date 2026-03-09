<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add new columns
        Schema::table('contacts', function (Blueprint $table) {
            $table->json('types')->default('["other"]')->after('country');
            $table->json('secondary_emails')->nullable()->after('email');
            $table->json('secondary_phones')->nullable()->after('phone');
        });

        // Migrate existing type data into types JSON array
        DB::table('contacts')->orderBy('id')->each(function ($contact) {
            $type = $contact->type ?: 'other';
            DB::table('contacts')->where('id', $contact->id)->update([
                'types' => json_encode([$type]),
            ]);
        });

        // Drop old type column
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('type')->default('other')->after('country');
        });

        // Migrate first type back
        DB::table('contacts')->orderBy('id')->each(function ($contact) {
            $types = json_decode($contact->types, true);
            DB::table('contacts')->where('id', $contact->id)->update([
                'type' => $types[0] ?? 'other',
            ]);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['types', 'secondary_emails', 'secondary_phones']);
        });
    }
};
