<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create pivot table
        Schema::create('artwork_project', function (Blueprint $table) {
            $table->foreignId('artwork_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->primary(['artwork_id', 'project_id']);
        });

        // Migrate existing project_id data to pivot table
        $artworks = DB::table('artworks')->whereNotNull('project_id')->get();
        foreach ($artworks as $artwork) {
            DB::table('artwork_project')->insert([
                'artwork_id' => $artwork->id,
                'project_id' => $artwork->project_id,
            ]);
        }

        // Drop project_id column
        Schema::table('artworks', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
        });

        $pivots = DB::table('artwork_project')->get();
        foreach ($pivots as $pivot) {
            DB::table('artworks')->where('id', $pivot->artwork_id)->update(['project_id' => $pivot->project_id]);
        }

        Schema::dropIfExists('artwork_project');
    }
};
