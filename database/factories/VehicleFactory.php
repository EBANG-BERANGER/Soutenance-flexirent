<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'partner_id' => Partner::factory(),
            'category_id' => Category::factory(),
            'model' => fake()->randomElement(['Toyota RAV4', 'Kia Sorento', 'Hyundai Tucson', 'Toyota Corolla']),
            'year' => fake()->numberBetween(Vehicle::minAcceptedYear(), (int) date('Y')),
            'propulsion' => 'essence',
            'car_type' => 'normal',
            'plate_number' => strtoupper(fake()->unique()->bothify('RA###?')),
            'chassis_number' => strtoupper(fake()->unique()->bothify('CHS#########')),
            'price_per_day' => fake()->numberBetween(20000, 100000),
            'driver_available' => false,
            'driver_fee_per_day' => 0,
            'deposit_amount' => 30000,
            'status' => 'available',
            'is_suspended' => false,
            'is_featured' => false,
        ];
    }
}
