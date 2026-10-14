<?php

namespace App\Services;

use App\Mail\ContractMail;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Everything that must happen the moment a payment is confirmed, whether
 * an admin marks it received manually, or the simulated MTN MoMo / Airtel
 * Money confirmation portal validates it automatically. Kept in one place
 * so both paths can never drift apart.
 */
class PaymentConfirmationService
{
    public function __construct(
        private ContractService $contractService,
        private NotificationService $notificationService,
    ) {
    }

    public function confirm(Payment $payment, ?string $transactionRef = null): Reservation
    {
        abort_if($payment->status === 'paid', 422, 'This payment has already been marked as paid.');

        $reservation = $payment->reservation;

        // A payment can't be manually confirmed for a rental whose end date is
        // more than 24h in the past: at that point it's stale/likely an
        // error, and should be corrected through a fresh payment instead.
        abort_if(
            $reservation->end_date->copy()->addDay()->isPast(),
            422,
            'This reservation ended more than 24 hours ago and can no longer be confirmed as paid.'
        );

        if ($transactionRef && Payment::where('transaction_ref', $transactionRef)->where('id', '!=', $payment->id)->exists()) {
            abort(422, 'This transaction reference has already been used for another payment.');
        }

        $payment->update([
            'status' => 'paid',
            'transaction_ref' => $transactionRef ?: ('REF-' . $payment->id . '-' . now()->timestamp),
        ]);

        $reservation->update([
            'payment_status' => $reservation->balanceDue() <= 0 ? 'paid' : 'deposit_paid',
            'status' => 'confirmed',
        ]);

        $reservation->applyCommissionSplit();
        $this->syncVehicleAvailability($reservation->vehicle);

        // Regenerated on every confirmation (deposit, then balance later),
        // so the contract's payment section always matches reality instead
        // of going stale after the first payment.
        $contract = $this->contractService->generateForReservation($reservation);

        $this->notificationService->notifyPaymentMarkedReceived($reservation);
        $this->notificationService->notifyAdminsPaymentConfirmed($reservation);
        $this->emailContract($reservation, $contract);

        return $reservation->fresh();
    }

    private function emailContract(Reservation $reservation, \App\Models\Contract $contract): void
    {
        try {
            if ($reservation->client?->email) {
                Mail::to($reservation->client->email)->send(new ContractMail($contract));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to email contract: ' . $e->getMessage());
        }
    }

    /**
     * Keep a vehicle's public listing status in sync with whether it
     * currently has a payment-confirmed reservation. A vehicle under manual
     * maintenance is left untouched, unless $force is true: used when the
     * partner/admin is deliberately taking it OUT of maintenance and wants
     * the resulting status (available vs. booked) resolved for them.
     */
    public function syncVehicleAvailability(Vehicle $vehicle, bool $force = false): void
    {
        if ($vehicle->status === 'maintenance' && ! $force) {
            return;
        }

        $hasConfirmedReservation = $vehicle->reservations()->where('status', 'confirmed')->exists();
        $desiredStatus = $hasConfirmedReservation ? 'booked' : 'available';

        if ($vehicle->status !== $desiredStatus) {
            $vehicle->update(['status' => $desiredStatus]);
        }
    }
}
