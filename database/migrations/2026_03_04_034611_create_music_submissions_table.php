<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('music_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('artist_name');
            $table->string('email')->nullable();
            $table->string('track_title')->nullable();
            $table->string('genre')->nullable();
            $table->text('message')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('new');
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('music_submissions');
    }
};
