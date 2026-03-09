<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('genre_organization', function (Blueprint $table) {
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->primary(['genre_id', 'organization_id']);
        });

        Schema::create('contact_genre', function (Blueprint $table) {
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->primary(['genre_id', 'contact_id']);
        });

        Schema::create('genre_project', function (Blueprint $table) {
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->primary(['genre_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genre_project');
        Schema::dropIfExists('contact_genre');
        Schema::dropIfExists('genre_organization');
        Schema::dropIfExists('genres');
    }
};
