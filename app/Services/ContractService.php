<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Rental Contract Generation Module (Ch.5.4.7).
 * Renders a PDF agreement from the confirmed reservation and stores it,
 * making it downloadable by the reservation's own client (or an admin);
 * see ContractController::download(). Partners have no download route or
 * dashboard link to it today.
 */
class ContractService
{
    public function generateForReservation(Reservation $reservation): Contract
    {
        $reservation->load('client', 'vehicle.partner.user');

        $pdf = Pdf::loadView('contracts.template', [
            'reservation' => $reservation,
        ]);

        $path = "contracts/reservation-{$reservation->id}.pdf";
        // Private disk: contracts hold ID and licence numbers, never public URLs.
        Storage::disk(\App\Support\PrivateFiles::DISK)->put($path, $pdf->output());
        Storage::disk('public')->delete($path); // drop any old public copy

        return Contract::updateOrCreate(
            ['reservation_id' => $reservation->id],
            ['pdf_path' => $path, 'generated_at' => now()]
        );
    }
}
