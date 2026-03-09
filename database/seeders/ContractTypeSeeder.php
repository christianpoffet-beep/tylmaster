<?php

namespace Database\Seeders;

use App\Models\ContractType;
use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Label', 'slug' => 'label', 'color' => 'bg-blue-100 text-blue-700', 'sort_order' => 1],
            ['name' => 'Publishing', 'slug' => 'publishing', 'color' => 'bg-indigo-100 text-indigo-700', 'sort_order' => 2],
            ['name' => 'Management', 'slug' => 'management', 'color' => 'bg-purple-100 text-purple-700', 'sort_order' => 3],
            ['name' => 'Licensing', 'slug' => 'licensing', 'color' => 'bg-green-100 text-green-700', 'sort_order' => 4],
            ['name' => 'Booking', 'slug' => 'booking', 'color' => 'bg-orange-100 text-orange-700', 'sort_order' => 5],
            ['name' => 'Promotion', 'slug' => 'promotion', 'color' => 'bg-pink-100 text-pink-700', 'sort_order' => 6],
            ['name' => 'Admin', 'slug' => 'admin', 'color' => 'bg-gray-100 text-gray-600', 'sort_order' => 7],
        ];

        foreach ($types as $type) {
            ContractType::firstOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
