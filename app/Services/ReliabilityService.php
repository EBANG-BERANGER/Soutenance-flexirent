<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReliabilityEvent;
use App\Models\User;

/**
 * Client reliability score (academic feature: rule-based, not ML).
 * Every award both logs a dated, reason-coded reliability_events row and
 * increments the client's stored reliability_score in one place, so every
 * call site (the two scheduled commands, admin's manual status edit, and
 * client-initiated cancellation) stays consistent with a single point scale.
 *
 * Scale:
 *   +5  reservation completed normally
 *    0  cancelled within policy (>48h before start) — logged for a
 *       complete history even though it costs nothing
 *  -10  late cancellation (<48h before start)
 *  -10  reservation expired unpaid (24h with no payment)
 */
class ReliabilityService
{
    public function awardCompleted(Reservation $reservation): void
    {
        $this->record($reservation->client, 5, 'Reservation completed normally', $reservation);
    }

    public function awardCancellation(Reservation $reservation, bool $withinPolicy): void
    {
        $withinPolicy
            ? $this->record($reservation->client, 0, 'Cancelled within policy', $reservation)
            : $this->record($reservation->client, -10, 'Late cancellation (less than 48h before start)', $reservation);
    }

    public function awardExpired(Reservation $reservation): void
    {
        $this->record($reservation->client, -10, 'Reservation expired unpaid', $reservation);
    }

    private function record(User $client, int $points, string $reason, Reservation $reservation): void
    {
        ReliabilityEvent::create([
            'user_id' => $client->id,
            'reservation_id' => $reservation->id,
            'points' => $points,
            'reason' => $reason,
            'created_at' => now(),
        ]);

        if ($points !== 0) {
            $client->increment('reliability_score', $points);
        }
    }
}
