<?php

namespace App\Models;

use App\Support\RentalPricing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    /**
     * Vehicles older than this age (in years) are refused at listing time.
     */
    public const MAX_AGE_YEARS = 14;

    public static function minAcceptedYear(): int
    {
        return (int) date('Y') - self::MAX_AGE_YEARS;
    }

    protected $fillable = [
        'partner_id', 'category_id', 'model', 'year', 'propulsion', 'car_type',
        'plate_number', 'chassis_number', 'price_per_day', 'price_per_week', 'price_per_month',
        'min_rental_days', 'driver_available',
        'driver_fee_per_day', 'deposit_amount', 'photo_path', 'status', 'is_suspended',
        'is_featured',
    ];

    protected $casts = [
        'driver_available' => 'boolean',
        'price_per_day' => 'decimal:2',
        'price_per_week' => 'decimal:2',
        'price_per_month' => 'decimal:2',
        'driver_fee_per_day' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'is_suspended' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Full charge breakdown for a given number of days, shown live on the
     * booking form and used server-side so the confirmed total always
     * matches what the client saw (Ch.5.4.3). The rental subtotal is split
     * into the cheapest combination of month/week/day blocks the partner
     * has priced (see App\Support\RentalPricing); driver fee and deposit
     * stay day-rate/flat as before.
     */
    public function chargesFor(int $days, bool $withDriver): array
    {
        $tiered = RentalPricing::tieredSubtotal(
            $days,
            (float) $this->price_per_day,
            $this->price_per_week !== null ? (float) $this->price_per_week : null,
            $this->price_per_month !== null ? (float) $this->price_per_month : null,
        );
        $subtotal = $tiered['subtotal'];
        $driverFee = $withDriver ? $this->driver_fee_per_day * $days : 0;
        $deposit = $this->deposit_amount;

        return [
            'subtotal' => $subtotal,
            'rental_breakdown' => $tiered,
            'driver_fee' => $driverFee,
            'deposit' => $deposit,
            'total' => $subtotal + $driverFee + $deposit,
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function images()
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function violations()
    {
        return $this->hasMany(Violation::class);
    }

    /**
     * Check whether the vehicle is free for a given date range.
     * This is the core conflict-prevention rule (Ch.4.4.1 / 5.4.3).
     */
    public function isAvailableBetween(string $start, string $end, ?int $excludeReservationId = null): bool
    {
        if ($this->status !== 'available' || $this->is_suspended) {
            return false;
        }

        return ! $this->reservations()
            ->whereIn('status', ['pending', 'confirmed'])
            ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId))
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<=', $start)
                          ->where('end_date', '>=', $end);
                    });
            })
            ->exists();
    }

    /**
     * A vehicle with a pending or confirmed reservation is committed to a
     * client: it can't be pulled to maintenance or deleted out from under
     * them. Price/photos remain editable; this only blocks availability
     * and deletion.
     */
    public function isLockedByActiveReservation(): bool
    {
        return $this->reservations()->whereIn('status', ['pending', 'confirmed'])->exists();
    }

    /**
     * A 'booked' vehicle stays listed in the public catalogue rather than
     * disappearing: this tells shoppers when it frees up instead. Takes the
     * latest end date among confirmed reservations, in case more than one is
     * queued up back-to-back.
     */
    public function currentBookingEndDate(): ?\Illuminate\Support\Carbon
    {
        // Uses the already-eager-loaded collection when available (e.g. the
        // catalogue grid preloads 'reservations' for exactly this) instead
        // of always issuing a fresh query per vehicle in the loop.
        $endDate = $this->relationLoaded('reservations')
            ? $this->reservations->where('status', 'confirmed')->max('end_date')
            : $this->reservations()->where('status', 'confirmed')->max('end_date');

        return $endDate ? \Illuminate\Support\Carbon::parse($endDate) : null;
    }

    public function averageRating(): float
    {
        return round(
            Review::whereIn('reservation_id', $this->reservations()->pluck('id'))
                ->avg('rating') ?? 0,
            1
        );
    }

    /**
     * "You might also like" recommendations for the vehicle detail page.
     * Content-based filtering: every other available vehicle is scored
     * against THIS vehicle's own attributes (no ratings/behavior data, no
     * ML library, just a weighted attribute match), then ranked by score:
     *   +3  same category (heaviest weight: body type matters most)
     *   +2  price_per_day within ±20% of this vehicle's rate
     *   +1  same car_type (normal/hybrid/electric)
     *   +1  same propulsion (essence/diesel), when both have one
     * Vehicles scoring 0 (nothing in common) are dropped rather than
     * padding the list with irrelevant suggestions.
     */
    public function similarVehicles(int $limit = 4)
    {
        $priceMin = $this->price_per_day * 0.8;
        $priceMax = $this->price_per_day * 1.2;

        return static::where('id', '!=', $this->id)
            ->where('status', 'available')
            ->where('is_suspended', false)
            ->with('category')
            ->get()
            ->map(function (self $candidate) use ($priceMin, $priceMax) {
                $score = 0;
                if ($candidate->category_id === $this->category_id) {
                    $score += 3;
                }
                if ($candidate->price_per_day >= $priceMin && $candidate->price_per_day <= $priceMax) {
                    $score += 2;
                }
                if ($candidate->car_type === $this->car_type) {
                    $score += 1;
                }
                if ($this->propulsion !== null && $candidate->propulsion === $this->propulsion) {
                    $score += 1;
                }

                $candidate->similarity_score = $score;

                return $candidate;
            })
            ->filter(fn (self $candidate) => $candidate->similarity_score > 0)
            ->sortByDesc('similarity_score')
            ->take($limit)
            ->values();
    }
}
