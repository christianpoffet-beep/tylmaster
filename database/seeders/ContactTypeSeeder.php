<?php

namespace Database\Seeders;

use App\Models\ContactType;
use Illuminate\Database\Seeder;

class ContactTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Artist', 'slug' => 'artist', 'color' => 'bg-purple-100 text-purple-700', 'sort_order' => 1],
            ['name' => 'Label', 'slug' => 'label', 'color' => 'bg-blue-100 text-blue-700', 'sort_order' => 2],
            ['name' => 'Venue', 'slug' => 'venue', 'color' => 'bg-orange-100 text-orange-700', 'sort_order' => 3],
            ['name' => 'Publisher', 'slug' => 'publisher', 'color' => 'bg-indigo-100 text-indigo-700', 'sort_order' => 4],
            ['name' => 'Fan', 'slug' => 'fan', 'color' => 'bg-pink-100 text-pink-700', 'sort_order' => 5],
            ['name' => 'Media', 'slug' => 'media', 'color' => 'bg-teal-100 text-teal-700', 'sort_order' => 6],
            ['name' => 'Kontakt', 'slug' => 'other', 'color' => 'bg-gray-100 text-gray-600', 'sort_order' => 7],
        ];

        foreach ($types as $type) {
            ContactType::firstOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
