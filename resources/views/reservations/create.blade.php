@extends('layouts.app')
@section('title', 'Reserve Vehicle')
@section('content')
<h2 class="mb-4">Reserve: {{ $vehicle->model }}</h2>
<div class="row">
<form method="POST" action="{{ route('reservations.store', $vehicle) }}" class="col-12 col-md-6"
      id="reservation-form"
      data-price-per-day="{{ $vehicle->price_per_day }}"
      data-price-per-week="{{ $vehicle->price_per_week }}"
      data-price-per-month="{{ $vehicle->price_per_month }}"
      data-driver-fee-per-day="{{ $vehicle->driver_fee_per_day }}"
      data-deposit-amount="{{ $vehicle->deposit_amount }}"
      data-min-rental-days="{{ $vehicle->min_rental_days }}">
    @csrf
    <div class="mb-3">
        <label class="form-label">ID number</label>
        <input type="text" name="id_number" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Nationality</label>
        <input type="text" name="nationality" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Driving license number</label>
        <input type="text" name="driving_license_number" class="form-control" pattern="[A-Za-z0-9]{5,20}" title="5 to 20 letters or digits" required>
    </div>
    <p class="text-muted small">These identify you on the rental contract, as required by the agreement.</p>
    <div class="mb-3">
        <label class="form-label">Start date</label>
        <input type="date" name="start_date" id="start_date" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">End date</label>
        <input type="date" name="end_date" id="end_date" class="form-control" required>
        @if($vehicle->min_rental_days > 1)
            <p class="form-text">This vehicle requires a minimum rental of {{ $vehicle->min_rental_days }} days.</p>
        @endif
        <p class="text-danger small d-none" id="min-days-warning"></p>
    </div>
    <div class="mb-3">
        <label class="form-label">Pickup / return time</label>
        <input type="time" name="pickup_time" id="pickup_time" class="form-control" min="08:00" max="23:00" value="08:00" style="max-width:160px;" required>
        <p class="form-text">Vehicles are picked up and returned between 08:00 and 23:00 only.</p>
    </div>
    @if($vehicle->driver_available)
    <div class="form-check mb-3">
        <input type="checkbox" name="with_driver" value="1" class="form-check-input" id="with_driver">
        <label class="form-check-label" for="with_driver">Include driver ({{ number_format($vehicle->driver_fee_per_day) }} RWF/day)</label>
    </div>
    @endif

    <div class="mb-3">
        <label class="form-label fw-bold d-block">How will you get the vehicle?</label>
        <div class="fx-pay-options">
            <label class="fx-pay-option">
                <input type="radio" name="delivery_method" value="pickup" id="delivery_pickup" checked>
                <div>
                    <strong>Pick up myself</strong>
                    <p class="text-muted small mb-0">Collect the vehicle at the partner's location, at no extra cost.</p>
                </div>
            </label>
            <label class="fx-pay-option">
                <input type="radio" name="delivery_method" value="delivery" id="delivery_delivery">
                <div>
                    <strong>Deliver it to me</strong>
                    <p class="text-muted small mb-0">FlexiRent brings the vehicle to you for {{ number_format(\App\Models\Reservation::DELIVERY_FEE) }} RWF.</p>
                </div>
            </label>
        </div>
    </div>
    <div class="mb-3 d-none" id="delivery-location-field">
        <label class="form-label">Delivery address or Google Maps link</label>
        <input type="text" name="delivery_location" id="delivery_location" class="form-control" placeholder="e.g. KG 7 Ave, Kigali or a Google Maps link">
    </div>

    <div class="card p-3 mb-3" id="charges-breakdown">
        <div class="d-flex justify-content-between"><span class="text-muted" id="charge-days-label">Rental (<span id="charge-days">1</span> day(s) &times; {{ number_format($vehicle->price_per_day) }} RWF)</span><span id="charge-subtotal">{{ number_format($vehicle->price_per_day) }} RWF</span></div>
        <div class="d-flex justify-content-between d-none" id="charge-driver-row"><span class="text-muted">Driver fee</span><span id="charge-driver-fee">0 RWF</span></div>
        <div class="d-flex justify-content-between d-none" id="charge-delivery-row"><span class="text-muted">Delivery fee</span><span id="charge-delivery-fee">0 RWF</span></div>
        @if($vehicle->deposit_amount > 0)
        <div class="d-flex justify-content-between"><span class="text-muted">Security deposit (caution)</span><span id="charge-deposit">{{ number_format($vehicle->deposit_amount) }} RWF</span></div>
        @endif
        <hr class="my-2">
        <div class="d-flex justify-content-between fw-bold"><span>Total</span><span id="charge-total">{{ number_format($vehicle->price_per_day + $vehicle->deposit_amount) }} RWF</span></div>
        @if($vehicle->deposit_amount > 0)
        <p class="text-muted small mb-0 mt-1">The deposit is refunded after return, subject to the rental agreement.</p>
        @endif
    </div>

    <button class="btn btn-gold">Continue to payment</button>
</form>
</div>

<script>
(function () {
    var form = document.getElementById('reservation-form');
    var pricePerDay = parseFloat(form.dataset.pricePerDay);
    var pricePerWeek = form.dataset.pricePerWeek ? parseFloat(form.dataset.pricePerWeek) : null;
    var pricePerMonth = form.dataset.pricePerMonth ? parseFloat(form.dataset.pricePerMonth) : null;
    var driverFeePerDay = parseFloat(form.dataset.driverFeePerDay);
    var depositAmount = parseFloat(form.dataset.depositAmount) || 0;
    var minRentalDays = parseInt(form.dataset.minRentalDays, 10) || 1;
    var deliveryFeeAmount = {{ (int) \App\Models\Reservation::DELIVERY_FEE }};
    var minDaysWarning = document.getElementById('min-days-warning');
    var submitButton = form.querySelector('button[type="submit"], button:not([type])');
    var startInput = document.getElementById('start_date');
    var endInput = document.getElementById('end_date');
    var driverInput = document.getElementById('with_driver');
    var deliveryInputs = document.querySelectorAll('input[name="delivery_method"]');
    var deliveryLocationField = document.getElementById('delivery-location-field');
    var deliveryLocationInput = document.getElementById('delivery_location');

    function isDelivery() {
        var checked = document.querySelector('input[name="delivery_method"]:checked');
        return checked && checked.value === 'delivery';
    }

    function toggleDeliveryField() {
        var delivery = isDelivery();
        deliveryLocationField.classList.toggle('d-none', !delivery);
        deliveryLocationInput.required = delivery;
        recalculate();
    }

    // Mirrors App\Support\RentalPricing::tieredSubtotal() server-side: the
    // cheapest combination of month/week/day blocks the partner has priced,
    // falling back to the day rate for whichever tiers aren't set.
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

        var subtotal = (months * (pricePerMonth || 0)) + (weeks * (pricePerWeek || 0)) + (remaining * pricePerDay);

        return { months: months, weeks: weeks, days: remaining, subtotal: subtotal };
    }

    function describeRentalLabel(breakdown) {
        var parts = [];
        if (breakdown.months) parts.push(breakdown.months + ' month(s) &times; ' + pricePerMonth.toLocaleString('en-US') + ' RWF');
        if (breakdown.weeks) parts.push(breakdown.weeks + ' week(s) &times; ' + pricePerWeek.toLocaleString('en-US') + ' RWF');
        if (breakdown.days) parts.push(breakdown.days + ' day(s) &times; ' + pricePerDay.toLocaleString('en-US') + ' RWF');
        return 'Rental (' + parts.join(' + ') + ')';
    }

    function recalculate() {
        var days = 1;
        if (startInput.value && endInput.value) {
            var start = new Date(startInput.value);
            var end = new Date(endInput.value);
            var diff = Math.round((end - start) / 86400000);
            if (diff > 0) {
                days = diff;
            }
        }

        var breakdown = tieredSubtotal(days);
        var subtotal = breakdown.subtotal;
        var withDriver = driverInput ? driverInput.checked : false;
        var driverFee = withDriver ? driverFeePerDay * days : 0;
        var deliveryFee = isDelivery() ? deliveryFeeAmount : 0;
        var total = subtotal + driverFee + deliveryFee + depositAmount;

        document.getElementById('charge-days-label').innerHTML = describeRentalLabel(breakdown);
        document.getElementById('charge-subtotal').textContent = subtotal.toLocaleString('en-US') + ' RWF';
        document.getElementById('charge-driver-fee').textContent = driverFee.toLocaleString('en-US') + ' RWF';
        document.getElementById('charge-driver-row').classList.toggle('d-none', !withDriver);
        document.getElementById('charge-delivery-fee').textContent = deliveryFee.toLocaleString('en-US') + ' RWF';
        document.getElementById('charge-delivery-row').classList.toggle('d-none', !deliveryFee);
        document.getElementById('charge-total').textContent = total.toLocaleString('en-US') + ' RWF';

        var belowMinimum = days < minRentalDays;
        minDaysWarning.classList.toggle('d-none', !belowMinimum);
        if (belowMinimum) {
            minDaysWarning.textContent = 'This vehicle requires a minimum rental of ' + minRentalDays + ' day(s); you selected ' + days + '.';
        }
        if (submitButton) {
            submitButton.disabled = belowMinimum;
        }
    }

    startInput.addEventListener('change', recalculate);
    endInput.addEventListener('change', recalculate);
    if (driverInput) {
        driverInput.addEventListener('change', recalculate);
    }
    deliveryInputs.forEach(function (input) {
        input.addEventListener('change', toggleDeliveryField);
    });
    recalculate();
})();
</script>
@endsection
