@extends('layouts.app')
@section('title', 'Terms & Cancellation Policy')
@section('content')
<h2 class="mb-1">Terms &amp; Cancellation Policy</h2>
<p class="text-muted mb-4">Last updated: {{ date('F Y') }}</p>

<div class="fx-prose">
    <h4>1. Using FlexiRent</h4>
    <p>FlexiRent is a booking intermediary connecting vehicle owners ("partners") with renters ("clients")
    in Kigali, Rwanda. By creating an account or booking a vehicle, you agree to these terms.</p>

    <h4>2. Bookings &amp; payment confirmation</h4>
    <p>A reservation is created as "pending" as soon as you submit the booking form, but it only becomes
    "confirmed" once a payment (a deposit or the full amount) is received. No live Mobile Money merchant
    account is connected yet: every payment request is recorded as pending and is confirmed manually by a
    FlexiRent administrator once the transfer is verified, which is also the moment your reservation is
    confirmed and your rental contract is issued. A rental contract PDF is generated for every reservation
    and always reflects the current payment status.</p>

    <h4>3. Cancellation policy</h4>
    <p>You may cancel a "pending" or "confirmed" reservation yourself from "My Reservations", at any time
    before it starts. A cancellation charge may apply depending on how much you had already paid and how
    much notice you gave, calculated on the number of hours between the moment you cancel and your
    reservation's start date:</p>
    <div class="table-responsive">
        <table class="table table-sm table-bordered fx-terms-table">
            <thead>
                <tr><th>Payment status</th><th>Notice given</th><th>Amount retained</th><th>Amount refunded</th></tr>
            </thead>
            <tbody>
                <tr><td>Not yet paid</td><td>Any time</td><td>None</td><td>N/A: nothing was paid</td></tr>
                <tr><td>Deposit paid</td><td>More than 48 hours before start</td><td>10% of the deposit</td><td>90% of the deposit</td></tr>
                <tr><td>Deposit paid</td><td>48 hours or less before start</td><td>100% of the deposit</td><td>None</td></tr>
                <tr><td>Paid in full</td><td>More than 48 hours before start</td><td>10% of the total price</td><td>90% of the total price</td></tr>
                <tr><td>Paid in full</td><td>48 hours or less before start</td><td>20% of the total price</td><td>80% of the total price</td></tr>
            </tbody>
        </table>
    </div>
    <p>Any amount due back to you is recorded as a pending refund and processed manually by FlexiRent
    administration. Once cancelled by you, a reservation is final; reactivation is only ever done by
    FlexiRent administration, at its discretion, to resolve a genuine dispute.</p>

    <h4>4. Partner validation</h4>
    <p>Every partner account (agency or individual owner) is reviewed manually before it can list vehicles.
    Registration requires either a company name and TIN (agency) or a national ID number (individual owner),
    together with a supporting document (business registration, or an ID/passport scan). The submitted
    information and the document are both required and serve complementary purposes: the data keeps the
    record consistent, while the document lets a FlexiRent administrator judge its authenticity. A partner
    account only becomes active once an administrator has validated both.</p>

    <h4>5. Vehicle availability</h4>
    <p>A vehicle with a pending or confirmed reservation is considered committed to that booking: it cannot
    be pulled into maintenance or deleted from the catalogue by its partner or by FlexiRent administration
    until that reservation is completed or cancelled. Its price and photos can still be updated at any time.</p>

    <h4>6. Information we collect from you</h4>
    <p>When you book a vehicle, FlexiRent collects your ID number, nationality, and driving license number.
    This information is used solely to identify you on the rental contract and to verify your eligibility to
    drive, as required by the rental agreement between you, FlexiRent, and the vehicle's owner. It is not
    used for any other purpose.</p>

    <h4>7. Partner listings</h4>
    <p>Partners are responsible for the accuracy of their vehicle listings and for the roadworthiness of
    any vehicle made available for rental through the platform.</p>
</div>
@endsection
