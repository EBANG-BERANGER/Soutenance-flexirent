<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Support\Facades\Storage;

/**
 * Rental Contract Generation Module (Ch.5.4.7).
 */
class ContractController extends Controller
{
    public function download(Reservation $reservation)
    {
        abort_unless(
            $reservation->client_id === auth()->id() || auth()->user()->isAdmin(),
            403
        );

        $contract = $reservation->contract;
        abort_if(! $contract, 404, 'Contract not yet generated for this reservation.');

        return Storage::disk('public')->download($contract->pdf_path, "contract-{$reservation->id}.pdf");
    }
}
