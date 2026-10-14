@extends('layouts.app')
@section('title', 'Privacy Policy')
@section('content')
<h2 class="mb-1">Privacy Policy</h2>
<p class="text-muted mb-4">Last updated: {{ date('F Y') }}</p>

<div class="fx-prose">
    <p><em>This is placeholder content and will be replaced with FlexiRent's full privacy policy.</em></p>

    <h4>What we collect</h4>
    <p>Account details (name, email, phone), identity and driving licence numbers provided at booking time,
    and payment references from Mobile Money transactions. Partner accounts additionally provide company
    and TIN details where applicable.</p>

    <h4>How we use it</h4>
    <p>To create and manage reservations, generate rental contracts, process payments, and send booking
    and account notifications by email.</p>

    <h4>Sharing</h4>
    <p>Reservation details (client identity, dates) are shared with the vehicle's partner as needed to
    fulfil a booking. We do not sell personal data to third parties.</p>

    <h4>Your data</h4>
    <p>Contact FlexiRent to request access to, correction of, or deletion of your personal data.</p>
</div>
@endsection
