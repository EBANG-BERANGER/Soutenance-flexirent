{{--
    Shared vehicle card, used by the homepage teaser, the catalogue grid,
    and the "You might also like" section on the vehicle detail page.
    Previously duplicated verbatim in three places; kept as one component
    so a future change to the card only has to happen once.

    Usage: <x-vehicle-card :vehicle="$vehicle" />
--}}
@php $bookedUntil = $vehicle->status === 'booked' ? $vehicle->currentBookingEndDate() : null; @endphp
<a href="{{ route('vehicles.show', $vehicle) }}" class="vehicle-card fx-reveal fx-tilt">
    @if($vehicle->status === 'booked')
        <span class="vehicle-card-badge">Booked{{ $bookedUntil ? ' until ' . $bookedUntil->format('d/m/Y') : '' }}</span>
    @endif
    <img src="{{ $vehicle->photo_path ? asset('storage/'.$vehicle->photo_path) : 'https://placehold.co/500x400?text=' . urlencode($vehicle->model) }}" alt="{{ $vehicle->model }}" loading="lazy">
    <span class="vehicle-card-caption">
        <span class="vehicle-card-name">{{ $vehicle->model }} ({{ $vehicle->year }})</span>
        <span class="vehicle-card-price">{{ number_format($vehicle->price_per_day) }} RWF/day</span>
    </span>
</a>
