<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Models\Violation;
use App\Services\NotificationService;
use App\Services\PaymentConfirmationService;
use Illuminate\Http\Request;

/**
 * Partner dashboard: performance metrics + revenue tracking (Ch.5.4.5, Fig 5.5).
 */
class PartnerController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private PaymentConfirmationService $paymentConfirmationService,
    ) {
    }

    public function dashboard(Request $request)
    {
        $partner = auth()->user()->partner;
        abort_if(! $partner, 403);

        $vehicles = $partner->vehicles()->withCount('reservations')->get();
        $vehicleIds = $vehicles->pluck('id');

        $activeReservations = Reservation::whereIn('vehicle_id', $vehicleIds)
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('vehicle', 'client')
            ->latest()
            ->get();

        // Same source of truth as the admin side (Partner::totalReceived(),
        // which itself just calls financialHistoryQuery() with no filters),
        // so this number and what an admin sees for this partner never disagree.
        $totalReceived = $partner->totalReceived();

        $notifications = Notification::where('user_id', auth()->id())->latest()->take(10)->get();

        $violations = Violation::whereIn('vehicle_id', $vehicleIds)
            ->with('vehicle', 'reservation.client')
            ->latest('violation_date')
            ->get();

        // Filterable rental history, same period/client filters and the
        // same underlying query as the admin partner-history page.
        $filters = [
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'client' => $request->input('client'),
        ];
        $historyQuery = Reservation::financialHistoryQuery($vehicleIds, $filters['from'], $filters['to'], $filters['client']);
        $historyTotals = (clone $historyQuery)->selectRaw('
            COALESCE(SUM(total_price), 0) as total_price,
            COALESCE(SUM(owner_payout), 0) as owner_payout,
            COUNT(*) as count
        ')->first();
        $history = $historyQuery->with('vehicle', 'client')
            ->latest('start_date')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString();

        return view('partner.dashboard', compact(
            'partner', 'vehicles', 'activeReservations', 'totalReceived', 'notifications', 'violations',
            'filters', 'history', 'historyTotals'
        ));
    }

    /**
     * A partner reports a traffic violation against one of their own
     * vehicles. The responsible client (if any) is resolved from the
     * violation date and notified even if their rental has already ended.
     */
    public function storeViolation(Request $request, Vehicle $vehicle)
    {
        $partner = auth()->user()->partner;
        abort_if(! $partner, 403);
        abort_unless($vehicle->partner_id === $partner->id, 403);

        $validated = $request->validate([
            'reference_number' => ['required', 'string', 'max:255'],
            'violation_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_due_date' => ['required', 'date', 'after_or_equal:violation_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $reservation = Violation::resolveReservation($vehicle->id, $validated['violation_date']);

        $violation = Violation::create([
            'vehicle_id' => $vehicle->id,
            'reservation_id' => $reservation?->id,
            'reported_by' => auth()->id(),
            'reference_number' => $validated['reference_number'],
            'violation_date' => $validated['violation_date'],
            'payment_due_date' => $validated['payment_due_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $violation->load('vehicle', 'reservation.client', 'reportedBy');

        $this->notificationService->notifyClientOfViolation($violation);
        $this->notificationService->notifyAdminsNewViolation($violation);

        $status = $reservation
            ? "Violation recorded and {$reservation->client->name} has been notified."
            : 'Violation recorded, but no rental was found covering that date, so no client could be notified.';

        return back()->with('status', $status);
    }

    /**
     * A partner has no general vehicle-edit page today (only the
     * availability toggle and this), so the minimum-rental-days field gets
     * its own small, scoped endpoint rather than a bigger edit form.
     */
    public function updateMinRentalDays(Request $request, Vehicle $vehicle)
    {
        $partner = auth()->user()->partner;
        abort_if(! $partner, 403);
        abort_unless($vehicle->partner_id === $partner->id, 403);

        $validated = $request->validate([
            'min_rental_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $vehicle->update($validated);

        return back()->with('status', "Minimum rental for {$vehicle->model} set to {$validated['min_rental_days']} day(s).");
    }

    public function replyToAdmin(Request $request)
    {
        $partner = auth()->user()->partner;
        abort_if(! $partner, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $this->notificationService->notifyAdminsFromPartner($partner, $validated['message']);

        return back()->with('status', 'Your reply has been sent to FlexiRent.');
    }

    /**
     * Lets a partner pull their own vehicle off the market (e.g. for
     * maintenance) or put it back up, without going through admin. A
     * vehicle currently booked by a confirmed reservation can't be flipped
     * back to "available" by hand: syncVehicleAvailability() resolves it
     * to whatever the active reservations actually justify.
     */
    public function toggleVehicleAvailability(Vehicle $vehicle)
    {
        $partner = auth()->user()->partner;
        abort_if(! $partner, 403);
        abort_unless($vehicle->partner_id === $partner->id, 403);

        if ($vehicle->status === 'maintenance') {
            $this->paymentConfirmationService->syncVehicleAvailability($vehicle, force: true);
        } else {
            abort_if($vehicle->isLockedByActiveReservation(), 422, 'This vehicle has a pending or confirmed reservation and cannot be marked unavailable.');
            $vehicle->update(['status' => 'maintenance']);
        }

        return back()->with('status', "{$vehicle->model} is now marked as " . $vehicle->fresh()->status . '.');
    }
}
