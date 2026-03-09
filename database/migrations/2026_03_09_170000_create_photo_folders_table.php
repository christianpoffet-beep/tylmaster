<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('photo_folders')->nullOnDelete();
            $table->string('share_token', 64)->nullable()->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_photo_folder', function (Blueprint $table) {
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('photo_folder_id')->constrained()->cascadeOnDelete();
            $table->primary(['contact_id', 'photo_folder_id']);
        });

        Schema::create('organization_photo_folder', function (Blueprint $table) {
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('photo_folder_id')->constrained()->cascadeOnDelete();
            $table->primary(['organization_id', 'photo_folder_id']);
        });

        Schema::create('photo_folder_project', function (Blueprint $table) {
            $table->foreignId('photo_folder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->primary(['photo_folder_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_folder_project');
        Schema::dropIfExists('organization_photo_folder');
        Schema::dropIfExists('contact_photo_folder');
        Schema::dropIfExists('photo_folders');
    }
};
