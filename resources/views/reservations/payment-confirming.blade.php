@extends('layouts.app')
@section('title', 'Confirm on your phone')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card p-4 auth-card text-center"
             x-data="{
                confirmed: false,
                error: false,
                simulate() {
                    if (this.confirmed) return;
                    fetch('{{ route('payments.simulate-confirm', [$reservation, $payment]) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    })
                        .then(r => { if (!r.ok) throw new Error('failed'); return r.json(); })
                        .then(data => {
                            this.confirmed = true;
                            setTimeout(() => { window.location.href = data.redirect; }, 1800);
                        })
                        .catch(() => { this.error = true; });
                },
                init() {
                    // Only in demo mode (MOMO_SIMULATION). Otherwise the
                    // payment stays pending until an admin confirms it.
                    if (!@json($simulationEnabled)) return;
                    // Mirrors the delay a real MTN MoMo / Airtel Money push
                    // confirmation would take once the client approves it.
                    const delay = 4000 + Math.random() * 3000;
                    setTimeout(() => this.simulate(), delay);
                }
             }">

            <template x-if="!confirmed && !error">
                <div>
                    <div class="fx-momo-phone">
                        <div class="fx-momo-phone-pulse"></div>
                        <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/></svg>
                    </div>
                    <h3 class="mb-2">Confirm on your phone</h3>
                    <p class="text-muted">
                        A payment request for <strong>{{ number_format($payment->amount) }} RWF</strong> has been sent to your Mobile Money line.
                        Approve it on your phone to confirm reservation #{{ $reservation->id }}.
                    </p>
                    @if($payment->ussd_code)
                        <div class="fx-momo-box text-start">
                            <div class="fx-momo-label">Didn't get the prompt? Dial this code instead</div>
                            <div class="fx-momo-code">{{ $payment->ussd_code }}</div>
                        </div>
                    @endif
                    <div class="d-flex align-items-center justify-content-center gap-2 mt-4 mb-1">
                        <span class="fx-spinner"></span>
                        <span class="text-muted small">Waiting for confirmation&hellip;</span>
                    </div>
                    @if($simulationEnabled)
                    <button type="button" class="btn btn-outline-light btn-sm mt-3" @click="simulate()">
                        Simulate confirmation now
                    </button>
                    <p class="text-muted mt-3 mb-0" style="font-size:.75rem;">
                        Demo simulation &mdash; no live MTN MoMo / Airtel Money merchant account is connected yet.
                    </p>
                    @else
                    <p class="text-muted mt-3 mb-0 small">
                        Once your payment is received, FlexiRent confirms it and your reservation and contract appear in
                        <a href="{{ route('reservations.index') }}">My reservations</a>.
                    </p>
                    @endif
                </div>
            </template>

            <template x-if="confirmed">
                <div>
                    <div class="fx-momo-check">&check;</div>
                    <h3 class="mb-2">Payment confirmed</h3>
                    <p class="text-muted">Reservation #{{ $reservation->id }} is confirmed and your rental contract has been generated. Redirecting&hellip;</p>
                </div>
            </template>

            <template x-if="error">
                <div>
                    <h3 class="mb-2 text-danger">Something went wrong</h3>
                    <p class="text-muted">We couldn't confirm this payment automatically.</p>
                    <a href="{{ route('reservations.index') }}" class="btn btn-gold">Back to my reservations</a>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection
