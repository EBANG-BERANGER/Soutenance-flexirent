<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\NotificationService;
use App\Services\PaymentConfirmationService;
use App\Services\ReliabilityService;
use Illuminate\Console\Command;

/**
 * A pending reservation nobody pays for holds the vehicle indefinitely
 * otherwise. Cancels anything still 'pending'/'unpaid' 24h after it was
 * created, frees the vehicle, and penalizes the client's reliability score.
 * Run hourly, so an expiry is caught within an hour of the 24h mark rather
 * than sitting for up to a day under a daily schedule.
 */
class ExpireUnpaidReservations extends Command
{
    protected $signature = 'app:expire-unpaid-reservations';

    protected $description = 'Cancel pending, unpaid reservations 24h after creation and penalize the client\'s reliability score';

    public function handle(
        PaymentConfirmationService $paymentConfirmationService,
        ReliabilityService $reliabilityService,
        NotificationService $notificationService,
    ): int {
        $reservations = Reservation::where('status', 'pending')
            ->where('payment_status', 'unpaid')
            ->where('created_at', '<=', now()->subHours(24))
            ->with('vehicle', 'client')
            ->get();

        foreach ($reservations as $reservation) {
            $reservation->update([
                'status_before_cancellation' => $reservation->status,
                'status' => 'cancelled',
            ]);

            $paymentConfirmationService->syncVehicleAvailability($reservation->vehicle);
            $reliabilityService->awardExpired($reservation);
            $notificationService->notifyReservationExpired($reservation);
        }

        $this->info("Expired {$reservations->count()} unpaid reservation(s).");

        return self::SUCCESS;
    }
}
