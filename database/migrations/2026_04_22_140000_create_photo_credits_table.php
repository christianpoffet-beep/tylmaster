<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // photographer, graphic_artist
            $table->string('creditable_type');
            $table->unsignedBigInteger('creditable_id');
            $table->string('ipi_number', 50)->nullable();
            $table->timestamps();

            $table->unique(['photo_id', 'role', 'creditable_type', 'creditable_id', 'ipi_number'], 'photo_credits_unique');
            $table->index(['creditable_type', 'creditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_credits');
    }
};
