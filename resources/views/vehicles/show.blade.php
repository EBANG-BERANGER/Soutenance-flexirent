@extends('layouts.app')
@section('title', $vehicle->model)
@section('content')
<div class="row">
    <div class="col-md-6">
        @if($vehicle->images->isNotEmpty())
            <div class="fx-gallery" id="fx-gallery">
                @foreach($vehicle->images as $i => $image)
                    <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $vehicle->model }}" @if($i > 0) loading="lazy" @endif>
                @endforeach
            </div>
            @if($vehicle->images->count() > 1)
                <div class="fx-gallery-thumbs" id="fx-gallery-thumbs">
                    @foreach($vehicle->images as $i => $image)
                        <img src="{{ asset('storage/'.$image->path) }}" data-index="{{ $i }}" class="{{ $i === 0 ? 'is-active' : '' }}" loading="lazy">
                    @endforeach
                </div>
            @endif
        @else
            <img src="https://placehold.co/600x400?text={{ urlencode($vehicle->model) }}" class="img-fluid rounded">
        @endif
    </div>
    <div class="col-md-6">
        <h2>{{ $vehicle->model }} ({{ $vehicle->year }})</h2>
        <p class="text-muted">{{ $vehicle->category->name }} &middot; {{ ucfirst($vehicle->car_type) }}@if($vehicle->propulsion) &middot; {{ ucfirst($vehicle->propulsion) }}@endif, listed by {{ $vehicle->partner?->publicLabel() ?? 'FlexiRent' }}</p>
        <p class="fs-4 fw-bold text-gold mb-1">{{ number_format($vehicle->price_per_day) }} RWF / day</p>
        @if($vehicle->price_per_week || $vehicle->price_per_month)
        <p class="text-muted small mb-2">
            @if($vehicle->price_per_week) {{ number_format($vehicle->price_per_week) }} RWF / week @endif
            @if($vehicle->price_per_week && $vehicle->price_per_month) &middot; @endif
            @if($vehicle->price_per_month) {{ number_format($vehicle->price_per_month) }} RWF / month @endif
        </p>
        @endif
        <p>Driver available: {{ $vehicle->driver_available ? 'Yes ('.number_format($vehicle->driver_fee_per_day).' RWF/day)' : 'No' }}</p>
        <p>Average rating: {{ $vehicle->averageRating() }} / 5</p>
        @if($vehicle->min_rental_days > 1)
            <p>Minimum rental: <strong>{{ $vehicle->min_rental_days }} days</strong></p>
        @endif

        @php $bookedUntil = $vehicle->status === 'booked' ? $vehicle->currentBookingEndDate() : null; @endphp

        <div id="fx-reserve-cta">
            @if($vehicle->status === 'booked')
                <div class="alert alert-warning">
                    This vehicle is currently booked{{ $bookedUntil ? ' until ' . $bookedUntil->format('d/m/Y') : '' }}. You can browse other vehicles or check back after that date.
                </div>
            @elseif($vehicle->status !== 'available' || $vehicle->is_suspended)
                <div class="alert alert-secondary">This vehicle is not currently available for booking.</div>
            @else
                @auth
                    @if(auth()->user()->isClient())
                        <a href="{{ route('reservations.create', $vehicle) }}" class="btn btn-gold btn-lg">Reserve this vehicle</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-gold btn-lg">Login to reserve</a>
                @endauth
            @endif
        </div>
    </div>
</div>

@if($similarVehicles->isNotEmpty())
<div class="mt-5 pt-3">
    <h2 class="mb-4">You might also like</h2>
    <div class="row g-4">
        @foreach($similarVehicles as $similar)
            <div class="col-12 col-md-6 col-lg-3">
                <x-vehicle-card :vehicle="$similar" />
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="fx-sticky-bar" id="fx-sticky-bar">
    <div class="price"><small>Per day</small>{{ number_format($vehicle->price_per_day) }} RWF</div>
    @if($vehicle->status === 'booked')
        <span class="badge bg-warning text-dark">Booked{{ $bookedUntil ? ' until ' . $bookedUntil->format('d/m/Y') : '' }}</span>
    @elseif($vehicle->status !== 'available' || $vehicle->is_suspended)
        <span class="badge bg-secondary">Unavailable</span>
    @else
        @auth
            @if(auth()->user()->isClient())
                <a href="{{ route('reservations.create', $vehicle) }}" class="btn btn-gold">Reserve</a>
            @endif
        @else
            <a href="{{ route('login') }}" class="btn btn-gold">Login to reserve</a>
        @endauth
    @endif
</div>

<script>
(function () {
    var gallery = document.getElementById('fx-gallery');
    var thumbs = document.getElementById('fx-gallery-thumbs');

    if (gallery && thumbs) {
        thumbs.querySelectorAll('img').forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                gallery.scrollTo({ left: gallery.clientWidth * parseInt(thumb.dataset.index, 10), behavior: 'smooth' });
            });
        });

        gallery.addEventListener('scroll', function () {
            var index = Math.round(gallery.scrollLeft / gallery.clientWidth);
            thumbs.querySelectorAll('img').forEach(function (thumb, i) {
                thumb.classList.toggle('is-active', i === index);
            });
        }, { passive: true });
    }

    var cta = document.getElementById('fx-reserve-cta');
    var stickyBar = document.getElementById('fx-sticky-bar');
    if (cta && stickyBar && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                stickyBar.classList.toggle('is-active', !entry.isIntersecting);
                document.body.classList.toggle('fx-has-sticky-bar', !entry.isIntersecting);
            });
        }, { threshold: 0 });
        observer.observe(cta);
    }
})();
</script>
@endsection
