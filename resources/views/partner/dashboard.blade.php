@extends('layouts.app')
@section('title', 'Partner Dashboard')
@section('content')
<h2 class="mb-4">Partner Dashboard: {{ $partner->company_name ?: auth()->user()->name }}</h2>

<div class="fx-stat-tile fx-reveal mb-4 p-4 text-center" style="border-color:var(--gold);">
    <small>Total received since joining</small>
    <h2 class="mb-0 text-gold">{{ number_format($totalReceived) }} RWF</h2>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6"><div class="fx-stat-tile fx-reveal"><small>Vehicles listed</small><h3>{{ $vehicles->count() }}</h3></div></div>
    <div class="col-12 col-md-6"><div class="fx-stat-tile fx-reveal"><small>Active reservations</small><h3>{{ $activeReservations->count() }}</h3></div></div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0">My Vehicles</h4>
    <a href="{{ route('partner.vehicles.create') }}" class="btn btn-gold btn-sm">+ Add vehicle</a>
</div>
<div class="fx-table-card mb-5">
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr><th>Vehicle</th><th>Price/day</th><th>Min. rental</th><th>Status</th><th>Available</th><th>Reservations</th></tr></thead>
        <tbody>
            @foreach($vehicles as $v)
                <tr>
                    <td>{{ $v->model }}</td>
                    <td>{{ number_format($v->price_per_day) }} RWF</td>
                    <td>
                        <form method="POST" action="{{ route('partner.vehicles.min-rental-days.update', $v) }}" class="d-flex align-items-center gap-1">
                            @csrf
                            <input type="number" name="min_rental_days" value="{{ $v->min_rental_days }}" min="1" max="365" class="form-control form-control-sm" style="width:70px;">
                            <button class="btn btn-sm btn-outline-secondary">Save</button>
                        </form>
                    </td>
                    <td><span class="badge bg-secondary">{{ ucfirst($v->status) }}</span></td>
                    <td>
                        @if($v->status === 'booked')
                            <span class="text-muted small">Booked (active reservation)</span>
                        @else
                            <form method="POST" action="{{ route('partner.vehicles.toggle-availability', $v) }}">
                                @csrf
                                <label class="fx-switch">
                                    <input type="checkbox" onchange="this.form.submit()" {{ $v->status === 'available' ? 'checked' : '' }}>
                                    <span class="fx-switch-track"><span class="fx-switch-thumb"></span></span>
                                </label>
                            </form>
                        @endif
                    </td>
                    <td>{{ $v->reservations_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
</div>

<h4>Active Reservations</h4>
<div class="fx-table-card">
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr><th>Client</th><th>Vehicle</th><th>Dates</th><th>Status</th><th>Payment</th><th>Owed to you</th></tr></thead>
        <tbody>
            @foreach($activeReservations as $r)
                <tr>
                    <td>{{ $r->client->name }}</td>
                    <td>{{ $r->vehicle->model }}</td>
                    <td>{{ $r->start_date->format('d/m/Y') }} to {{ $r->end_date->format('d/m/Y') }}</td>
                    <td><span class="badge bg-secondary">{{ ucfirst($r->status) }}</span></td>
                    <td>
                        @if($r->payment_status === 'paid')
                            <span class="badge bg-success">Paid</span>
                        @elseif($r->payment_status === 'deposit_paid')
                            <span class="badge bg-warning text-dark">Deposit</span>
                        @else
                            <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </td>
                    <td>{{ $r->owner_payout !== null ? number_format($r->owner_payout) . ' RWF' : 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0">Traffic Violations</h4>
</div>
<div class="fx-table-card mb-3">
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr><th>Vehicle</th><th>Reference</th><th>Date</th><th>Due</th><th>Client</th><th>Status</th></tr></thead>
        <tbody>
            @forelse($violations as $v)
                <tr>
                    <td>{{ $v->vehicle->model }}</td>
                    <td>{{ $v->reference_number }}</td>
                    <td>{{ $v->violation_date->format('d/m/Y') }}</td>
                    <td>{{ $v->payment_due_date->format('d/m/Y') }}</td>
                    <td>{{ $v->reservation?->client?->name ?? 'Not identified' }}</td>
                    <td>
                        @if($v->status === 'paid')
                            <span class="badge bg-success">Paid</span>
                        @else
                            <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">No violations reported.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
</div>

<div class="fx-table-card mb-5 p-3">
    <h5 class="mb-3">Report a violation</h5>
    <form method="POST" action="" id="fx-violation-form" class="row g-3" onsubmit="return !!this.action">
        @csrf
        <div class="col-12 col-md-4">
            <label class="form-label">Vehicle</label>
            <select class="form-select" required onchange="document.getElementById('fx-violation-form').action = this.value">
                <option value="">Select a vehicle</option>
                @foreach($vehicles as $v)
                    <option value="{{ route('partner.vehicles.violations.store', $v) }}">{{ $v->model }} ({{ $v->plate_number }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Reference number(s)</label>
            <input type="text" name="reference_number" class="form-control" placeholder="e.g. RRA-123456" required>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label">Violation date</label>
            <input type="date" name="violation_date" class="form-control" max="{{ now()->toDateString() }}" required>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label">Payment due</label>
            <input type="date" name="payment_due_date" class="form-control" required>
        </div>
        <div class="col-12">
            <label class="form-label">Notes (optional)</label>
            <textarea name="notes" class="form-control" rows="2" maxlength="2000"></textarea>
        </div>
        <div class="col-12">
            <button class="btn btn-gold btn-sm">Report violation</button>
            <p class="form-text mb-0">The client who had the vehicle on that date is found automatically and notified, even if their rental has already ended.</p>
        </div>
    </form>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0">Rental History</h4>
</div>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4"><div class="fx-stat-tile fx-reveal"><small>Total charged (filtered)</small><h4>{{ number_format($historyTotals->total_price) }} RWF</h4></div></div>
    <div class="col-6 col-md-4"><div class="fx-stat-tile fx-reveal"><small>Owed to you (filtered)</small><h4>{{ number_format($historyTotals->owner_payout) }} RWF</h4></div></div>
    <div class="col-12 col-md-4"><div class="fx-stat-tile fx-reveal"><small>Rentals (filtered)</small><h4>{{ $historyTotals->count }}</h4></div></div>
</div>

@include('admin.partials.history-filters')

<div class="fx-table-card mb-5">
<div class="table-responsive">
    <table class="table table-hover">
        <thead><tr><th>Vehicle</th><th>Client</th><th>Dates</th><th>Total</th><th>Owed to you</th><th>Payment</th></tr></thead>
        <tbody>
            @forelse($history as $r)
                <tr>
                    <td>{{ $r->vehicle->model }}</td>
                    <td>{{ $r->client->name }}</td>
                    <td>{{ $r->start_date->format('d/m/Y') }} to {{ $r->end_date->format('d/m/Y') }}</td>
                    <td>{{ number_format($r->total_price) }} RWF</td>
                    <td>{{ $r->owner_payout !== null ? number_format($r->owner_payout) . ' RWF' : 'N/A' }}</td>
                    <td>
                        @if($r->payment_status === 'paid')
                            <span class="badge bg-success">Paid</span>
                        @elseif($r->payment_status === 'deposit_paid')
                            <span class="badge bg-warning text-dark">Deposit</span>
                        @else
                            <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">No rentals match these filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
</div>
{{ $history->links() }}

<h4>Notifications from FlexiRent</h4>
<div class="mb-5">
    @forelse($notifications as $n)
        <div class="fx-stat-tile fx-reveal mb-3">
            <small class="text-muted">{{ $n->created_at->format('d/m/Y H:i') }}</small>
            <p class="fw-bold mb-1">{{ $n->title }}</p>
            <p class="mb-2">{{ $n->content }}</p>
        </div>
    @empty
        <p class="text-muted">No notifications yet.</p>
    @endforelse

    <form method="POST" action="{{ route('partner.notifications.reply') }}" class="mt-3">
        @csrf
        <label class="form-label">Reply to FlexiRent</label>
        <textarea name="message" class="form-control mb-2" rows="2" maxlength="2000" placeholder="Write a message to FlexiRent administration..." required></textarea>
        <button class="btn btn-gold btn-sm">Send reply</button>
    </form>
</div>
@endsection
