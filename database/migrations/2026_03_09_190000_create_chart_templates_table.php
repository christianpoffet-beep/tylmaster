<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('organization_type_slug')->nullable();
            $table->timestamps();
        });

        Schema::create('chart_template_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chart_template_id')->constrained()->cascadeOnDelete();
            $table->string('number', 10);
            $table->string('name');
            $table->string('type'); // asset, liability, income, expense
            $table->string('parent_number', 10)->nullable();
            $table->boolean('is_header')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_template_accounts');
        Schema::dropIfExists('chart_templates');
    }
};
