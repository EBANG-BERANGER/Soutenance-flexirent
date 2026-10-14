<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Violation extends Model
{
    protected $fillable = [
        'vehicle_id', 'reservation_id', 'reported_by',
        'reference_number', 'violation_date', 'payment_due_date',
        'status', 'notes',
    ];

    protected $casts = [
        'violation_date' => 'date',
        'payment_due_date' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Finds whichever reservation had this vehicle out on the violation
     * date, regardless of whether that rental has since ended, so the
     * client actually responsible can be identified and notified. Only
     * 'confirmed' or 'completed' reservations count as a real rental;
     * 'pending'/'cancelled' ones never had the car in the client's hands.
     */
    public static function resolveReservation(int $vehicleId, string $violationDate): ?Reservation
    {
        return Reservation::where('vehicle_id', $vehicleId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereDate('start_date', '<=', $violationDate)
            ->whereDate('end_date', '>=', $violationDate)
            ->first();
    }
}
