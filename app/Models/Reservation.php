<?php

namespace App\Models;

use App\Support\RentalPricing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    /** Deposit required for a "reserve only" booking, as a fraction of the total price. */
    const DEPOSIT_RATE = 0.15;

    /** Fee charged when a confirmed reservation is amended, as a fraction of the original invoice. */
    const CHANGE_FEE_RATE = 0.10;

    /** Share of the total price FlexiRent keeps as commission; the rest is owed to the vehicle owner. */
    const PLATFORM_COMMISSION_RATE = 0.10;

    /** Flat fee charged when the client asks for the vehicle to be delivered instead of picking it up. */
    const DELIVERY_FEE = 10000;

    protected $fillable = [
        'client_id', 'vehicle_id', 'id_number', 'driving_license_number', 'nationality',
        'start_date', 'end_date', 'pickup_time', 'with_driver', 'total_price', 'status', 'status_before_cancellation',
        'payment_type', 'payment_status', 'original_total_price', 'deposit_amount',
        'platform_commission', 'owner_payout',
        'delivery_method', 'delivery_location', 'delivery_fee',
        'price_per_day_snapshot', 'price_per_week_snapshot', 'price_per_month_snapshot',
        'driver_fee_per_day_snapshot', 'vehicle_deposit_snapshot',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'with_driver' => 'boolean',
        'platform_commission' => 'decimal:2',
        'owner_payout' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

    public function violations()
    {
        return $this->hasMany(Violation::class);
    }

    public function reliabilityEvents()
    {
        return $this->hasMany(ReliabilityEvent::class);
    }

    /**
     * Single source of truth behind every financial history view (admin's
     * per-vehicle history, admin's per-partner history, the partner
     * dashboard, and Partner::totalReceived()) — so the same reservation
     * always counts the same way everywhere, and a filter applied on one
     * screen means the same thing as on any other.
     *
     * Only 'confirmed'/'completed' reservations count as real, priced
     * rentals; 'pending' (never paid) and 'cancelled' ones are excluded.
     * $from/$to filter on start_date (inclusive); $client matches the
     * booking client's name or email (partial, case-insensitive).
     */
    public static function financialHistoryQuery($vehicleIds, ?string $from = null, ?string $to = null, ?string $client = null)
    {
        return static::whereIn('vehicle_id', (array) (
                $vehicleIds instanceof \Illuminate\Support\Collection ? $vehicleIds->all() : $vehicleIds
            ))
            ->whereIn('status', ['confirmed', 'completed'])
            ->when($from, fn ($q) => $q->whereDate('start_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('start_date', '<=', $to))
            ->when($client, function ($q) use ($client) {
                $q->whereHas('client', function ($cq) use ($client) {
                    $cq->where('name', 'like', "%{$client}%")
                        ->orWhere('email', 'like', "%{$client}%");
                });
            });
    }

    public function isDelivery(): bool
    {
        return $this->delivery_method === 'delivery';
    }

    public function numberOfDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) ?: 1;
    }

    /**
     * Rate breakdown at the price actually agreed when this reservation was
     * made: price_per_day/driver_fee_per_day/deposit_amount as they stood
     * on the vehicle at booking time, not whatever the partner has since
     * changed them to. Falls back to the vehicle's current price only for
     * reservations created before these snapshot columns existed.
     */
    public function chargedRateBreakdown(): array
    {
        $pricePerDay = (float) ($this->price_per_day_snapshot ?? $this->vehicle->price_per_day);
        $pricePerWeek = $this->price_per_week_snapshot ?? $this->vehicle->price_per_week;
        $pricePerMonth = $this->price_per_month_snapshot ?? $this->vehicle->price_per_month;
        $driverFeePerDay = (float) ($this->driver_fee_per_day_snapshot ?? $this->vehicle->driver_fee_per_day);
        $deposit = (float) ($this->vehicle_deposit_snapshot ?? $this->vehicle->deposit_amount);

        $tiered = RentalPricing::tieredSubtotal(
            $this->numberOfDays(),
            $pricePerDay,
            $pricePerWeek !== null ? (float) $pricePerWeek : null,
            $pricePerMonth !== null ? (float) $pricePerMonth : null,
        );
        $subtotal = $tiered['subtotal'];
        $driverFee = $this->with_driver ? $driverFeePerDay * $this->numberOfDays() : 0;

        return [
            'price_per_day' => $pricePerDay,
            'rental_breakdown' => $tiered,
            'subtotal' => $subtotal,
            'driver_fee' => $driverFee,
            'deposit' => $deposit,
            'delivery_fee' => (float) $this->delivery_fee,
            'total' => $subtotal + $driverFee + $deposit + (float) $this->delivery_fee,
        ];
    }

    public function amountPaid(): float
    {
        return (float) $this->payments()->where('status', 'paid')->sum('amount');
    }

    public function balanceDue(): float
    {
        return max(0, $this->total_price - $this->amountPaid());
    }

    public function depositDue(): float
    {
        return round($this->total_price * self::DEPOSIT_RATE, 2);
    }

    public function changeFeeAmount(): float
    {
        return round(($this->original_total_price ?? $this->total_price) * self::CHANGE_FEE_RATE, 2);
    }

    /**
     * Nothing currently flips a reservation's status to 'completed'
     * automatically once its end date passes (only an admin editing it by
     * hand does), so review eligibility can't rely on that status alone:
     * a confirmed rental whose dates are in the past is just as reviewable.
     */
    public function isEligibleForReview(): bool
    {
        return $this->status === 'completed'
            || ($this->status === 'confirmed' && $this->end_date->isPast());
    }

    /**
     * Split the total price between FlexiRent's commission and the owner's payout,
     * and persist the result. Called once payment is confirmed (paid/deposit_paid).
     */
    public function applyCommissionSplit(): void
    {
        $commission = round($this->total_price * self::PLATFORM_COMMISSION_RATE, 2);

        $this->forceFill([
            'platform_commission' => $commission,
            'owner_payout' => round($this->total_price - $commission, 2),
        ])->save();
    }
}
