@extends('layouts.app')
@section('title', 'Contact Us')
@section('content')
<h2 class="mb-2">Contact Us</h2>
<p class="text-muted mb-4">Questions about a booking, becoming a partner, or anything else: reach us directly.</p>

<div class="row g-4">
    <div class="col-12 col-md-6">
        <div class="card p-4 auth-card">
            <ul class="list-unstyled mb-0" style="display:flex; flex-direction:column; gap:16px;">
                <li>
                    <strong class="d-block mb-1">Phone</strong>
                    <a href="tel:+250780097079">+250 780 097 079</a>
                </li>
                <li>
                    <strong class="d-block mb-1">WhatsApp</strong>
                    <a href="https://wa.me/250780097079" target="_blank" rel="noopener">+250 780 097 079</a>
                </li>
                <li>
                    <strong class="d-block mb-1">Email</strong>
                    <a href="mailto:contact@flexirent.rw">contact@flexirent.rw</a>
                </li>
                <li>
                    <strong class="d-block mb-1">Location</strong>
                    Kigali, Rwanda
                </li>
            </ul>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="fx-prose">
            <p>Prefer to book directly? Browse our <a href="{{ route('vehicles.fleet') }}">verified fleet</a>
            and reserve online in a few minutes: payment is handled via MTN or Airtel Mobile Money, and
            a rental contract is generated automatically.</p>
            <p>Interested in listing your own vehicle? <a href="{{ route('register') }}">Register as a partner</a>
            to get started.</p>
        </div>
    </div>
</div>
@endsection
