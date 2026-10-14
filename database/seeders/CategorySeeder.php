<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * The vehicle categories shown across the catalogue and homepage filters.
 * "Jeep" is spelled out exactly (not "SUV/4x4") because the catalogue
 * template keys off that exact name to pick a different icon.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Sedan', 'description' => 'Comfortable, fuel-efficient cars for city driving and business trips.'],
            ['name' => 'SUV', 'description' => 'Spacious, elevated vehicles suited to Kigali\'s hills and longer trips.'],
            ['name' => 'Jeep', 'description' => 'Rugged 4x4s built for unpaved roads and upcountry travel.'],
            ['name' => 'Hatchback', 'description' => 'Compact, easy-to-park cars, ideal for solo or light city use.'],
            ['name' => 'Pickup', 'description' => 'Open-bed trucks for cargo, equipment and work trips.'],
            ['name' => 'Minivan', 'description' => 'Roomy vehicles for group travel, families and airport transfers.'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }

        $this->command?->info('Seeded ' . count($categories) . ' vehicle categories.');
    }
}
