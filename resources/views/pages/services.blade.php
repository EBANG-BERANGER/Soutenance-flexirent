@extends('layouts.app')
@section('title', 'Our Services')
@section('content')
<h2 class="mb-2">Our Services</h2>
<p class="text-muted mb-4">What you can book through FlexiRent's network of verified partners.</p>

<div class="fx-why-grid">
    <div class="fx-why-item">
        <div class="fx-why-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h1.5l1-4a2 2 0 0 1 1.9-1.4h5.2a2 2 0 0 1 1.9 1.4l1 4H19"/><path d="M3 17v1.5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V17h12v1.5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1V17"/><path d="M3 17l1.2-5.8A2 2 0 0 1 6.2 9.6h11.6a2 2 0 0 1 2 1.6L21 17"/><circle cx="7.5" cy="17" r="1.3"/><circle cx="16.5" cy="17" r="1.3"/></svg>
        </div>
        <h5>Car Rental</h5>
        <p>Browse verified vehicles across Kigali and book directly, with Mobile Money payment and an
        automatic rental contract.</p>
        <a href="{{ route('vehicles.fleet') }}">Browse the fleet &rarr;</a>
    </div>
    <div class="fx-why-item">
        <div class="fx-why-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 19h19M4 19V9l4-5h8l4 5v10"/><path d="M8 19v-4h8v4"/></svg>
        </div>
        <h5>Airport Transfer</h5>
        <p>Book a vehicle with driver for a smooth pickup or drop-off at Kigali International Airport.</p>
    </div>
    <div class="fx-why-item">
        <div class="fx-why-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a8 8 0 0 1 16 0v1"/></svg>
        </div>
        <h5>Chauffeur Service</h5>
        <p>Prefer not to drive? Select a vehicle with driver directly on the booking form for point-to-point
        trips around the city.</p>
    </div>
    <div class="fx-why-item">
        <div class="fx-why-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        </div>
        <h5>Other Services</h5>
        <p>Have a specific need (long-term rental, corporate accounts, event transport)? <a href="{{ route('contact') }}">Get in touch</a> and we'll help.</p>
    </div>
</div>
@endsection
