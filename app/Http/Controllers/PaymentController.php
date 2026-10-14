<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Reservation;
use App\Services\MomoService;
use App\Services\NotificationService;
use App\Services\PaymentConfirmationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private MomoService $momoService,
        private PaymentConfirmationService $paymentConfirmationService,
    ) {
    }

    /**
     * Payment Integration Module (Ch.5.4.4).
     * MTN MoMo / Airtel Money are represented as manual USSD instructions
     * here, since no live Collections API merchant account exists yet.
     * Every payment is recorded as "pending" and only becomes "paid" once
     * an admin confirms it (with the real transaction reference), which is
     * also the trigger that confirms the reservation and issues the
     * contract. This applies uniformly to mobile money and office payments.
     */
    public function store(Request $request, Reservation $reservation)
    {
        $this->authorizeOwner($reservation);
        abort_if($reservation->payment_status === 'paid', 422, 'This reservation has already been paid in full.');

        $isFirstPayment = $reservation->payment_status === 'unpaid';

        $validated = $request->validate([
            'method' => ['required', 'in:mtn_momo,airtel_money,manual'],
            'phone_number' => ['required_unless:method,manual', 'nullable', 'string'],
            'payment_type' => [$isFirstPayment ? 'required' : 'nullable', 'in:full,deposit'],
            'accepted_terms' => ['accepted'],
        ], [
            'accepted_terms.accepted' => 'You must accept the Terms & Cancellation Policy before paying.',
        ]);

        if ($isFirstPayment && ($validated['payment_type'] ?? null) === 'deposit' && $reservation->client->isRestrictedToFullPayment()) {
            return back()->withErrors(['payment_type' => 'Full payment is required for this booking due to repeated late cancellations or unpaid expiries on your account. Deposit-only is not available.']);
        }

        if ($isFirstPayment) {
            $paymentType = $validated['payment_type'];
            $amount = $paymentType === 'deposit' ? $reservation->depositDue() : $reservation->total_price;
            $recordType = $paymentType === 'deposit' ? 'deposit' : 'full';

            $reservation->update([
                'payment_type' => $paymentType,
                'deposit_amount' => $paymentType === 'deposit' ? $amount : null,
            ]);
        } else {
            $amount = $reservation->balanceDue();
            $recordType = 'balance';
        }

        $ussdCode = in_array($validated['method'], ['mtn_momo', 'airtel_money'])
            ? $this->momoService->ussdCode($amount)
            : null;

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'method' => $validated['method'],
            'type' => $recordType,
            'amount' => $amount,
            'transaction_ref' => null,
            'ussd_code' => $ussdCode,
            'status' => 'pending',
        ]);

        $this->notificationService->notifyPaymentRequested($reservation, $payment);

        if (in_array($validated['method'], ['mtn_momo', 'airtel_money'])) {
            return redirect()->route('payments.confirming', ['reservation' => $reservation, 'payment' => $payment]);
        }

        return redirect()->route('reservations.index')
            ->with('status', "Payment request recorded (Ref pending). Please pay {$this->formatAmount($amount)} RWF at the office to confirm your reservation.");
    }

    /**
     * The "confirm on your phone" portal shown right after a Mobile Money
     * payment is submitted, mirroring what a real MTN MoMo / Airtel Money
     * push confirmation screen looks like from the client's side. There is
     * no live merchant account behind it yet, so confirmation is simulated
     * (see simulateConfirm()) rather than driven by a real webhook.
     */
    public function confirming(Reservation $reservation, Payment $payment)
    {
        $this->authorizeOwner($reservation);
        abort_unless($payment->reservation_id === $reservation->id, 404);

        if ($payment->status === 'paid') {
            return redirect()->route('reservations.index')->with('status', 'This payment has already been confirmed.');
        }

        return view('reservations.payment-confirming', compact('reservation', 'payment'));
    }

    /**
     * Simulates the asynchronous confirmation a real MoMo Collections API
     * webhook would send once the client approves the prompt on their
     * phone. Triggered automatically by the confirming page's timer (and
     * available as an instant "simulate now" button for demos).
     */
    public function simulateConfirm(Reservation $reservation, Payment $payment)
    {
        $this->authorizeOwner($reservation);
        abort_unless($payment->reservation_id === $reservation->id, 404);
        abort_unless(in_array($payment->method, ['mtn_momo', 'airtel_money']), 422);

        $this->paymentConfirmationService->confirm($payment, 'MOMO-SIM-' . now()->timestamp);

        return response()->json(['redirect' => route('reservations.index')]);
    }

    private function authorizeOwner(Reservation $reservation): void
    {
        abort_unless(
            $reservation->client_id === auth()->id() || auth()->user()->isAdmin(),
            403
        );
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount);
    }
}
