<?php

namespace Database\Seeders;

use App\Models\ProjectType;
use Illuminate\Database\Seeder;

class ProjectTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Release', 'slug' => 'release', 'color' => 'bg-purple-100 text-purple-700', 'sort_order' => 1],
            ['name' => 'Event', 'slug' => 'event', 'color' => 'bg-orange-100 text-orange-700', 'sort_order' => 2],
            ['name' => 'Administration', 'slug' => 'administration', 'color' => 'bg-gray-100 text-gray-700', 'sort_order' => 3],
        ];

        foreach ($types as $type) {
            ProjectType::firstOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
