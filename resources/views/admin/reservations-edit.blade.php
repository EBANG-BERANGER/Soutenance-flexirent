@extends('layouts.admin')
@section('title', 'Edit Reservation')
@section('content')
<h2 class="mb-4">Edit Reservation #{{ $reservation->id }}</h2>

<div class="row">
<form method="POST" action="{{ route('admin.reservations.update', $reservation) }}" class="col-12 col-md-6">
    @csrf
    @method('PUT')
    <p class="text-muted">
        {{ $reservation->client->name }}, {{ $reservation->vehicle->model }}
        @if($reservation->vehicle->partner)
            &middot; Owner: <a href="{{ route('admin.partners.edit', $reservation->vehicle->partner) }}">{{ $reservation->vehicle->partner->adminLabel() }}</a>
        @endif
    </p>
    <div class="mb-3">
        <label class="form-label">Start date</label>
        <input type="date" name="start_date" class="form-control" value="{{ $reservation->start_date->format('Y-m-d') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">End date</label>
        <input type="date" name="end_date" class="form-control" value="{{ $reservation->end_date->format('Y-m-d') }}" required>
    </div>
    <div class="form-check mb-3">
        <input type="checkbox" name="with_driver" value="1" class="form-check-input" id="wd" @checked($reservation->with_driver)>
        <label class="form-check-label" for="wd">Include driver</label>
    </div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="pending" @selected($reservation->status == 'pending')>Pending</option>
            <option value="confirmed" @selected($reservation->status == 'confirmed')>Confirmed</option>
            <option value="cancelled" @selected($reservation->status == 'cancelled')>Cancelled</option>
            <option value="completed" @selected($reservation->status == 'completed')>Completed</option>
            <option value="suspended" @selected($reservation->status == 'suspended')>Suspended</option>
        </select>
    </div>
    <p class="text-muted small">The total price is recalculated automatically from the vehicle's rate when you save.</p>
    <button class="btn btn-gold">Save changes</button>
    <a href="{{ route('admin.reservations') }}" class="btn btn-outline-secondary">Cancel</a>
</form>
</div>
@endsection
