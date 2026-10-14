<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_vehicle_with_no_reservations_is_available(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => 'available']);

        $this->assertTrue($vehicle->isAvailableBetween('2027-01-10', '2027-01-15'));
    }

    public function test_a_vehicle_is_unavailable_for_dates_overlapping_a_pending_reservation(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => 'available']);
        Reservation::factory()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => '2027-01-10',
            'end_date' => '2027-01-15',
            'status' => 'pending',
        ]);

        // Overlaps the middle of the existing reservation.
        $this->assertFalse($vehicle->isAvailableBetween('2027-01-12', '2027-01-13'));
        // Overlaps the start.
        $this->assertFalse($vehicle->isAvailableBetween('2027-01-08', '2027-01-11'));
        // Overlaps the end.
        $this->assertFalse($vehicle->isAvailableBetween('2027-01-14', '2027-01-20'));
        // Fully contains the existing reservation.
        $this->assertFalse($vehicle->isAvailableBetween('2027-01-01', '2027-01-31'));
    }

    public function test_a_vehicle_is_available_for_dates_that_do_not_overlap(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => 'available']);
        Reservation::factory()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => '2027-01-10',
            'end_date' => '2027-01-15',
            'status' => 'confirmed',
        ]);

        $this->assertTrue($vehicle->isAvailableBetween('2027-01-16', '2027-01-20'));
        $this->assertTrue($vehicle->isAvailableBetween('2027-01-01', '2027-01-09'));
    }

    public function test_a_cancelled_reservation_does_not_block_the_dates(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => 'available']);
        Reservation::factory()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => '2027-01-10',
            'end_date' => '2027-01-15',
            'status' => 'cancelled',
        ]);

        $this->assertTrue($vehicle->isAvailableBetween('2027-01-10', '2027-01-15'));
    }

    public function test_a_suspended_vehicle_is_never_available(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => 'available', 'is_suspended' => true]);

        $this->assertFalse($vehicle->isAvailableBetween('2027-01-10', '2027-01-15'));
    }

    public function test_a_vehicle_under_maintenance_is_never_available(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => 'maintenance']);

        $this->assertFalse($vehicle->isAvailableBetween('2027-01-10', '2027-01-15'));
    }
}
