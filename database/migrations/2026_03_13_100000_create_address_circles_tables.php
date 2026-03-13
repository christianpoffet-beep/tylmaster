<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('address_circles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('info');
            $table->timestamps();
        });

        // Linked organizations (metadata)
        Schema::create('address_circle_organization', function (Blueprint $table) {
            $table->foreignId('address_circle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->primary(['address_circle_id', 'organization_id']);
        });

        // Linked projects (metadata)
        Schema::create('address_circle_project', function (Blueprint $table) {
            $table->foreignId('address_circle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->primary(['address_circle_id', 'project_id']);
        });

        // Members (polymorphic: contacts and organizations)
        Schema::create('address_circle_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('address_circle_id')->constrained()->cascadeOnDelete();
            $table->string('memberable_type');
            $table->unsignedBigInteger('memberable_id');
            $table->timestamps();
            $table->unique(['address_circle_id', 'memberable_type', 'memberable_id'], 'acm_unique');
            $table->index(['memberable_type', 'memberable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address_circle_members');
        Schema::dropIfExists('address_circle_project');
        Schema::dropIfExists('address_circle_organization');
        Schema::dropIfExists('address_circles');
    }
};
