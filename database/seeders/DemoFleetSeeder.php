<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Partner;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Populates the catalogue with a realistic Kigali fleet: validated
 * partners (agencies and independent owners) and vehicles spread across
 * every category and price band, so the public site, admin panel and
 * partner dashboard all have real content instead of an empty database.
 * Run after CategorySeeder; DemoReservationsSeeder depends on this fleet.
 */
class DemoFleetSeeder extends Seeder
{
    private const PASSWORD = 'Demo@2026!';

    public function run(): void
    {
        $categories = Category::pluck('id', 'name');

        if ($categories->isEmpty()) {
            $this->command?->warn('No categories found, run CategorySeeder first.');

            return;
        }

        $partners = $this->seedPartners();
        $vehiclePool = $this->vehiclePool();

        $created = 0;
        $partnerIndex = 0;
        $featuredAssigned = false;

        // Two passes over the pool: every model gets at least one listing,
        // and about a third get a second listing (different partner/year/
        // price) so the fleet reads as ~30 vehicles rather than exactly
        // one of each model.
        foreach ([1, 2] as $pass) {
            foreach ($vehiclePool as $spec) {
                if ($pass === 2 && random_int(1, 100) > 35) {
                    continue;
                }

                $partner = $partners[$partnerIndex % count($partners)];
                $partnerIndex++;

                $isElectric = $spec['car_type'] === 'electric';
                $roll = random_int(1, 100);
                $status = $roll <= 10 ? 'booked' : ($roll <= 15 ? 'maintenance' : 'available');
                $withDriver = random_int(1, 100) <= 30;
                // Rounded to the nearest 1,000 RWF: real Kigali rental
                // listings are priced in round figures, not raw random
                // integers like "27,679/day".
                $pricePerDay = (int) round(random_int($spec['price'][0], $spec['price'][1]) / 1000) * 1000;

                $vehicle = Vehicle::create([
                    'partner_id' => $partner->id,
                    'category_id' => $categories[$spec['category']],
                    'model' => $spec['model'],
                    'year' => random_int(Vehicle::minAcceptedYear() + 2, (int) date('Y')),
                    'propulsion' => $isElectric ? null : $spec['propulsion'],
                    'car_type' => $spec['car_type'],
                    'plate_number' => $this->plateNumber(),
                    'chassis_number' => strtoupper('CHS' . Str::random(9)),
                    'price_per_day' => $pricePerDay,
                    'driver_available' => $withDriver,
                    'driver_fee_per_day' => $withDriver ? random_int(8, 15) * 1000 : 0,
                    'deposit_amount' => $pricePerDay * random_int(3, 6),
                    'status' => $status,
                    'is_suspended' => false,
                    'is_featured' => ! $featuredAssigned && $spec['model'] === 'Toyota Land Cruiser Prado',
                ]);

                if ($vehicle->is_featured) {
                    $featuredAssigned = true;
                }

                $created++;
            }
        }

        $this->command?->info("Seeded {$created} vehicles across " . count($partners) . ' partners.');
    }

    /**
     * @return array<int, Partner>
     */
    private function seedPartners(): array
    {
        $roster = [
            ['type' => 'agency', 'company' => 'Kigali Prime Motors', 'owner' => 'Eric Bizimana'],
            ['type' => 'agency', 'company' => 'Inzozi Auto Hire', 'owner' => 'Diane Umutoni'],
            ['type' => 'agency', 'company' => 'Gasabo Fleet Services', 'owner' => 'Patrick Rugamba'],
            ['type' => 'individual', 'company' => null, 'owner' => 'Jean-Paul Nkurunziza'],
            ['type' => 'agency', 'company' => 'Nyarutarama Wheels', 'owner' => 'Aline Mutesi'],
            ['type' => 'individual', 'company' => null, 'owner' => 'Solange Ingabire'],
            ['type' => 'agency', 'company' => 'Kacyiru Premium Cars', 'owner' => 'Emmanuel Habiyaremye'],
            ['type' => 'agency', 'company' => 'Rwanda Comfort Rides', 'owner' => 'Chantal Uwera'],
        ];

        $partners = [];

        foreach ($roster as $entry) {
            $username = Str::slug($entry['owner'], '.') . random_int(10, 99);
            $email = Str::slug($entry['owner'], '.') . '@partners.flexirent.test';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $entry['owner'],
                    'username' => $username,
                    'password' => Hash::make(self::PASSWORD),
                    'role' => 'partner',
                    'phone' => '+250 78' . random_int(1000000, 9999999),
                    'address' => 'Kigali, Rwanda',
                    'email_verified_at' => now(),
                ]
            );

            $partners[] = Partner::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'partner_type' => $entry['type'],
                    'company_name' => $entry['company'],
                    'tin' => $entry['type'] === 'agency' ? (string) random_int(100000000, 999999999) : null,
                    'national_id_number' => $entry['type'] === 'individual' ? (string) random_int(1000000000000000, 1999999999999999) : null,
                    'status' => 'validated',
                    'is_suspended' => false,
                ]
            );
        }

        return $partners;
    }

    private function vehiclePool(): array
    {
        return [
            ['model' => 'Toyota Corolla', 'category' => 'Sedan', 'car_type' => 'normal', 'propulsion' => 'essence', 'price' => [22000, 32000]],
            ['model' => 'Toyota Premio', 'category' => 'Sedan', 'car_type' => 'normal', 'propulsion' => 'essence', 'price' => [25000, 35000]],
            ['model' => 'Toyota Corolla Hybrid', 'category' => 'Sedan', 'car_type' => 'hybrid', 'propulsion' => 'essence', 'price' => [30000, 40000]],
            ['model' => 'Toyota RAV4', 'category' => 'SUV', 'car_type' => 'normal', 'propulsion' => 'essence', 'price' => [45000, 65000]],
            ['model' => 'Honda CR-V', 'category' => 'SUV', 'car_type' => 'normal', 'propulsion' => 'essence', 'price' => [48000, 68000]],
            ['model' => 'Mazda CX-5', 'category' => 'SUV', 'car_type' => 'normal', 'propulsion' => 'diesel', 'price' => [46000, 66000]],
            ['model' => 'Nissan X-Trail', 'category' => 'SUV', 'car_type' => 'normal', 'propulsion' => 'diesel', 'price' => [44000, 60000]],
            ['model' => 'Toyota RAV4 Hybrid', 'category' => 'SUV', 'car_type' => 'hybrid', 'propulsion' => 'essence', 'price' => [55000, 75000]],
            ['model' => 'Toyota Land Cruiser Prado', 'category' => 'Jeep', 'car_type' => 'normal', 'propulsion' => 'diesel', 'price' => [90000, 140000]],
            ['model' => 'Toyota Land Cruiser V8', 'category' => 'Jeep', 'car_type' => 'normal', 'propulsion' => 'diesel', 'price' => [120000, 180000]],
            ['model' => 'Mitsubishi Pajero', 'category' => 'Jeep', 'car_type' => 'normal', 'propulsion' => 'diesel', 'price' => [80000, 120000]],
            ['model' => 'Suzuki Vitz', 'category' => 'Hatchback', 'car_type' => 'normal', 'propulsion' => 'essence', 'price' => [18000, 26000]],
            ['model' => 'Toyota Vitz', 'category' => 'Hatchback', 'car_type' => 'normal', 'propulsion' => 'essence', 'price' => [18000, 26000]],
            ['model' => 'Honda Fit', 'category' => 'Hatchback', 'car_type' => 'normal', 'propulsion' => 'essence', 'price' => [19000, 27000]],
            ['model' => 'Toyota Hilux', 'category' => 'Pickup', 'car_type' => 'normal', 'propulsion' => 'diesel', 'price' => [55000, 80000]],
            ['model' => 'Isuzu D-Max', 'category' => 'Pickup', 'car_type' => 'normal', 'propulsion' => 'diesel', 'price' => [52000, 75000]],
            ['model' => 'Toyota Noah', 'category' => 'Minivan', 'car_type' => 'normal', 'propulsion' => 'essence', 'price' => [40000, 55000]],
            ['model' => 'Toyota Voxy', 'category' => 'Minivan', 'car_type' => 'normal', 'propulsion' => 'essence', 'price' => [42000, 58000]],
            ['model' => 'Nissan Caravan', 'category' => 'Minivan', 'car_type' => 'normal', 'propulsion' => 'diesel', 'price' => [45000, 62000]],
            ['model' => 'Nissan Leaf', 'category' => 'Hatchback', 'car_type' => 'electric', 'propulsion' => null, 'price' => [50000, 70000]],
            ['model' => 'BYD Atto 3', 'category' => 'SUV', 'car_type' => 'electric', 'propulsion' => null, 'price' => [65000, 90000]],
        ];
    }

    private function plateNumber(): string
    {
        do {
            $plate = 'RA' . chr(random_int(65, 90)) . random_int(100, 999) . chr(random_int(65, 90));
        } while (Vehicle::where('plate_number', $plate)->exists());

        return $plate;
    }
}
