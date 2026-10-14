<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Services\ContractService;
use App\Services\MomoService;
use App\Services\NotificationService;
use App\Services\PaymentConfirmationService;
use App\Services\ReliabilityService;
use App\Support\RentalPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private ContractService $contractService,
        private MomoService $momoService,
        private PaymentConfirmationService $paymentConfirmationService,
        private ReliabilityService $reliabilityService,
    ) {
    }

    /**
     * Reservation form (Ch.5.5 Figure 5.3).
     */
    public function create(Vehicle $vehicle)
    {
        return view('reservations.create', compact('vehicle'));
    }

    /**
     * Create a reservation (Ch.5.4.3 Reservation and Booking Module).
     * Conflict prevention is enforced via Vehicle::isAvailableBetween().
     */
    public function store(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            // Pickup/return only happens during business hours (08:00-23:00).
            'pickup_time' => ['required', 'date_format:H:i', 'after_or_equal:08:00', 'before_or_equal:23:00'],
            'with_driver' => ['boolean'],
            'id_number' => ['required', 'string', 'max:50'],
            'nationality' => ['required', 'string', 'max:100'],
            // Generic format: alphanumeric, 5-20 characters. No stricter
            // per-country pattern, since there's no verifiable official
            // source to validate against.
            'driving_license_number' => ['required', 'string', 'regex:/^[A-Za-z0-9]{5,20}$/'],
            'delivery_method' => ['required', 'in:pickup,delivery'],
            'delivery_location' => ['required_if:delivery_method,delivery', 'nullable', 'string', 'max:255'],
        ], [
            'driving_license_number.regex' => 'Enter a valid driving license number (5 to 20 letters/digits).',
            'pickup_time.after_or_equal' => 'Pickup/return time must be between 08:00 and 23:00.',
            'pickup_time.before_or_equal' => 'Pickup/return time must be between 08:00 and 23:00.',
        ]);

        $days = Carbon::parse($validated['start_date'])->diffInDays(Carbon::parse($validated['end_date'])) ?: 1;

        if ($days < $vehicle->min_rental_days) {
            return back()->withErrors(['end_date' => "This vehicle requires a minimum rental of {$vehicle->min_rental_days} day(s)."])->withInput();
        }

        $withDriver = $request->boolean('with_driver');
        $charges = $vehicle->chargesFor($days, $withDriver);
        $isDelivery = $validated['delivery_method'] === 'delivery';
        $deliveryFee = $isDelivery ? Reservation::DELIVERY_FEE : 0;
        $total = $charges['total'] + $deliveryFee;

        // Locked so two people booking the same vehicle/dates at the same
        // instant can't both pass the availability check before either one
        // commits. Without this, both requests could read "available" and
        // create overlapping reservations.
        $reservation = DB::transaction(function () use ($vehicle, $validated, $withDriver, $isDelivery, $deliveryFee, $total) {
            $lockedVehicle = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();

            if (! $lockedVehicle->isAvailableBetween($validated['start_date'], $validated['end_date'])) {
                return null;
            }

            return Reservation::create([
                'client_id' => auth()->id(),
                'vehicle_id' => $vehicle->id,
                'id_number' => $validated['id_number'],
                'nationality' => $validated['nationality'],
                'driving_license_number' => $validated['driving_license_number'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'pickup_time' => $validated['pickup_time'],
                'with_driver' => $withDriver,
                'delivery_method' => $validated['delivery_method'],
                'delivery_location' => $isDelivery ? $validated['delivery_location'] : null,
                'delivery_fee' => $deliveryFee,
                'total_price' => $total,
                'original_total_price' => $total,
                // Frozen at the price actually agreed today, so a later
                // partner price change never retroactively alters what this
                // client owes or what the contract PDF shows.
                'price_per_day_snapshot' => $lockedVehicle->price_per_day,
                'price_per_week_snapshot' => $lockedVehicle->price_per_week,
                'price_per_month_snapshot' => $lockedVehicle->price_per_month,
                'driver_fee_per_day_snapshot' => $lockedVehicle->driver_fee_per_day,
                'vehicle_deposit_snapshot' => $lockedVehicle->deposit_amount,
                'status' => 'pending',
            ]);
        });

        if (! $reservation) {
            return back()->withErrors(['start_date' => 'This vehicle is not available for the selected dates.']);
        }

        // Generated immediately (not only once paid) so the client can read
        // the full agreement (including the current payment situation)
        // before committing to pay.
        $this->contractService->generateForReservation($reservation);

        $this->notificationService->notifyReservationCreated($reservation);
        $this->notificationService->notifyAdminsNewReservation($reservation);

        return redirect()->route('reservations.payment', $reservation)
            ->with('status', 'Reservation created. Please complete payment to confirm.');
    }

    public function paymentForm(Reservation $reservation)
    {
        $this->authorizeOwner($reservation);

        $merchantNumber = $this->momoService->merchantNumber();

        return view('reservations.payment', compact('reservation', 'merchantNumber'));
    }

    public function index()
    {
        $reservations = auth()->user()->reservations()
            ->with('vehicle')
            ->latest()
            ->paginate(10);

        $reliabilityEvents = auth()->user()->reliabilityEvents()->latest('created_at')->get();

        return view('reservations.index', compact('reservations', 'reliabilityEvents'));
    }

    /**
     * Let a client amend an already-confirmed reservation's dates/driver
     * option. A change fee (10% of the original invoice) is added to the
     * total and shown before the client confirms the change.
     */
    public function edit(Reservation $reservation)
    {
        $this->authorizeOwner($reservation);
        abort_unless($reservation->status === 'confirmed', 422, 'Only confirmed reservations can be modified.');

        $changeFee = $reservation->changeFeeAmount();

        return view('reservations.edit', compact('reservation', 'changeFee'));
    }

    public function update(Request $request, Reservation $reservation)
    {
        $this->authorizeOwner($reservation);
        abort_unless($reservation->status === 'confirmed', 422, 'Only confirmed reservations can be modified.');

        $validated = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'pickup_time' => ['required', 'date_format:H:i', 'after_or_equal:08:00', 'before_or_equal:23:00'],
            'with_driver' => ['boolean'],
        ], [
            'pickup_time.after_or_equal' => 'Pickup/return time must be between 08:00 and 23:00.',
            'pickup_time.before_or_equal' => 'Pickup/return time must be between 08:00 and 23:00.',
        ]);

        $vehicle = $reservation->vehicle;
        $days = Carbon::parse($validated['start_date'])->diffInDays(Carbon::parse($validated['end_date'])) ?: 1;

        if ($days < $vehicle->min_rental_days) {
            return back()->withErrors(['end_date' => "This vehicle requires a minimum rental of {$vehicle->min_rental_days} day(s)."]);
        }

        $withDriver = $request->boolean('with_driver');
        $changeFee = $reservation->changeFeeAmount();

        // Locked so a concurrent booking/edit on the same vehicle can't slip
        // in between the availability check and the update committing.
        $newTotal = DB::transaction(function () use ($reservation, $vehicle, $validated, $withDriver, $days, $changeFee) {
            $lockedVehicle = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();

            if (! $lockedVehicle->isAvailableBetween($validated['start_date'], $validated['end_date'], $reservation->id)) {
                return null;
            }

            // Recompute at the ORIGINALLY agreed rate (price_per_day_snapshot),
            // not the vehicle's current live price. A partner changing their
            // listing price shouldn't retroactively change what an existing
            // client is charged when they modify their dates.
            $pricePerDay = (float) ($reservation->price_per_day_snapshot ?? $lockedVehicle->price_per_day);
            $pricePerWeek = $reservation->price_per_week_snapshot ?? $lockedVehicle->price_per_week;
            $pricePerMonth = $reservation->price_per_month_snapshot ?? $lockedVehicle->price_per_month;
            $driverFeePerDay = (float) ($reservation->driver_fee_per_day_snapshot ?? $lockedVehicle->driver_fee_per_day);
            $deposit = (float) ($reservation->vehicle_deposit_snapshot ?? $lockedVehicle->deposit_amount);
            $subtotal = RentalPricing::tieredSubtotal(
                $days,
                $pricePerDay,
                $pricePerWeek !== null ? (float) $pricePerWeek : null,
                $pricePerMonth !== null ? (float) $pricePerMonth : null,
            )['subtotal'];
            $driverFee = $withDriver ? $driverFeePerDay * $days : 0;
            // delivery_fee isn't part of the rate breakdown (it's not a vehicle
            // charge) and delivery_method/location aren't editable here, so
            // carry the existing fee forward: omitting it silently
            // under-charged every delivery reservation that got modified.
            $total = $subtotal + $driverFee + $deposit + $changeFee + (float) $reservation->delivery_fee;

            $reservation->update([
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'pickup_time' => $validated['pickup_time'],
                'with_driver' => $withDriver,
                'total_price' => $total,
                'payment_status' => $reservation->amountPaid() >= $total ? 'paid' : ($reservation->amountPaid() > 0 ? 'deposit_paid' : 'unpaid'),
            ]);

            return $total;
        });

        if ($newTotal === null) {
            return back()->withErrors(['start_date' => 'This vehicle is not available for the selected dates.']);
        }

        // total_price just changed (change fee added), so the commission
        // split computed at payment-confirmation time is now stale: recompute
        // it against the new total so platform_commission/owner_payout stay accurate.
        $reservation->applyCommissionSplit();

        Payment::create([
            'reservation_id' => $reservation->id,
            'method' => 'manual',
            'type' => 'change_fee',
            'amount' => $changeFee,
            'status' => 'pending',
        ]);

        $this->contractService->generateForReservation($reservation);
        $this->notificationService->notifyReservationUpdated($reservation, $changeFee);

        return redirect()->route('reservations.payment', $reservation)
            ->with('status', "Reservation updated. A change fee of {$this->formatAmount($changeFee)} RWF has been added. Remaining balance: {$this->formatAmount($reservation->balanceDue())} RWF.");
    }

    /**
     * Client-initiated cancellation, distinct from AdminController's
     * cancelReservation(): strictly the reservation's own client, only from
     * 'pending'/'confirmed', and (unlike the admin path) charges a
     * graduated fee based on how much notice was given and how much has
     * already been paid, rather than cancelling for free.
     */
    public function cancel(Reservation $reservation)
    {
        abort_unless($reservation->client_id === auth()->id(), 403);
        abort_unless(in_array($reservation->status, ['pending', 'confirmed']), 422, 'This reservation can no longer be cancelled.');

        $hoursUntilStart = (Carbon::parse($reservation->start_date)->timestamp - now()->timestamp) / 3600;
        $moreThan48h = $hoursUntilStart > 48;

        $retained = 0.0;
        $refund = 0.0;

        if ($reservation->payment_status === 'unpaid') {
            // No charge: nothing was ever paid.
        } elseif ($reservation->payment_status === 'deposit_paid') {
            $deposit = (float) ($reservation->deposit_amount ?? 0);
            $retained = $moreThan48h ? round($deposit * 0.10, 2) : $deposit;
            $refund = round($deposit - $retained, 2);
        } else { // 'paid'
            $total = (float) $reservation->total_price;
            $retained = $moreThan48h ? round($total * 0.10, 2) : round($total * 0.20, 2);
            $refund = round($total - $retained, 2);
        }

        if ($refund > 0) {
            Payment::create([
                'reservation_id' => $reservation->id,
                'method' => 'manual',
                'type' => 'refund',
                'amount' => $refund,
                'status' => 'pending',
            ]);
        }

        $reservation->update([
            'status_before_cancellation' => $reservation->status,
            'status' => 'cancelled',
        ]);

        $this->paymentConfirmationService->syncVehicleAvailability($reservation->vehicle);
        $this->notificationService->notifyReservationCancelledByClient($reservation, $retained, $refund);
        $this->reliabilityService->awardCancellation($reservation, $moreThan48h);

        // Deliberately final-sounding: only the retained/refund amounts are
        // shown, with no mention that an admin could later reactivate it:
        // reactivation is an admin-only dispute-resolution tool, not
        // something a client should expect to request.
        $message = $retained > 0
            ? "Reservation #{$reservation->id} has been cancelled. {$this->formatAmount($retained)} RWF has been retained" . ($refund > 0 ? ", and {$this->formatAmount($refund)} RWF will be refunded." : '.')
            : "Reservation #{$reservation->id} has been cancelled. No charges were applied.";

        return redirect()->route('reservations.index')->with('status', $message);
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount);
    }

    private function authorizeOwner(Reservation $reservation): void
    {
        abort_unless(
            $reservation->client_id === auth()->id() || auth()->user()->isAdmin(),
            403
        );
    }
}
