@extends('layouts.app')
@section('title', 'Payment')
@section('content')
<h2 class="mb-4">Complete Payment</h2>
<div class="row">
    <div class="col-12 col-md-7">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <p class="mb-0">Reservation #{{ $reservation->id }}: {{ $reservation->vehicle->model }}</p>
            @if($reservation->contract)
                <a href="{{ route('contracts.download', $reservation) }}" class="btn btn-outline-light btn-sm">Read the contract before paying</a>
            @endif
        </div>

        @if($reservation->payment_status === 'deposit_paid')
            <div class="card p-3 mb-3 border-success">
                <p class="mb-1 text-muted small">Deposit already paid</p>
                <p class="fs-5 fw-bold text-success mb-0">{{ number_format($reservation->amountPaid()) }} RWF</p>
            </div>
            <p class="fs-4 fw-bold text-gold">Remaining balance: {{ number_format($reservation->balanceDue()) }} RWF</p>
            <p class="text-muted small">Total reservation amount: {{ number_format($reservation->total_price) }} RWF</p>
        @else
            <p class="fs-4 fw-bold text-gold">{{ number_format($reservation->total_price) }} RWF</p>
        @endif

        <form method="POST" action="{{ route('payments.store', $reservation) }}"
              data-no-spinner
              x-data="{
                paymentType: 'full',
                method: 'mtn_momo',
                merchant: '{{ $merchantNumber ?: '000000' }}',
                fullAmount: {{ (int) $reservation->total_price }},
                depositAmount: {{ (int) $reservation->depositDue() }},
                balanceAmount: {{ (int) $reservation->balanceDue() }},
                isFirstPayment: {{ $reservation->payment_status === 'unpaid' ? 'true' : 'false' }},
                submitting: false,
                get amount() {
                    if (!this.isFirstPayment) return this.balanceAmount;
                    return this.paymentType === 'deposit' ? this.depositAmount : this.fullAmount;
                },
                get ussd() {
                    return '*182*1*1*' + this.merchant + '*' + this.amount + '#';
                }
              }"
              @submit="submitting = true">
            @csrf

            @if($reservation->payment_status === 'unpaid')
            <div class="mb-3">
                <label class="form-label fw-bold">How do you want to pay?</label>
                <div class="fx-pay-options">
                    <label class="fx-pay-option">
                        <input type="radio" name="payment_type" value="full" x-model="paymentType">
                        <div>
                            <strong>Pay in full</strong>
                            <p class="text-muted small mb-0">{{ number_format($reservation->total_price) }} RWF now. Reservation confirmed immediately.</p>
                        </div>
                    </label>
                    @if(auth()->user()->isRestrictedToFullPayment())
                        <div class="fx-pay-option" style="opacity:.6; cursor:not-allowed;">
                            <input type="radio" disabled>
                            <div>
                                <strong>Reserve only (unavailable)</strong>
                                <p class="text-muted small mb-0">Not available for this account due to repeated late cancellations or unpaid expiries. Full payment is required.</p>
                            </div>
                        </div>
                    @else
                        <label class="fx-pay-option">
                            <input type="radio" name="payment_type" value="deposit" x-model="paymentType">
                            <div>
                                <strong>Reserve only</strong>
                                <p class="text-muted small mb-0">Pay a {{ number_format(\App\Models\Reservation::DEPOSIT_RATE * 100) }}% deposit now ({{ number_format($reservation->depositDue()) }} RWF). Balance of {{ number_format($reservation->total_price - $reservation->depositDue()) }} RWF is due when you pick up the vehicle.</p>
                            </div>
                        </label>
                    @endif
                </div>
            </div>
            @endif

            <div class="mb-3">
                <label class="form-label">Payment method</label>
                <select name="method" x-model="method" class="form-select">
                    <option value="mtn_momo">MTN Mobile Money</option>
                    <option value="airtel_money">Airtel Money</option>
                    <option value="manual">Pay at office (manual)</option>
                </select>
            </div>
            <div class="mb-3" x-show="method !== 'manual'" x-cloak>
                <label class="form-label">Mobile Money number</label>
                <input type="text" name="phone_number" class="form-control" placeholder="07XXXXXXXX">
            </div>

            <div class="fx-momo-box" x-show="method === 'mtn_momo' || method === 'airtel_money'" x-cloak>
                <div class="fx-momo-label">Dial this code on your phone to pay <strong x-text="amount.toLocaleString('en-US') + ' RWF'"></strong></div>
                <div class="fx-momo-code" x-text="ussd"></div>
                <p class="text-muted small mb-0">FlexiRent will confirm your reservation once the payment is received. Keep your phone's confirmation SMS as proof.</p>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" name="accepted_terms" value="1" class="form-check-input" id="accepted_terms" required>
                <label class="form-check-label" for="accepted_terms">
                    I have read and accept the <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms &amp; Cancellation Policy</a>.
                </label>
            </div>

            <button class="btn btn-gold w-100 mt-3" :disabled="submitting">
                <span x-show="!submitting">Record payment request</span>
                <span x-show="submitting" x-cloak>
                    <span class="fx-spinner"></span> Processing&hellip;
                </span>
            </button>
        </form>
    </div>
</div>
@endsection
