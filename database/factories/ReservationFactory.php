<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+5 days');
        $end = (clone $start)->modify('+' . fake()->numberBetween(1, 6) . ' days');

        return [
            'client_id' => User::factory(),
            'vehicle_id' => Vehicle::factory(),
            'id_number' => fake()->numerify('ID#########'),
            'driving_license_number' => fake()->bothify('DL-####??'),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'with_driver' => false,
            'delivery_method' => 'pickup',
            'delivery_fee' => 0,
            'total_price' => 100000,
            'original_total_price' => 100000,
            'payment_type' => 'full',
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }
}
