<?php

namespace App\Support;

/**
 * Splits a rental duration into the cheapest combination of month/week/day
 * blocks, using whichever of price_per_month / price_per_week the partner
 * has set (both optional) and falling back to the day rate for any part
 * that isn't covered. Shared between Vehicle::chargesFor() (live pricing,
 * shown on the booking form) and Reservation::chargedRateBreakdown() /
 * ReservationController (frozen snapshot pricing, used for confirmed
 * reservations and contracts) so the two never drift apart.
 *
 * A vehicle with no price_per_week/price_per_month set behaves exactly as
 * before: every day is charged at price_per_day.
 */
class RentalPricing
{
    private const DAYS_PER_WEEK = 7;
    private const DAYS_PER_MONTH = 30;

    public static function tieredSubtotal(int $days, float $perDay, ?float $perWeek, ?float $perMonth): array
    {
        $remaining = max($days, 0);
        $months = 0;
        $weeks = 0;

        if ($perMonth !== null && $perMonth > 0) {
            $months = intdiv($remaining, self::DAYS_PER_MONTH);
            $remaining -= $months * self::DAYS_PER_MONTH;
        }

        if ($perWeek !== null && $perWeek > 0) {
            $weeks = intdiv($remaining, self::DAYS_PER_WEEK);
            $remaining -= $weeks * self::DAYS_PER_WEEK;
        }

        $subtotal = ($months * (float) $perMonth) + ($weeks * (float) $perWeek) + ($remaining * $perDay);

        return [
            'months' => $months,
            'weeks' => $weeks,
            'days' => $remaining,
            'subtotal' => round($subtotal, 2),
        ];
    }
}
