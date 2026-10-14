<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReservationBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Mail::fake();
    }

    public function test_a_client_can_book_an_available_vehicle(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $vehicle = Vehicle::factory()->create(['status' => 'available', 'price_per_day' => 50000]);

        $response = $this->actingAs($client)->post("/vehicles/{$vehicle->id}/reserve", [
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'id_number' => 'ID12345',
            'nationality' => 'Rwandan',
            'driving_license_number' => 'DL12345',
            'delivery_method' => 'pickup',
            'pickup_time' => '10:00',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, Reservation::where('vehicle_id', $vehicle->id)->where('client_id', $client->id)->count());
    }

    public function test_booking_the_same_vehicle_for_overlapping_dates_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => 'available']);
        Reservation::factory()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'status' => 'pending',
        ]);

        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)->post("/vehicles/{$vehicle->id}/reserve", [
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'id_number' => 'ID12345',
            'nationality' => 'Rwandan',
            'driving_license_number' => 'DL12345',
            'delivery_method' => 'pickup',
            'pickup_time' => '10:00',
        ]);

        $response->assertSessionHasErrors('start_date');
        $this->assertSame(1, Reservation::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_booking_below_the_vehicles_minimum_rental_days_is_rejected(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $vehicle = Vehicle::factory()->create(['status' => 'available', 'min_rental_days' => 3]);

        $response = $this->actingAs($client)->post("/vehicles/{$vehicle->id}/reserve", [
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(), // 1 day, below the 3-day minimum
            'id_number' => 'ID12345',
            'nationality' => 'Rwandan',
            'driving_license_number' => 'DL12345',
            'delivery_method' => 'pickup',
            'pickup_time' => '10:00',
        ]);

        $response->assertSessionHasErrors('end_date');
        $this->assertSame(0, Reservation::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_booking_at_the_vehicles_minimum_rental_days_succeeds(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $vehicle = Vehicle::factory()->create(['status' => 'available', 'min_rental_days' => 3]);

        $response = $this->actingAs($client)->post("/vehicles/{$vehicle->id}/reserve", [
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(), // exactly 3 days
            'id_number' => 'ID12345',
            'nationality' => 'Rwandan',
            'driving_license_number' => 'DL12345',
            'delivery_method' => 'pickup',
            'pickup_time' => '10:00',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, Reservation::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_booking_with_delivery_adds_the_delivery_fee(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $vehicle = Vehicle::factory()->create(['status' => 'available', 'price_per_day' => 50000, 'deposit_amount' => 0]);

        $this->actingAs($client)->post("/vehicles/{$vehicle->id}/reserve", [
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'id_number' => 'ID12345',
            'nationality' => 'Rwandan',
            'driving_license_number' => 'DL12345',
            'delivery_method' => 'delivery',
            'pickup_time' => '10:00',
            'delivery_location' => 'Kigali Heights',
        ]);

        $reservation = Reservation::where('vehicle_id', $vehicle->id)->first();
        $this->assertEquals(50000 + Reservation::DELIVERY_FEE, (float) $reservation->total_price);
        $this->assertEquals(Reservation::DELIVERY_FEE, (float) $reservation->delivery_fee);
    }
}
