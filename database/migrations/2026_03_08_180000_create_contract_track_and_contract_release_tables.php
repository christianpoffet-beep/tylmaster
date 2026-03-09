<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_track', function (Blueprint $table) {
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('track_id')->constrained()->cascadeOnDelete();
            $table->primary(['contract_id', 'track_id']);
        });

        Schema::create('contract_release', function (Blueprint $table) {
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->primary(['contract_id', 'release_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_track');
        Schema::dropIfExists('contract_release');
    }
};
