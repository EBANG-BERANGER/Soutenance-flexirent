<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with a real admin account plus a
     * full demo dataset (categories, partners, vehicles, clients,
     * reservations, payments, contracts, reviews) so every page of the
     * site (public catalogue, partner dashboard, admin panel) has
     * realistic content instead of sitting empty.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            CategorySeeder::class,
            DemoFleetSeeder::class,
            DemoClientsSeeder::class,
            DemoReservationsSeeder::class,
            UatDemoSeeder::class,
        ]);
    }
}
