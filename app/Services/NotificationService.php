<?php

namespace App\Services;

use App\Mail\TransactionalMail;
use App\Models\Notification as NotificationRecord;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Violation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notification Module (Ch.5.4.8).
 * Every event is recorded in the notifications table AND sent as a real
 * email via the configured mailer (Mailtrap in dev, a real SMTP/API
 * provider in production). Mail failures are logged but never block the
 * underlying reservation/payment flow.
 */
class NotificationService
{
    public function notifyReservationCreated(Reservation $reservation): void
    {
        $handoff = $reservation->isDelivery()
            ? "It will be delivered to you at {$reservation->delivery_location} on {$reservation->start_date->format('d/m/Y')}."
            : "You'll pick it up on {$reservation->start_date->format('d/m/Y')}.";

        $this->send(
            $reservation->client_id,
            'Reservation received',
            "Your reservation #{$reservation->id} for {$reservation->vehicle->model} has been received and is awaiting payment. {$handoff}"
        );
    }

    public function notifyPaymentRequested(Reservation $reservation, Payment $payment): void
    {
        $label = $payment->type === 'deposit' ? 'deposit' : ($payment->type === 'balance' ? 'balance' : 'payment');

        $this->send(
            $reservation->client_id,
            'Payment request recorded',
            "We've recorded your {$label} request of " . number_format($payment->amount) . " Frw for reservation #{$reservation->id} ({$reservation->vehicle->model}) via " . str_replace('_', ' ', $payment->method) . ". FlexiRent will confirm your reservation once the payment is received."
        );
    }

    public function notifyReservationUpdated(Reservation $reservation, float $changeFee): void
    {
        $this->send(
            $reservation->client_id,
            'Reservation updated',
            "Reservation #{$reservation->id} for {$reservation->vehicle->model} has been updated to " . $reservation->start_date->format('d/m/Y') . ' - ' . $reservation->end_date->format('d/m/Y') . ". A change fee of " . number_format($changeFee) . " Frw was added. Remaining balance: " . number_format($reservation->balanceDue()) . ' Frw.'
        );
    }

    public function notifyReservationExpired(Reservation $reservation): void
    {
        $this->send(
            $reservation->client_id,
            'Reservation expired',
            "Reservation #{$reservation->id} for {$reservation->vehicle->model} was automatically cancelled because no payment was received within 24 hours. You're welcome to book again."
        );
    }

    public function notifyReservationCancelledByAdmin(Reservation $reservation): void
    {
        $this->send(
            $reservation->client_id,
            'Reservation cancelled',
            "Your reservation #{$reservation->id} for {$reservation->vehicle->model} has been cancelled by FlexiRent administration."
        );
    }

    public function notifyReservationCancelledByClient(Reservation $reservation, float $retained, float $refund): void
    {
        $charge = $retained > 0
            ? number_format($retained) . ' Frw was retained' . ($refund > 0 ? ' and ' . number_format($refund) . ' Frw will be refunded.' : '.')
            : 'No charges were applied.';

        $this->send(
            $reservation->client_id,
            'Reservation cancelled',
            "Reservation #{$reservation->id} for {$reservation->vehicle->model} has been cancelled. {$charge}"
        );

        if ($partnerUser = $reservation->vehicle->partner?->user) {
            $this->send(
                $partnerUser->id,
                'Reservation cancelled by client',
                "Reservation #{$reservation->id} for {$reservation->vehicle->model} ({$reservation->start_date->format('d/m/Y')} to {$reservation->end_date->format('d/m/Y')}) was cancelled by the client. The vehicle is available again."
            );
        }

        $this->notifyAdmins(
            "Reservation #{$reservation->id} cancelled by client",
            "{$reservation->client->name} cancelled reservation #{$reservation->id} for {$reservation->vehicle->model}. "
            . number_format($retained) . ' Frw retained, ' . number_format($refund) . ' Frw due for refund' . ($refund > 0 ? ' (pending payment record created).' : '.')
        );
    }

    public function notifyAdminsNewReservation(Reservation $reservation): void
    {
        $this->notifyAdmins(
            "New reservation #{$reservation->id}",
            "{$reservation->client->name} requested reservation #{$reservation->id} for {$reservation->vehicle->model} "
            . "({$reservation->start_date->format('d/m/Y')} to {$reservation->end_date->format('d/m/Y')}). "
            . "Total: " . number_format($reservation->total_price) . " Frw. Awaiting payment."
        );
    }

    public function notifyAdminsPaymentConfirmed(Reservation $reservation): void
    {
        $this->notifyAdmins(
            "Payment confirmed: reservation #{$reservation->id}",
            "Payment for reservation #{$reservation->id} ({$reservation->vehicle->model}, client: {$reservation->client->name}) has been confirmed. "
            . "Total: " . number_format($reservation->total_price) . " Frw. FlexiRent commission: " . number_format((float) $reservation->platform_commission) . " Frw. "
            . "Owed to owner: " . number_format((float) $reservation->owner_payout) . " Frw."
        );
    }

    public function notifyAdminsNewPartnerRegistration(Partner $partner): void
    {
        $label = $partner->company_name ?: $partner->user->name . ' (individual)';

        $this->notifyAdmins(
            "New partner registration: {$label}",
            "{$partner->user->name} ({$partner->user->email}) registered as a " . ($partner->partner_type === 'agency' ? 'agency' : 'individual') . " partner"
            . ($partner->company_name ? " ({$partner->company_name})" : '') . '. Awaiting validation.'
        );
    }

    /**
     * Tells the client who had the vehicle out on the violation date about
     * a traffic fine reported against it, even if their rental has since
     * ended (Violation::resolveReservation() looks up 'confirmed'/'completed'
     * reservations regardless of current status). No-op if no reservation
     * covered that date, since there's no client to notify.
     */
    public function notifyClientOfViolation(Violation $violation): void
    {
        if (! $violation->reservation) {
            return;
        }

        $this->send(
            $violation->reservation->client_id,
            'Traffic violation reported on your rental',
            "A traffic violation (ref. {$violation->reference_number}) dated {$violation->violation_date->format('d/m/Y')} was reported against the {$violation->vehicle->model} you rented "
            . "({$violation->reservation->start_date->format('d/m/Y')} to {$violation->reservation->end_date->format('d/m/Y')}). "
            . "Payment is due by {$violation->payment_due_date->format('d/m/Y')}. Please contact FlexiRent support if you believe this is in error."
        );
    }

    public function notifyAdminsNewViolation(Violation $violation): void
    {
        $clientLine = $violation->reservation
            ? "Matched to reservation #{$violation->reservation->id}, client {$violation->reservation->client->name} (notified)."
            : 'No matching reservation found for that date, client could not be identified.';

        $this->notifyAdmins(
            "Traffic violation reported: {$violation->vehicle->model}",
            "{$violation->reportedBy->name} reported violation {$violation->reference_number} on {$violation->vehicle->model}, "
            . "dated {$violation->violation_date->format('d/m/Y')}, due {$violation->payment_due_date->format('d/m/Y')}. {$clientLine}"
        );
    }

    private function notifyAdmins(string $title, string $content): void
    {
        foreach (User::where('role', 'admin')->get() as $admin) {
            $this->send($admin->id, $title, $content);
        }
    }

    public function notifyPaymentMarkedReceived(Reservation $reservation): void
    {
        $handoffPoint = $reservation->isDelivery() ? 'delivery' : 'pickup';
        $statusLabel = $reservation->payment_status === 'deposit_paid'
            ? "Your deposit has been confirmed and your reservation is booked. The remaining balance of " . number_format($reservation->balanceDue()) . " Frw is due at {$handoffPoint}."
            : 'Your payment has been confirmed in full.';

        $handoff = $reservation->isDelivery()
            ? "Your vehicle will be delivered to {$reservation->delivery_location} on {$reservation->start_date->format('d/m/Y')}."
            : "You can pick up your vehicle on {$reservation->start_date->format('d/m/Y')}.";

        $this->send(
            $reservation->client_id,
            'Payment confirmed',
            "Payment for reservation #{$reservation->id} has been confirmed by FlexiRent. {$statusLabel} {$handoff} Your rental contract is ready to download."
        );

        if ($partnerUser = $reservation->vehicle->partner?->user) {
            $this->send(
                $partnerUser->id,
                'New confirmed booking',
                "Your vehicle {$reservation->vehicle->model} has been booked from {$reservation->start_date->format('d/m/Y')} to {$reservation->end_date->format('d/m/Y')} ({$reservation->numberOfDays()} day(s)). "
                . "Total paid by client: " . number_format($reservation->total_price) . " Frw. Amount owed to you after FlexiRent's commission: " . number_format((float) $reservation->owner_payout) . " Frw."
            );
        }
    }

    /**
     * @param string[] $changes human-readable "Field: old → new" lines
     */
    public function notifyPartnerVehicleUpdated(Vehicle $vehicle, array $changes): void
    {
        if (! $partnerUser = $vehicle->partner?->user) {
            return;
        }

        $this->send(
            $partnerUser->id,
            "Your vehicle {$vehicle->model} was updated by FlexiRent",
            "FlexiRent administration made the following change(s) to {$vehicle->model}: " . implode('; ', $changes) . '.'
        );
    }

    public function notifyAdminsFromPartner(Partner $partner, string $message): void
    {
        // Internal admin-facing message: use the partner's real identity, not
        // the sanitized publicLabel() reserved for public-facing pages.
        $senderLabel = $partner->company_name ?: $partner->user->name;

        $this->notifyAdmins("Reply from partner: {$senderLabel}", $message);
    }

    private function send(int $userId, string $title, string $content, string $channel = 'email'): void
    {
        NotificationRecord::create([
            'user_id' => $userId,
            'channel' => $channel,
            'title' => $title,
            'content' => $content,
        ]);

        try {
            $user = User::find($userId);
            if ($user?->email) {
                Mail::to($user->email)->send(new TransactionalMail($title, $content));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send notification email: ' . $e->getMessage());
        }
    }
}
