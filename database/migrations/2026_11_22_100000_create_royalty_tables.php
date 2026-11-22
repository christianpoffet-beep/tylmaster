<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Usage data from distributors (Rebeat first): one row per line of their
 * accounting export, matched to a track or a product. The statements to the
 * artists are built from these lines later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_imports', function (Blueprint $table) {
            $table->id();
            $table->string('source', 30);
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->unsignedInteger('rows_new')->default(0);
            $table->unsignedInteger('rows_duplicate')->default(0);
            $table->unsignedInteger('rows_presettled')->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('royalty_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('royalty_import_id')->constrained()->cascadeOnDelete();
            $table->string('source', 30);
            $table->string('external_id', 64);
            $table->foreignId('track_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('release_id')->nullable()->constrained()->nullOnDelete();
            // isrc = same ISRC as the track, rule = remembered assignment, manual = picked here
            $table->string('match_type', 10)->nullable();
            $table->string('isrc', 15)->nullable();
            $table->string('ean', 20)->nullable();
            $table->string('artist')->nullable();
            $table->string('title')->nullable();
            $table->string('label')->nullable();
            $table->date('period');
            $table->date('sales_date')->nullable();
            $table->date('billing_period')->nullable();
            $table->string('country', 80)->nullable();
            $table->string('shop', 80)->nullable();
            $table->string('portal', 80)->nullable();
            $table->string('sales_type', 30)->nullable();
            $table->string('sales_category', 30)->nullable();
            $table->string('stream_type', 120)->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('amount_gross', 14, 6)->default(0);
            $table->decimal('amount', 14, 6)->default(0);
            $table->boolean('presettled')->default(false);

            $table->unique(['source', 'external_id']);
            $table->index(['royalty_import_id', 'isrc']);
            $table->index(['track_id', 'period']);
        });

        // An assignment made once by hand, used again for every later import
        Schema::create('royalty_match_rules', function (Blueprint $table) {
            $table->id();
            $table->string('source', 30);
            $table->string('match_key');
            $table->foreignId('track_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('release_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['source', 'match_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_match_rules');
        Schema::dropIfExists('royalty_lines');
        Schema::dropIfExists('royalty_imports');
    }
};
