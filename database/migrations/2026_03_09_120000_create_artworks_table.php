<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('artwork_path')->nullable();
            $table->unsignedBigInteger('artwork_file_size')->nullable();
            $table->string('artwork_mime_type')->nullable();
            $table->string('artwork_original_name')->nullable();
            $table->string('photographer')->nullable();
            $table->string('artwork_by')->nullable();
            $table->string('logo_by')->nullable();
            $table->string('design_by')->nullable();
            $table->unsignedSmallInteger('yoc')->nullable();
            $table->timestamps();
        });

        Schema::create('artwork_logos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('original_name');
            $table->string('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artwork_logos');
        Schema::dropIfExists('artworks');
    }
};
