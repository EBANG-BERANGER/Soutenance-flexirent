<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Review;
use Illuminate\Http\Request;

/**
 * Rating and Review Module (Ch.5.4.9).
 */
class ReviewController extends Controller
{
    public function create(Reservation $reservation)
    {
        abort_unless($reservation->client_id === auth()->id(), 403);
        abort_unless($reservation->isEligibleForReview(), 422, 'You can only review rentals that have finished.');

        return view('reviews.create', compact('reservation'));
    }

    public function store(Request $request, Reservation $reservation)
    {
        abort_unless($reservation->client_id === auth()->id(), 403);
        abort_unless($reservation->isEligibleForReview(), 422, 'You can only review rentals that have finished.');
        abort_if($reservation->review, 422, 'You have already reviewed this rental.');

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::create([
            'reservation_id' => $reservation->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return redirect()->route('reservations.index')->with('status', 'Thank you for your review!');
    }
}
