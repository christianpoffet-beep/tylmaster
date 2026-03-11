<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('model_label')->nullable();
            $table->string('action'); // created, updated, deleted
            $table->string('field')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('action');
            $table->index('created_at');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
