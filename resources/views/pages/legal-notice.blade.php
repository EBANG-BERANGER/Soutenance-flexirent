@extends('layouts.app')
@section('title', 'Legal Notice')
@section('content')
<h2 class="mb-1">Legal Notice</h2>
<p class="text-muted mb-4">Last updated: {{ date('F Y') }}</p>

<div class="fx-prose">
    <p><em>This is placeholder content and will be replaced with FlexiRent's full legal notice.</em></p>

    <h4>Publisher</h4>
    <p>FlexiRent &mdash; car rental intermediary platform, operating in Kigali, Rwanda.</p>

    <h4>Contact</h4>
    <p>Email: <a href="mailto:contact@flexirent.rw">contact@flexirent.rw</a><br>
    Phone: <a href="tel:+250780097079">+250 780 097 079</a></p>

    <h4>Role</h4>
    <p>FlexiRent acts as an intermediary between independent vehicle owners and renters. It is not the
    owner of the vehicles listed on the platform unless explicitly stated on a listing.</p>
</div>
@endsection
