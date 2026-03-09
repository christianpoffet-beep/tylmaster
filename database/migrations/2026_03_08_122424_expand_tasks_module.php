<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expand tasks table
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->change();
            $table->text('description')->nullable()->after('title');
            $table->string('priority')->nullable()->after('description');
        });

        // Polymorphic pivot table for task relationships
        Schema::create('taskables', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('taskable_id');
            $table->string('taskable_type');
            $table->index(['taskable_id', 'taskable_type']);
            $table->primary(['task_id', 'taskable_id', 'taskable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskables');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['description', 'priority']);
        });
    }
};
