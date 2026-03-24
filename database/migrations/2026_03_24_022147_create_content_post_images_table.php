<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_post_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_post_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 20); // upload, photo, artwork, artwork_logo
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_post_images');
    }
};
