@extends('layouts.app')
@section('title', 'Modify Reservation')
@section('content')
<h2 class="mb-4">Modify Reservation #{{ $reservation->id }}: {{ $reservation->vehicle->model }}</h2>

<div class="alert alert-warning">
    Changing this reservation adds a change fee of <strong>{{ number_format($changeFee) }} RWF</strong>
    (10% of the original {{ number_format($reservation->original_total_price) }} RWF invoice), on top of any price
    difference from the new dates.
</div>

<div class="row">
<form method="POST" action="{{ route('reservations.update', $reservation) }}" class="col-12 col-md-6"
      id="reservation-edit-form"
      data-price-per-day="{{ $reservation->price_per_day_snapshot ?? $reservation->vehicle->price_per_day }}"
      data-price-per-week="{{ $reservation->price_per_week_snapshot ?? $reservation->vehicle->price_per_week }}"
      data-price-per-month="{{ $reservation->price_per_month_snapshot ?? $reservation->vehicle->price_per_month }}"
      data-driver-fee-per-day="{{ $reservation->driver_fee_per_day_snapshot ?? $reservation->vehicle->driver_fee_per_day }}"
      data-deposit-amount="{{ $reservation->vehicle_deposit_snapshot ?? $reservation->vehicle->deposit_amount }}"
      data-delivery-fee="{{ $reservation->delivery_fee }}"
      data-change-fee="{{ $changeFee }}">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Start date</label>
        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $reservation->start_date->format('Y-m-d') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">End date</label>
        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $reservation->end_date->format('Y-m-d') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Pickup / return time</label>
        <input type="time" name="pickup_time" id="pickup_time" class="form-control" min="08:00" max="23:00" value="{{ $reservation->pickup_time ? \Illuminate\Support\Carbon::parse($reservation->pickup_time)->format('H:i') : '08:00' }}" style="max-width:160px;" required>
        <p class="form-text">Vehicles are picked up and returned between 08:00 and 23:00 only.</p>
    </div>
    @if($reservation->vehicle->driver_available)
    <div class="form-check mb-3">
        <input type="checkbox" name="with_driver" value="1" class="form-check-input" id="with_driver" {{ $reservation->with_driver ? 'checked' : '' }}>
        <label class="form-check-label" for="with_driver">Include driver ({{ number_format($reservation->vehicle->driver_fee_per_day) }} RWF/day)</label>
    </div>
    @endif

    <div class="card p-3 mb-3" id="charges-breakdown">
        <div class="d-flex justify-content-between"><span class="text-muted">Rental (<span id="charge-days">1</span> day(s))</span><span id="charge-subtotal">0 RWF</span></div>
        <div class="d-flex justify-content-between d-none" id="charge-driver-row"><span class="text-muted">Driver fee</span><span id="charge-driver-fee">0 RWF</span></div>
        @if($reservation->delivery_fee > 0)
        <div class="d-flex justify-content-between"><span class="text-muted">Delivery fee</span><span>{{ number_format($reservation->delivery_fee) }} RWF</span></div>
        @endif
        <div class="d-flex justify-content-between"><span class="text-muted">Change fee</span><span>{{ number_format($changeFee) }} RWF</span></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between fw-bold"><span>New total</span><span id="charge-total">0 RWF</span></div>
        <div class="d-flex justify-content-between text-muted small"><span>Already paid</span><span>{{ number_format($reservation->amountPaid()) }} RWF</span></div>
    </div>

    <button class="btn btn-gold">Confirm changes</button>
    <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary">Cancel</a>
</form>
</div>

<script>
(function () {
    var form = document.getElementById('reservation-edit-form');
    var pricePerDay = parseFloat(form.dataset.pricePerDay);
    var pricePerWeek = form.dataset.pricePerWeek ? parseFloat(form.dataset.pricePerWeek) : null;
    var pricePerMonth = form.dataset.pricePerMonth ? parseFloat(form.dataset.pricePerMonth) : null;
    var driverFeePerDay = parseFloat(form.dataset.driverFeePerDay);
    var depositAmount = parseFloat(form.dataset.depositAmount) || 0;
    var deliveryFee = parseFloat(form.dataset.deliveryFee) || 0;
    var changeFee = parseFloat(form.dataset.changeFee) || 0;
    var startInput = document.getElementById('start_date');
    var endInput = document.getElementById('end_date');
    var driverInput = document.getElementById('with_driver');

    // Mirrors App\Support\RentalPricing::tieredSubtotal() server-side.
    function tieredSubtotal(days) {
        var remaining = days;
        var months = 0, weeks = 0;

        if (pricePerMonth) {
            months = Math.floor(remaining / 30);
            remaining -= months * 30;
        }
        if (pricePerWeek) {
            weeks = Math.floor(remaining / 7);
            remaining -= weeks * 7;
        }

        return (months * (pricePerMonth || 0)) + (weeks * (pricePerWeek || 0)) + (remaining * pricePerDay);
    }

    function recalculate() {
        var days = 1;
        if (startInput.value && endInput.value) {
            var start = new Date(startInput.value);
            var end = new Date(endInput.value);
            var diff = Math.round((end - start) / 86400000);
            if (diff > 0) days = diff;
        }

        var subtotal = tieredSubtotal(days);
        var withDriver = driverInput ? driverInput.checked : false;
        var driverFee = withDriver ? driverFeePerDay * days : 0;
        var total = subtotal + driverFee + depositAmount + deliveryFee + changeFee;

        document.getElementById('charge-days').textContent = days;
        document.getElementById('charge-subtotal').textContent = subtotal.toLocaleString('en-US') + ' RWF';
        document.getElementById('charge-driver-fee').textContent = driverFee.toLocaleString('en-US') + ' RWF';
        document.getElementById('charge-driver-row').classList.toggle('d-none', !withDriver);
        document.getElementById('charge-total').textContent = total.toLocaleString('en-US') + ' RWF';
    }

    startInput.addEventListener('change', recalculate);
    endInput.addEventListener('change', recalculate);
    if (driverInput) driverInput.addEventListener('change', recalculate);
    recalculate();
})();
</script>
@endsection
