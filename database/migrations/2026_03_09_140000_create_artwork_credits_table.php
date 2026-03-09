<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artwork_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artwork_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // photographer, artwork_by, logo_by, design_by
            $table->string('creditable_type'); // App\Models\Contact or App\Models\Organization
            $table->unsignedBigInteger('creditable_id');
            $table->timestamps();

            $table->unique(['artwork_id', 'role', 'creditable_type', 'creditable_id'], 'artwork_credits_unique');
            $table->index(['creditable_type', 'creditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artwork_credits');
    }
};
