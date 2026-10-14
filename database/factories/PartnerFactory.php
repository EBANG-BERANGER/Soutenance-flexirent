<?php

namespace Database\Factories;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'partner']),
            'partner_type' => 'agency',
            'company_name' => fake()->company(),
            'tin' => fake()->numerify('###########'),
            'status' => 'validated',
            'is_suspended' => false,
        ];
    }

    public function individual(): static
    {
        return $this->state(fn () => [
            'partner_type' => 'individual',
            'company_name' => null,
            'tin' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }
}
