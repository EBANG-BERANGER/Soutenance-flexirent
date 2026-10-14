<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ContractMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Contract $contract)
    {
    }

    public function build()
    {
        $reservation = $this->contract->reservation;

        return $this->subject("Your FlexiRent contract: Reservation #{$reservation->id}")
            ->view('emails.contract', ['reservation' => $reservation]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath(Storage::disk('local')->path($this->contract->pdf_path))
                ->as("flexirent-contract-{$this->contract->reservation_id}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
