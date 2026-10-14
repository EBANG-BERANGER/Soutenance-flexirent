<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReservationCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Mail::fake();
        User::factory()->create(['role' => 'admin']);
    }

    private function reservation(array $attributes): Reservation
    {
        $client = User::factory()->create(['role' => 'client']);
        $vehicle = Vehicle::factory()->create(['status' => 'available']);

        return Reservation::factory()->create(array_merge([
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'confirmed',
            'total_price' => 100000,
        ], $attributes));
    }

    public function test_cancelling_an_unpaid_reservation_is_free(): void
    {
        $reservation = $this->reservation([
            'payment_status' => 'unpaid',
            'start_date' => now()->addDays(5),
        ]);

        $this->actingAs($reservation->client)->post("/reservations/{$reservation->id}/cancel");

        $reservation->refresh();
        $this->assertSame('cancelled', $reservation->status);
        $this->assertSame('confirmed', $reservation->status_before_cancellation);
        $this->assertSame(0, Payment::where('reservation_id', $reservation->id)->where('type', 'refund')->count());
    }

    public function test_cancelling_a_deposit_paid_reservation_more_than_48h_out_retains_ten_percent(): void
    {
        $reservation = $this->reservation([
            'payment_status' => 'deposit_paid',
            'deposit_amount' => 20000,
            'start_date' => now()->addDays(5),
        ]);

        $this->actingAs($reservation->client)->post("/reservations/{$reservation->id}/cancel");

        $refund = Payment::where('reservation_id', $reservation->id)->where('type', 'refund')->first();
        $this->assertNotNull($refund);
        $this->assertEquals(18000.0, (float) $refund->amount); // 90% of 20000
    }

    public function test_cancelling_a_deposit_paid_reservation_within_48h_retains_the_full_deposit(): void
    {
        $reservation = $this->reservation([
            'payment_status' => 'deposit_paid',
            'deposit_amount' => 20000,
            'start_date' => now()->addHours(10),
        ]);

        $this->actingAs($reservation->client)->post("/reservations/{$reservation->id}/cancel");

        $this->assertSame(0, Payment::where('reservation_id', $reservation->id)->where('type', 'refund')->count());
    }

    public function test_cancelling_a_fully_paid_reservation_more_than_48h_out_retains_ten_percent_of_total(): void
    {
        $reservation = $this->reservation([
            'payment_status' => 'paid',
            'total_price' => 100000,
            'start_date' => now()->addDays(5),
        ]);

        $this->actingAs($reservation->client)->post("/reservations/{$reservation->id}/cancel");

        $refund = Payment::where('reservation_id', $reservation->id)->where('type', 'refund')->first();
        $this->assertEquals(90000.0, (float) $refund->amount);
    }

    public function test_cancelling_a_fully_paid_reservation_within_48h_retains_twenty_percent_of_total(): void
    {
        $reservation = $this->reservation([
            'payment_status' => 'paid',
            'total_price' => 100000,
            'start_date' => now()->addHours(10),
        ]);

        $this->actingAs($reservation->client)->post("/reservations/{$reservation->id}/cancel");

        $refund = Payment::where('reservation_id', $reservation->id)->where('type', 'refund')->first();
        $this->assertEquals(80000.0, (float) $refund->amount);
    }

    public function test_a_client_cannot_cancel_someone_elses_reservation(): void
    {
        $reservation = $this->reservation(['payment_status' => 'unpaid', 'start_date' => now()->addDays(5)]);
        $otherClient = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($otherClient)->post("/reservations/{$reservation->id}/cancel");

        $response->assertForbidden();
    }

    public function test_admin_can_reactivate_a_cancelled_reservation(): void
    {
        $admin = User::where('role', 'admin')->first();
        $reservation = $this->reservation(['payment_status' => 'unpaid', 'start_date' => now()->addDays(5)]);
        $this->actingAs($reservation->client)->post("/reservations/{$reservation->id}/cancel");
        $reservation->refresh();

        $response = $this->actingAs($admin)->post("/control-panel/reservations/{$reservation->id}/reactivate", [
            'reason' => 'Client called support, misclick.',
        ]);

        $response->assertRedirect();
        $reservation->refresh();
        $this->assertSame('confirmed', $reservation->status);
        $this->assertNull($reservation->status_before_cancellation);
    }

    public function test_admin_cannot_reactivate_into_a_now_conflicting_slot(): void
    {
        $admin = User::where('role', 'admin')->first();
        $reservation = $this->reservation(['payment_status' => 'unpaid', 'start_date' => now()->addDays(5), 'end_date' => now()->addDays(7)]);
        $this->actingAs($reservation->client)->post("/reservations/{$reservation->id}/cancel");
        $reservation->refresh();

        // Another reservation now occupies the same vehicle/dates.
        Reservation::factory()->create([
            'vehicle_id' => $reservation->vehicle_id,
            'start_date' => $reservation->start_date,
            'end_date' => $reservation->end_date,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)->post("/control-panel/reservations/{$reservation->id}/reactivate", [
            'reason' => 'Attempting reactivation despite conflict.',
        ]);

        $response->assertSessionHasErrors('reason');
        $this->assertSame('cancelled', $reservation->fresh()->status);
    }

    public function test_admin_can_suspend_and_resume_a_reservation(): void
    {
        $admin = User::where('role', 'admin')->first();
        $reservation = $this->reservation(['start_date' => now()->addDays(5), 'end_date' => now()->addDays(7)]);

        $this->actingAs($admin)->post("/control-panel/reservations/{$reservation->id}/suspend");
        $this->assertSame('suspended', $reservation->fresh()->status);

        $response = $this->actingAs($admin)->post("/control-panel/reservations/{$reservation->id}/resume");

        $response->assertRedirect();
        $this->assertSame('confirmed', $reservation->fresh()->status);
    }

    public function test_admin_cannot_resume_into_a_now_conflicting_slot(): void
    {
        $admin = User::where('role', 'admin')->first();
        $reservation = $this->reservation(['start_date' => now()->addDays(5), 'end_date' => now()->addDays(7)]);

        $this->actingAs($admin)->post("/control-panel/reservations/{$reservation->id}/suspend");
        $this->assertSame('suspended', $reservation->fresh()->status);

        // Suspending freed the vehicle up: another client books it for the
        // same dates before the admin resumes the first reservation.
        Reservation::factory()->create([
            'vehicle_id' => $reservation->vehicle_id,
            'start_date' => $reservation->start_date,
            'end_date' => $reservation->end_date,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)->post("/control-panel/reservations/{$reservation->id}/resume");

        $response->assertSessionHasErrors('reservation');
        $this->assertSame('suspended', $reservation->fresh()->status);
    }

    public function test_a_vehicle_with_a_pending_reservation_cannot_be_deleted(): void
    {
        $admin = User::where('role', 'admin')->first();
        $vehicle = Vehicle::factory()->create(['status' => 'available']);
        Reservation::factory()->create(['vehicle_id' => $vehicle->id, 'status' => 'pending']);

        $response = $this->actingAs($admin)->delete("/control-panel/vehicles/{$vehicle->id}");

        $response->assertStatus(422);
        $this->assertNotNull($vehicle->fresh());
    }

    public function test_a_vehicle_with_no_active_reservation_can_be_deleted(): void
    {
        $admin = User::where('role', 'admin')->first();
        $vehicle = Vehicle::factory()->create(['status' => 'available']);

        $response = $this->actingAs($admin)->delete("/control-panel/vehicles/{$vehicle->id}");

        $response->assertRedirect();
        $this->assertNull(Vehicle::find($vehicle->id));
    }
}
