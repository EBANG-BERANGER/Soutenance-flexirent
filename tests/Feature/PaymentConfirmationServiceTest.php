<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PaymentConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentConfirmationServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentConfirmationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Mail::fake();
        User::factory()->create(['role' => 'admin']);
        $this->service = app(PaymentConfirmationService::class);
    }

    public function test_confirming_a_payment_splits_commission_ten_percent_to_flexirent(): void
    {
        $reservation = Reservation::factory()->create(['total_price' => 100000]);
        $payment = Payment::factory()->create([
            'reservation_id' => $reservation->id,
            'amount' => 100000,
            'status' => 'pending',
        ]);

        $this->service->confirm($payment);

        $reservation->refresh();
        $this->assertSame('10000.00', $reservation->platform_commission);
        $this->assertSame('90000.00', $reservation->owner_payout);
    }

    public function test_confirming_a_payment_marks_reservation_and_vehicle_confirmed(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => 'available']);
        $reservation = Reservation::factory()->create(['vehicle_id' => $vehicle->id, 'total_price' => 50000]);
        $payment = Payment::factory()->create(['reservation_id' => $reservation->id, 'amount' => 50000]);

        $this->service->confirm($payment);

        $this->assertSame('confirmed', $reservation->fresh()->status);
        $this->assertSame('paid', $reservation->fresh()->payment_status);
        $this->assertSame('booked', $vehicle->fresh()->status);
    }

    public function test_a_deposit_payment_leaves_the_reservation_partially_paid(): void
    {
        $reservation = Reservation::factory()->create(['total_price' => 100000]);
        $payment = Payment::factory()->create([
            'reservation_id' => $reservation->id,
            'amount' => 40000,
            'type' => 'deposit',
        ]);

        $this->service->confirm($payment);

        $this->assertSame('deposit_paid', $reservation->fresh()->payment_status);
    }

    public function test_confirming_the_same_payment_twice_is_rejected(): void
    {
        $reservation = Reservation::factory()->create(['total_price' => 50000]);
        $payment = Payment::factory()->create(['reservation_id' => $reservation->id, 'amount' => 50000]);

        $this->service->confirm($payment);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->confirm($payment->fresh());
    }

    public function test_a_confirmed_payment_generates_a_contract(): void
    {
        $reservation = Reservation::factory()->create(['total_price' => 60000]);
        $payment = Payment::factory()->create(['reservation_id' => $reservation->id, 'amount' => 60000]);

        $this->service->confirm($payment);

        $this->assertNotNull($reservation->fresh()->contract);
        Storage::disk('public')->assertExists($reservation->fresh()->contract->pdf_path);
    }

    public function test_maintenance_vehicles_are_left_alone_unless_forced(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => 'maintenance']);

        $this->service->syncVehicleAvailability($vehicle);
        $this->assertSame('maintenance', $vehicle->fresh()->status);

        $this->service->syncVehicleAvailability($vehicle, force: true);
        $this->assertSame('available', $vehicle->fresh()->status);
    }
}
