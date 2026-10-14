<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReservationModificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Mail::fake();
    }

    private function bookedReservation(array $vehicleAttributes = []): Reservation
    {
        $client = User::factory()->create(['role' => 'client']);
        $vehicle = Vehicle::factory()->create(array_merge([
            'status' => 'available', 'price_per_day' => 50000, 'deposit_amount' => 0,
        ], $vehicleAttributes));

        $this->actingAs($client)->post("/vehicles/{$vehicle->id}/reserve", [
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'id_number' => 'ID12345',
            'nationality' => 'Rwandan',
            'driving_license_number' => 'DL12345',
            'delivery_method' => 'delivery',
            'pickup_time' => '10:00',
            'delivery_location' => 'Kigali Heights',
        ]);

        $reservation = Reservation::where('vehicle_id', $vehicle->id)->first();
        $reservation->update(['status' => 'confirmed']);

        return $reservation->fresh();
    }

    public function test_modifying_a_reservation_keeps_the_delivery_fee(): void
    {
        $reservation = $this->bookedReservation();
        $this->assertEquals(Reservation::DELIVERY_FEE, (float) $reservation->delivery_fee);

        $response = $this->actingAs($reservation->client)->put("/reservations/{$reservation->id}", [
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(), // 4 days instead of 2
            'pickup_time' => '10:00',
        ]);

        $response->assertRedirect();
        $reservation->refresh();

        $expectedSubtotal = 50000 * 4;
        $expectedChangeFee = $reservation->original_total_price * Reservation::CHANGE_FEE_RATE;
        $this->assertEquals(
            $expectedSubtotal + $expectedChangeFee + Reservation::DELIVERY_FEE,
            (float) $reservation->total_price
        );
    }

    public function test_modifying_a_reservation_uses_the_price_agreed_at_booking_not_the_current_vehicle_price(): void
    {
        $reservation = $this->bookedReservation(['price_per_day' => 50000]);
        $this->assertEquals(50000, (float) $reservation->price_per_day_snapshot);

        // Partner doubles the price after the client already booked.
        $reservation->vehicle->update(['price_per_day' => 100000]);

        $response = $this->actingAs($reservation->client)->put("/reservations/{$reservation->id}", [
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(13)->toDateString(), // 3 days
            'pickup_time' => '10:00',
        ]);

        $response->assertRedirect();
        $reservation->refresh();

        $expectedSubtotal = 50000 * 3; // original rate, not the new 100000
        $expectedChangeFee = $reservation->original_total_price * Reservation::CHANGE_FEE_RATE;
        $this->assertEquals(
            $expectedSubtotal + $expectedChangeFee + Reservation::DELIVERY_FEE,
            (float) $reservation->total_price
        );
    }

    public function test_modifying_a_reservation_into_a_now_conflicting_slot_is_rejected(): void
    {
        $reservation = $this->bookedReservation();

        Reservation::factory()->create([
            'vehicle_id' => $reservation->vehicle_id,
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(22)->toDateString(),
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($reservation->client)->put("/reservations/{$reservation->id}", [
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(22)->toDateString(),
            'pickup_time' => '10:00',
        ]);

        $response->assertSessionHasErrors('start_date');
    }
}
