<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A track used to hold exactly one audio file in its own columns. Versions
 * (master, radio edit, instrumental, ...) each get a row here instead, with
 * their own ISRC, title and remark. One of them is the main version.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('track_audio_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('title')->nullable();
            $table->string('isrc', 20)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_main')->default(false);
            $table->string('audio_format', 20)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('bitrate')->nullable();
            $table->unsignedInteger('sample_rate')->nullable();
            $table->unsignedTinyInteger('channels')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['track_id', 'is_main']);
            $table->index(['track_id', 'sort_order']);
            $table->index('isrc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('track_audio_files');
    }
};
