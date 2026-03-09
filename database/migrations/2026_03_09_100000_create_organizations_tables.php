<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // band, label, publishing, venue, event_festival, media, oma
            $table->json('names');  // Array of names, min 1
            $table->text('biography')->nullable();
            $table->json('websites')->nullable(); // Array of URLs
            $table->timestamps();
        });

        Schema::create('contact_organization', function (Blueprint $table) {
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->primary(['contact_id', 'organization_id']);
        });

        Schema::create('organization_project', function (Blueprint $table) {
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->primary(['organization_id', 'project_id']);
        });

        Schema::create('organization_track', function (Blueprint $table) {
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('track_id')->constrained()->cascadeOnDelete();
            $table->primary(['organization_id', 'track_id']);
        });

        Schema::create('organization_release', function (Blueprint $table) {
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->primary(['organization_id', 'release_id']);
        });

        Schema::create('contract_organization', function (Blueprint $table) {
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->primary(['contract_id', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_organization');
        Schema::dropIfExists('organization_release');
        Schema::dropIfExists('organization_track');
        Schema::dropIfExists('organization_project');
        Schema::dropIfExists('contact_organization');
        Schema::dropIfExists('organizations');
    }
};
