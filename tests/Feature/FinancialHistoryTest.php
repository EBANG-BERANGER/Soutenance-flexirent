<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinancialHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Mail::fake();
        User::factory()->create(['role' => 'admin']);
    }

    private function paidReservation(Vehicle $vehicle, array $attributes = []): Reservation
    {
        $client = User::factory()->create(['role' => 'client']);

        return Reservation::factory()->create(array_merge([
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
            'total_price' => 100000,
            'platform_commission' => 10000,
            'owner_payout' => 90000,
        ], $attributes));
    }

    public function test_only_confirmed_and_completed_reservations_count_toward_the_totals(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->paidReservation($vehicle, ['status' => 'completed']);
        $this->paidReservation($vehicle, ['status' => 'pending']); // never paid, shouldn't count
        $this->paidReservation($vehicle, ['status' => 'cancelled']); // shouldn't count

        $total = \App\Models\Reservation::financialHistoryQuery([$vehicle->id])->sum('total_price');

        $this->assertEquals(100000, $total);
    }

    public function test_admin_vehicle_history_filters_by_date_range(): void
    {
        $admin = User::where('role', 'admin')->first();
        $vehicle = Vehicle::factory()->create();
        $inRange = $this->paidReservation($vehicle, ['start_date' => now()->subDays(5), 'end_date' => now()->subDays(3)]);
        $outOfRange = $this->paidReservation($vehicle, ['start_date' => now()->subDays(60), 'end_date' => now()->subDays(58)]);

        $response = $this->actingAs($admin)->get("/control-panel/vehicles/{$vehicle->id}/history?from=" . now()->subDays(10)->toDateString() . '&to=' . now()->toDateString());

        $response->assertOk();
        $response->assertSee($inRange->client->name);
        $response->assertDontSee($outOfRange->client->name);
    }

    public function test_admin_partner_history_filters_by_client_search_and_recalculates_totals(): void
    {
        $admin = User::where('role', 'admin')->first();
        $partner = Partner::factory()->create();
        $vehicle = Vehicle::factory()->create(['partner_id' => $partner->id]);

        $target = User::factory()->create(['role' => 'client', 'name' => 'Aline Mutesi']);
        $other = User::factory()->create(['role' => 'client', 'name' => 'Jean Habimana']);
        Reservation::factory()->create([
            'client_id' => $target->id, 'vehicle_id' => $vehicle->id, 'status' => 'completed',
            'total_price' => 50000, 'owner_payout' => 45000, 'platform_commission' => 5000,
        ]);
        Reservation::factory()->create([
            'client_id' => $other->id, 'vehicle_id' => $vehicle->id, 'status' => 'completed',
            'total_price' => 70000, 'owner_payout' => 63000, 'platform_commission' => 7000,
        ]);

        $response = $this->actingAs($admin)->get("/control-panel/partners/{$partner->id}/history?client=Aline");

        $response->assertOk();
        $response->assertSee('45,000');
        $response->assertDontSee('63,000');
    }
}
