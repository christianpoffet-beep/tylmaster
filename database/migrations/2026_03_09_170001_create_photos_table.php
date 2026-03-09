<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_folder_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('original_name');
            $table->string('public_slug', 10)->unique();
            $table->string('title')->nullable();
            $table->string('location')->nullable();
            $table->date('photo_date')->nullable();
            $table->text('story')->nullable();
            $table->text('info')->nullable();
            $table->string('photographer')->nullable();
            $table->string('graphic_artist')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
