@extends('layouts.app')
@section('title', 'My Reservations')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-4" x-data="{ open: false }">
    <h2 class="mb-0">My Reservations</h2>
    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" style="width:26px;height:26px;padding:0;line-height:1;" @click="open = !open" aria-label="Reliability history">!</button>
    <span class="text-muted small">Reliability status: <strong>{{ auth()->user()->reliability_score }}</strong></span>

    <div class="fx-table-card p-3 w-100 mt-2" x-show="open" x-cloak>
        <h6 class="mb-2">Reliability history</h6>
        <p class="text-muted small">Points are earned for reservations completed normally, and lost for late cancellations (less than 48h before the start date) or reservations left unpaid for 24 hours. Below -30, only full payment is accepted on new bookings.</p>
        @forelse($reliabilityEvents as $event)
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div>
                    <span class="d-block">{{ $event->reason }}</span>
                    <span class="text-muted small">{{ $event->created_at->format('d/m/Y H:i') }}{{ $event->reservation_id ? ' · Reservation #'.$event->reservation_id : '' }}</span>
                </div>
                <span class="fw-bold {{ $event->points > 0 ? 'text-success' : ($event->points < 0 ? 'text-danger' : 'text-muted') }}">{{ $event->points > 0 ? '+' : '' }}{{ $event->points }}</span>
            </div>
        @empty
            <p class="text-muted small mb-0">No reliability events yet.</p>
        @endforelse
    </div>
</div>
<div class="fx-table-card">
<div class="table-responsive">
<table class="table table-hover">
    <thead>
        <tr><th>Vehicle</th><th>Dates</th><th>Total</th><th>Status</th><th>Payment</th><th>Actions</th></tr>
    </thead>
    <tbody>
        @forelse($reservations as $r)
            <tr>
                <td>{{ $r->vehicle->model }}</td>
                <td>{{ $r->start_date->format('d/m/Y') }} to {{ $r->end_date->format('d/m/Y') }}</td>
                <td>{{ number_format($r->total_price) }} RWF</td>
                <td><span class="badge bg-secondary">{{ ucfirst($r->status) }}</span></td>
                <td>
                    @if($r->payment_status === 'paid')
                        <span class="badge bg-success">Paid in full</span>
                    @elseif($r->payment_status === 'deposit_paid')
                        <span class="badge bg-warning text-dark">{{ number_format($r->amountPaid()) }} / {{ number_format($r->total_price) }} RWF paid</span>
                        <div class="text-muted small mt-1">{{ number_format($r->balanceDue()) }} RWF remaining</div>
                    @else
                        <span class="badge bg-danger">Unpaid</span>
                        <div class="text-muted small mt-1">{{ number_format($r->total_price) }} RWF due</div>
                    @endif
                </td>
                <td>
                    @if($r->payment_status !== 'paid' && $r->status !== 'cancelled')
                        <a href="{{ route('reservations.payment', $r) }}" class="btn btn-sm btn-gold">Pay</a>
                    @endif
                    @if($r->status === 'confirmed')
                        <a href="{{ route('reservations.edit', $r) }}" class="btn btn-sm btn-outline-secondary">Modify</a>
                    @endif
                    @if(in_array($r->status, ['pending', 'confirmed']))
                        <form method="POST" action="{{ route('reservations.cancel', $r) }}" class="d-inline" onsubmit="return confirm('Cancel this reservation? Depending on your payment status and timing, a cancellation charge may apply.')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                        </form>
                    @endif
                    @if($r->contract)
                        <a href="{{ route('contracts.download', $r) }}" class="btn btn-sm btn-navy">Contract PDF</a>
                    @endif
                    @if($r->isEligibleForReview() && !$r->review)
                        <a href="{{ route('reviews.create', $r) }}" class="btn btn-sm btn-gold">Leave a review</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-muted">No reservations yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
</div>
{{ $reservations->links() }}
@endsection
