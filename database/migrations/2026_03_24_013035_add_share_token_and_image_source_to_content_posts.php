<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_posts', function (Blueprint $table) {
            $table->string('share_token', 64)->nullable()->unique()->after('notes');
            $table->string('image_source_type')->nullable()->after('image');
            $table->unsignedBigInteger('image_source_id')->nullable()->after('image_source_type');
        });
    }

    public function down(): void
    {
        Schema::table('content_posts', function (Blueprint $table) {
            $table->dropColumn(['share_token', 'image_source_type', 'image_source_id']);
        });
    }
};
