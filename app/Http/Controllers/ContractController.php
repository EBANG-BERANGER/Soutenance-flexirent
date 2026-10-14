<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Support\PrivateFiles;
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
        abort_unless(PrivateFiles::ensurePrivate($contract->pdf_path), 404);

        return Storage::disk('local')->download($contract->pdf_path, "contract-{$reservation->id}.pdf");
    }
}
