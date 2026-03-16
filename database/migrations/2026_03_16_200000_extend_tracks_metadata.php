<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->string('version', 100)->nullable()->after('title');
            $table->text('composers')->nullable()->after('genre');
            $table->text('producers')->nullable()->after('composers');
            $table->string('language', 10)->nullable()->after('producers');
            $table->text('description')->nullable()->after('language');
            $table->unsignedSmallInteger('bpm')->nullable()->after('duration_seconds');
            $table->string('musical_key', 10)->nullable()->after('bpm');
            $table->unsignedInteger('bitrate')->nullable()->after('audio_file_path');
            $table->unsignedInteger('sample_rate')->nullable()->after('bitrate');
            $table->string('audio_format', 20)->nullable()->after('sample_rate');
            $table->unsignedTinyInteger('channels')->nullable()->after('audio_format');
        });
    }

    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn([
                'version', 'composers', 'producers', 'language', 'description',
                'bpm', 'musical_key', 'bitrate', 'sample_rate', 'audio_format', 'channels',
            ]);
        });
    }
};
