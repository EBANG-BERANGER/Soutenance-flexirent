<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Violation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ViolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_partner_can_report_a_violation_and_the_client_is_notified_even_after_the_rental_ended(): void
    {
        $partner = Partner::factory()->create();
        $vehicle = Vehicle::factory()->create(['partner_id' => $partner->id, 'status' => 'available']);
        $client = User::factory()->create(['role' => 'client']);
        $reservation = Reservation::factory()->create([
            'client_id' => $client->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
            'start_date' => now()->subMonths(3),
            'end_date' => now()->subMonths(3)->addDays(4),
        ]);

        $response = $this->actingAs($partner->user)->post("/partner/vehicles/{$vehicle->id}/violations", [
            'reference_number' => 'RRA-999',
            'violation_date' => $reservation->start_date->addDay()->toDateString(),
            'payment_due_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertRedirect();
        $violation = Violation::first();
        $this->assertNotNull($violation);
        $this->assertSame($reservation->id, $violation->reservation_id);
        $this->assertSame(1, \App\Models\Notification::where('user_id', $client->id)
            ->where('title', 'Traffic violation reported on your rental')
            ->count());
    }

    public function test_partner_cannot_report_a_violation_on_another_partners_vehicle(): void
    {
        $owner = Partner::factory()->create();
        $otherPartner = Partner::factory()->create();
        $vehicle = Vehicle::factory()->create(['partner_id' => $owner->id, 'status' => 'available']);

        $response = $this->actingAs($otherPartner->user)->post("/partner/vehicles/{$vehicle->id}/violations", [
            'reference_number' => 'RRA-000',
            'violation_date' => now()->subDay()->toDateString(),
            'payment_due_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertForbidden();
        $this->assertSame(0, Violation::count());
    }
}
