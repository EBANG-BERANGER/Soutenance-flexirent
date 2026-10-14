<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\PaymentConfirmationService;
use App\Services\ReliabilityService;
use Illuminate\Console\Command;

/**
 * Nothing else ever flips a reservation from 'confirmed' to 'completed' once
 * its end date passes, which silently blocked reviews and left vehicles
 * showing as 'booked' long after the rental was over. Run daily.
 */
class CompleteFinishedReservations extends Command
{
    protected $signature = 'app:complete-finished-reservations';

    protected $description = 'Mark confirmed reservations past their end date as completed, and free up their vehicles';

    public function handle(PaymentConfirmationService $paymentConfirmationService, ReliabilityService $reliabilityService): int
    {
        $reservations = Reservation::where('status', 'confirmed')
            ->whereDate('end_date', '<', now())
            ->with('vehicle', 'client')
            ->get();

        foreach ($reservations as $reservation) {
            $reservation->update(['status' => 'completed']);
            $paymentConfirmationService->syncVehicleAvailability($reservation->vehicle);
            $reliabilityService->awardCompleted($reservation);
        }

        $this->info("Completed {$reservations->count()} finished reservation(s).");

        return self::SUCCESS;
    }
}
