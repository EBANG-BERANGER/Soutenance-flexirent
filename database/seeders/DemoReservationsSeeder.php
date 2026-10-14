<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ContractService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Gives the 32 demo clients (DemoClientsSeeder) a finished rental history:
 * completed reservations, paid payments, generated contracts, and reviews
 * for most of them, so the admin dashboard, Reservations, Payments,
 * Contracts and Reviews pages all have realistic content for the defense
 * instead of sitting empty.
 */
class DemoReservationsSeeder extends Seeder
{
    private array $comments = [
        5 => [
            'Excellent service, the car was clean and exactly as described. Will rent again!',
            'Très bonne expérience, le véhicule était impeccable et le processus de réservation simple.',
            'Smooth pickup, friendly partner, no surprises on the price. Highly recommend FlexiRent.',
            'Voiture parfaite pour mon séjour à Kigali, paiement Mobile Money très pratique.',
            'Great communication throughout and the contract made everything feel official and safe.',
        ],
        4 => [
            'Good experience overall, car was a bit dusty on pickup but ran perfectly.',
            'Bon rapport qualité-prix, léger retard à la livraison mais rien de grave.',
            'Reliable vehicle for the week, would book again for a longer trip.',
        ],
        3 => [
            'Correct mais le carburant n\'était pas plein au départ, sinon tout s\'est bien passé.',
            'Decent rental, communication with the partner could have been faster.',
        ],
    ];

    public function run(): void
    {
        $clients = User::where(function ($q) {
            $q->where('email', 'like', '%@gmail.com')->orWhere('email', 'like', '%@outlook.com');
        })->where('role', 'client')->get();
        $vehicles = Vehicle::all();

        if ($clients->isEmpty() || $vehicles->isEmpty()) {
            $this->command?->warn('No demo clients or vehicles found, run DemoClientsSeeder first.');

            return;
        }

        $contractService = app(ContractService::class);
        $reservationCount = 0;
        $reviewCount = 0;

        foreach ($clients as $i => $client) {
            // Not every client rented a car; about two-thirds did.
            if ($i % 3 === 2) {
                continue;
            }

            $vehicle = $vehicles->random();
            $days = random_int(2, 9);
            $tripsAgo = random_int(5, 150); // spread rentals over the last ~5 months
            $start = Carbon::now()->subDays($tripsAgo + $days);
            $end = Carbon::now()->subDays($tripsAgo);
            $withDriver = random_int(1, 100) <= 20;
            $isDelivery = random_int(1, 100) <= 30;
            $deliveryFee = $isDelivery ? 10000 : 0;

            $subtotal = $vehicle->price_per_day * $days;
            $driverFee = $withDriver ? $vehicle->driver_fee_per_day * $days : 0;
            $total = $subtotal + $driverFee + $deliveryFee;

            $reservation = $client->reservations()->create([
                'vehicle_id' => $vehicle->id,
                'id_number' => strtoupper(fake()->bothify('ID#######')),
                'driving_license_number' => strtoupper(fake()->bothify('DL-####??')),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'with_driver' => $withDriver,
                'delivery_method' => $isDelivery ? 'delivery' : 'pickup',
                'delivery_location' => $isDelivery ? 'Kigali Heights, KG 7 Ave' : null,
                'delivery_fee' => $deliveryFee,
                'total_price' => $total,
                'original_total_price' => $total,
                'payment_type' => 'full',
                'payment_status' => 'paid',
                'status' => 'completed',
            ]);
            $reservation->applyCommissionSplit();
            $reservationCount++;

            Payment::create([
                'reservation_id' => $reservation->id,
                'method' => fake()->randomElement(['mtn_momo', 'airtel_money']),
                'type' => 'full',
                'amount' => $total,
                'transaction_ref' => 'DEMO-' . strtoupper(fake()->bothify('??######')),
                'status' => 'paid',
            ]);

            $contractService->generateForReservation($reservation);

            // Most, but not all, finished rentals get a review.
            if (random_int(1, 100) <= 70) {
                $rating = fake()->randomElement([5, 5, 5, 4, 4, 3]);
                Review::create([
                    'reservation_id' => $reservation->id,
                    'rating' => $rating,
                    'comment' => fake()->randomElement($this->comments[$rating]),
                ]);
                $reviewCount++;
            }
        }

        $this->command?->info("Seeded {$reservationCount} completed reservations (with payments + contracts) and {$reviewCount} reviews.");

        $this->bookCurrentlyBookedVehicles($clients);
    }

    /**
     * Vehicles seeded with status='booked' (DemoFleetSeeder) need a real
     * confirmed, in-progress reservation so currentBookingEndDate() has
     * something to report, otherwise the "Booked until ..." badge on the
     * catalogue has no date to show.
     */
    private function bookCurrentlyBookedVehicles($clients): void
    {
        $vehicles = Vehicle::where('status', 'booked')->get();
        $count = 0;

        foreach ($vehicles as $vehicle) {
            $client = $clients->random();
            $days = random_int(3, 10);
            $start = Carbon::now()->subDays(random_int(0, 2));
            $end = Carbon::now()->addDays(random_int(1, 12));

            $subtotal = $vehicle->price_per_day * $days;
            $reservation = $client->reservations()->create([
                'vehicle_id' => $vehicle->id,
                'id_number' => strtoupper(fake()->bothify('ID#######')),
                'driving_license_number' => strtoupper(fake()->bothify('DL-####??')),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'with_driver' => false,
                'delivery_method' => 'pickup',
                'delivery_fee' => 0,
                'total_price' => $subtotal,
                'original_total_price' => $subtotal,
                'payment_type' => 'full',
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);
            $reservation->applyCommissionSplit();

            Payment::create([
                'reservation_id' => $reservation->id,
                'method' => fake()->randomElement(['mtn_momo', 'airtel_money']),
                'type' => 'full',
                'amount' => $subtotal,
                'transaction_ref' => 'DEMO-' . strtoupper(fake()->bothify('??######')),
                'status' => 'paid',
            ]);

            $count++;
        }

        if ($count) {
            $this->command?->info("Booked {$count} currently-booked vehicles with a live confirmed reservation.");
        }
    }
}
