<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\ReliabilityEvent;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReliabilityScoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Mail::fake();
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

    public function test_completing_a_reservation_awards_five_points(): void
    {
        $reservation = $this->reservation([
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDay(),
        ]);

        Artisan::call('app:complete-finished-reservations');

        $client = $reservation->client->fresh();
        $this->assertSame('completed', $reservation->fresh()->status);
        $this->assertSame(5, $client->reliability_score);
        $this->assertSame(1, ReliabilityEvent::where('user_id', $client->id)->where('points', 5)->count());
    }

    public function test_late_cancellation_deducts_ten_points(): void
    {
        $reservation = $this->reservation([
            'payment_status' => 'unpaid',
            'start_date' => now()->addHours(10), // less than 48h away
        ]);

        $this->actingAs($reservation->client)->post("/reservations/{$reservation->id}/cancel");

        $client = $reservation->client->fresh();
        $this->assertSame(-10, $client->reliability_score);
        $event = ReliabilityEvent::where('user_id', $client->id)->first();
        $this->assertStringContainsString('Late cancellation', $event->reason);
    }

    public function test_on_time_cancellation_logs_a_zero_point_event_without_changing_the_score(): void
    {
        $reservation = $this->reservation([
            'payment_status' => 'unpaid',
            'start_date' => now()->addDays(5), // more than 48h away
        ]);

        $this->actingAs($reservation->client)->post("/reservations/{$reservation->id}/cancel");

        $client = $reservation->client->fresh();
        $this->assertSame(0, $client->reliability_score);
        $event = ReliabilityEvent::where('user_id', $client->id)->first();
        $this->assertNotNull($event, 'A zero-point event should still be logged for a complete history.');
        $this->assertSame('Cancelled within policy', $event->reason);
    }

    public function test_a_client_below_the_reliability_threshold_cannot_choose_deposit_only(): void
    {
        $client = User::factory()->create(['role' => 'client', 'reliability_score' => -30]);
        $vehicle = Vehicle::factory()->create(['status' => 'available', 'deposit_amount' => 0]);
        $reservation = Reservation::factory()->create([
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'total_price' => 100000,
        ]);

        $response = $this->actingAs($client)->post("/reservations/{$reservation->id}/payment", [
            'method' => 'manual',
            'payment_type' => 'deposit',
            'accepted_terms' => '1',
        ]);

        $response->assertSessionHasErrors('payment_type');
        $this->assertSame('unpaid', $reservation->fresh()->payment_status);
    }
}
