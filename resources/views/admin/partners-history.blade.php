@extends('layouts.admin')
@section('title', 'Partner Financial History')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
    <h2 class="mb-0">{{ $partner->adminLabel() }}</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.partners.history.export', $partner) }}?{{ http_build_query(request()->query()) }}" class="btn btn-admin btn-admin-ghost">Export CSV</a>
        <a href="{{ route('admin.partners.edit', $partner) }}" class="btn btn-admin btn-admin-outline">Edit partner</a>
    </div>
</div>
<p class="text-muted mb-4">{{ $partner->user->name }} ({{ $partner->user->email }})</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="fx-admin-stat is-financial">
        <div><small>Total charged</small><h4>{{ number_format($totals->total_price) }} RWF</h4></div>
    </div></div>
    <div class="col-6 col-md-3"><div class="fx-admin-stat is-financial">
        <div><small>Paid to partner</small><h4>{{ number_format($totals->owner_payout) }} RWF</h4></div>
    </div></div>
    <div class="col-6 col-md-3"><div class="fx-admin-stat is-financial">
        <div><small>FlexiRent commission</small><h4>{{ number_format($totals->platform_commission) }} RWF</h4></div>
    </div></div>
    <div class="col-6 col-md-3"><div class="fx-admin-stat">
        <div><small>Rentals</small><h4>{{ $totals->count }}</h4></div>
    </div></div>
</div>

@include('admin.partials.history-filters')

<div class="table-responsive">
<table class="table table-hover">
    <thead><tr><th>Vehicle</th><th>Client</th><th>Dates</th><th>Total</th><th>Owner payout</th><th>Payment</th></tr></thead>
    <tbody>
        @forelse($reservations as $r)
            <tr>
                <td><a href="{{ route('admin.vehicles.history', $r->vehicle) }}">{{ $r->vehicle->model }}</a></td>
                <td>{{ $r->client->name }} ({{ $r->client->email }})</td>
                <td>{{ $r->start_date->format('d/m/Y') }} to {{ $r->end_date->format('d/m/Y') }}</td>
                <td>{{ number_format($r->total_price) }} RWF</td>
                <td>{{ number_format($r->owner_payout) }} RWF</td>
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
{{ $reservations->links() }}
@endsection
