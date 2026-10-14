<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 100px 40px 90px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }

        header { position: fixed; top: -80px; left: 0; right: 0; height: 70px; border-bottom: 2px solid #b8860b; padding-bottom: 10px; }
        header img { height: 45px; float: left; }
        header .company { float: right; text-align: right; font-size: 10px; color: #555; }
        header .company strong { color: #0a1f44; font-size: 13px; }

        footer { position: fixed; bottom: -70px; left: 0; right: 0; height: 60px; border-top: 1px solid #ccc; padding-top: 8px; font-size: 9px; color: #777; text-align: center; }

        h1 { font-family: 'DejaVu Serif', Georgia, serif; color: #0a1f44; font-size: 18px; text-align: center; margin-bottom: 4px; }
        .ref-line { font-size: 10px; color: #666; text-align: center; margin-bottom: 18px; }

        p.intro { text-align: justify; margin-bottom: 14px; }

        table.details { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.details th { width: 35%; text-align: left; background: #f4f0e6; padding: 6px 9px; border: 1px solid #e2ddd0; color: #0a1f44; font-weight: 600; font-size: 10.5px; }
        table.details td { padding: 6px 9px; border: 1px solid #e2ddd0; font-size: 10.5px; }

        h2.article { font-family: 'DejaVu Serif', Georgia, serif; font-size: 12px; color: #0a1f44; border-bottom: 1px solid #e2c88a; padding-bottom: 3px; margin-top: 16px; margin-bottom: 8px; }
        h3.sub { font-size: 10.5px; font-weight: 700; margin: 8px 0 4px; }

        ul.terms, ol.terms { padding-left: 16px; margin: 0 0 6px; }
        ul.terms li, ol.terms li { margin-bottom: 5px; text-align: justify; }

        .note-flag { color: #b02a2a; font-weight: 700; margin: 10px 0; }

        .condition-grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .condition-grid td { border: 1px solid #ccc; text-align: center; font-size: 9px; color: #555; padding: 4px; height: 70px; vertical-align: bottom; width: 20%; }
        .condition-note { font-size: 9.5px; color: #666; margin: 6px 0 4px; }

        .comment-box { border: 1px solid #b8860b; border-radius: 3px; padding: 8px 10px; margin-top: 10px; }
        .comment-box .label { font-weight: 700; color: #0a1f44; font-size: 10.5px; margin-bottom: 6px; }
        .comment-box .line { border-bottom: 1px dotted #999; height: 16px; }

        .signatures { width: 100%; margin-top: 30px; }
        .signatures td { width: 50%; vertical-align: top; padding-top: 20px; font-size: 10.5px; }
        .sig-line { border-top: 1px solid #333; width: 85%; margin-top: 34px; padding-top: 4px; font-size: 9.5px; color: #555; }
        .done-line { margin-top: 22px; font-size: 10.5px; }
    </style>
</head>
<body>
    <header>
        @if(file_exists(public_path('images/logo.png')))
            <img src="{{ public_path('images/logo.png') }}">
        @else
            <div style="float:left; font-size:18px; font-weight:bold; color:#0a1f44;">Flexi<span style="color:#b8860b;">Rent</span></div>
        @endif
        <div class="company">
            <strong>FlexiRent</strong><br>
            Kigali, Rwanda<br>
            TEL: 0780097079 &nbsp;|&nbsp; contact@flexirent.rw
        </div>
    </header>

    <footer>
        FlexiRent Car Rental Agreement, Reference RES-{{ $reservation->id }}, Page <span></span><br>
        TEL: 0780097079 / E-mail: ebangachille2.0@gmail.com
    </footer>

    <h1>CAR RENTAL AGREEMENT</h1>
    <div class="ref-line">Reference: <strong>RES-{{ $reservation->id }}</strong> &nbsp;|&nbsp; Generated: {{ now()->format('d/m/Y H:i') }}</div>

    @if($reservation->vehicle->partner)
    <p class="intro">
        This agreement is between <strong>{{ $reservation->vehicle->partner->publicLabel() }}</strong>, a partner of FlexiRent, hereafter "the Owner",
        working in partnership with FlexiRent, Kigali, Rwanda (acting as booking and payment intermediary only),
        and the client with the name of <strong>{{ $reservation->client->name }}</strong>, whose ID number is <strong>{{ $reservation->id_number }}</strong>
        and driving license number <strong>{{ $reservation->driving_license_number }}</strong>, whose telephone number is
        <strong>{{ $reservation->client->phone ?? 'N/A' }}</strong>, hereafter "the Renter".
    </p>
    @else
    <p class="intro">
        This agreement is between <strong>FlexiRent</strong>, Kigali, Rwanda, owner of the vehicle described below, hereafter "the Owner",
        and the client with the name of <strong>{{ $reservation->client->name }}</strong>, whose ID number is <strong>{{ $reservation->id_number }}</strong>
        and driving license number <strong>{{ $reservation->driving_license_number }}</strong>, whose telephone number is
        <strong>{{ $reservation->client->phone ?? 'N/A' }}</strong>, hereafter "the Renter".
    </p>
    @endif
    <p class="intro">
        Whereas the Renter is willing to rent a car and the Owner has agreed to rent the car registered as follows:
    </p>

    <table class="details">
        <tr><th>Type</th><td>{{ $reservation->vehicle->model }} ({{ $reservation->vehicle->year }})</td></tr>
        <tr><th>Plate number</th><td>{{ $reservation->vehicle->plate_number }}</td></tr>
        <tr><th>Chassis</th><td>{{ $reservation->vehicle->chassis_number ?? 'N/A' }}</td></tr>
    </table>

    <p class="intro" style="margin-top:12px">The Parties have agreed as follows:</p>

    <h2 class="article">ARTICLE 1: Reasons and Nature of this Agreement</h2>
    <p>This agreement is intended for the interaction between the car owner and the client.</p>

    <h2 class="article">ARTICLE 2: Contract Duration</h2>
    {{-- Uses the rate actually agreed at booking (chargedRateBreakdown), not
         the vehicle's current live price, otherwise this Charges Summary
         can silently stop matching total_price if the partner has since
         changed their listing price. --}}
    @php($charges = $reservation->chargedRateBreakdown())
    @php($rb = $charges['rental_breakdown'])
    <p>
        This car rental contract starts on <strong>{{ $reservation->start_date->format('d/m/Y') }}</strong> and ends on
        <strong>{{ $reservation->end_date->format('d/m/Y') }}</strong>, a complete period of <strong>{{ $reservation->numberOfDays() }} day(s)</strong>,
        pickup/return time <strong>{{ $reservation->pickup_time ? \Illuminate\Support\Carbon::parse($reservation->pickup_time)->format('H:i') : '08:00' }}</strong> (between 08:00 and 23:00),
        @if($rb['months'] || $rb['weeks'])
            billed as
            @if($rb['months']){{ $rb['months'] }} month(s) at <strong>{{ number_format($reservation->vehicle->price_per_month ?? 0) }} Frw</strong>@endif
            @if($rb['months'] && $rb['weeks']) + @endif
            @if($rb['weeks']){{ $rb['weeks'] }} week(s) at <strong>{{ number_format($reservation->vehicle->price_per_week ?? 0) }} Frw</strong>@endif
            @if(($rb['months'] || $rb['weeks']) && $rb['days']) + @endif
            @if($rb['days']){{ $rb['days'] }} day(s) at <strong>{{ number_format($charges['price_per_day']) }} Frw</strong>@endif.
        @else
            at the rate of <strong>{{ number_format($charges['price_per_day']) }} Frw</strong> per day.
        @endif
        Caution (security deposit): <strong>{{ number_format($charges['deposit']) }} Frw</strong>, refunded after 3 days without contravention.
    </p>
    <p>
        @if($reservation->isDelivery())
            The vehicle will be <strong>delivered</strong> to the Renter at <strong>{{ $reservation->delivery_location }}</strong> on the start date,
            for a delivery fee of <strong>{{ number_format($reservation->delivery_fee) }} Frw</strong> (included in the total price below).
        @else
            The Renter will <strong>pick up</strong> the vehicle at the Owner's location on the start date.
        @endif
    </p>

    <h2 class="article">ARTICLE 3: Responsibilities of the Contracting Parties</h2>
    <h3 class="sub">1. Responsibilities of the vehicle owner</h3>
    <ul class="terms">
        <li>The "car owner" is responsible for providing the "renter" with a car that is in good working condition, has insurance, and has the basic equipment as required by the laws of Rwanda, namely (technical control &amp; insurance, lifter, fire extinguisher, triangle, spare tire and its keys), and it has been serviced with all the necessary basics, so that it immediately starts working without any problem.</li>
        <li>The car owner agrees that when there is a need to repair something on the car in the basic services related to breaking the oil or changing the old materials (mechanical defects) on the car, it will be repaired by him, but the damage to the body will be repaired by the renter.</li>
    </ul>
    <h3 class="sub">2. Responsibilities of the Renter</h3>
    <ul class="terms">
        <li>The renter undertakes to take good care of the rented car and to fulfill all the obligations regarding the rental as stipulated in this agreement.</li>
        <li>The renter agrees that when the car makes an accident or there is damage to the housing when the car is in his/her hands, the "renter" is asked to be responsible, use the car quickly, but first inform the car owner and count the days the car stays in the garage.</li>
        <li>The renter must immediately pay the owner the rental amount corresponding to the days the car is taken before it starts working.</li>
        <li>The renter must check the oil and water of the car. If the vehicle is not controlled and it heats up, the renter must repair or replace the damaged materials.</li>
        <li>When the car is stolen while in the hands of the renter, the renter shall be responsible for reporting and the searching process with security organs and paying for it in case of loss.</li>
    </ul>

    <h2 class="article">ARTICLE 4: Breach of this Agreement</h2>
    <h3 class="sub">1. Breach of Contract by the vehicle Owner</h3>
    <p>The Vehicle Owner will be deemed to be in breach of contract in the following circumstances:</p>
    <ul class="terms">
        <li>When he did not provide insurance, as well as other basic equipment required by the Rwanda National Police as mentioned above.</li>
        <li>When he did not repair the car on time and it appeared that it was part of his responsibilities.</li>
    </ul>
    <h3 class="sub">2. Breach of Contract by the Renter</h3>
    <p>The Renter will be deemed to be in breach of contract in the following circumstances:</p>
    <ul class="terms">
        <li>In the event of any changes made to this agreement or in an act contrary to the provisions of this rental agreement without the full agreement of both parties.</li>
        <li>If the renter uses the car in activities that are against the laws of the Government of Rwanda (fraud, drugs, etc.), the renter will be responsible.</li>
        <li>In case the renter drinks alcohol and drives, which is illegal, the renter who violates the law and drives under the influence of alcohol will bear all the consequences.</li>
    </ul>

    <h2 class="article">ARTICLE 5: Use of the Vehicle in Service and Replacement</h2>
    <p>When a car breaks down, the owner must quickly repair it, if necessary, replace it with another car. It is the responsibility of the car owner to bring a replacement, and if not possible, the car owner will refund the payment for the remaining days of the contract.</p>

    <h2 class="article">ARTICLE 6: Condition of the Vehicle</h2>
    <p>The vehicle must be delivered washed and returned washed. When the vehicle is delivered with fuel, it must be returned with the same fuel level delivered.</p>

    <h2 class="article">ARTICLE 7: Payment of Taxes and Duties Required in Relation to the Rent Referred to in this Agreement</h2>
    <p>Regarding taxes and other fines (contravention, parking fee, etc.) required by the government or other regulatory authorities, they will be paid by the renter.</p>

    <h2 class="article">ARTICLE 8: The Laws Governing this Agreement</h2>
    <p>Other than what is provided in this agreement, both parties agree to follow the provisions of the laws of Rwanda regarding rent. Any disputes that arise will be resolved through negotiation and compromise, and if not, the case will be submitted to the competent courts.</p>

    <h2 class="article">ARTICLE 9: Liability and Damage Responsibility</h2>
    <p>The client accepts full responsibility for any damage caused to the vehicle, whether accidental or intentional, including but not limited to mechanical, structural, or aesthetic damage. The client shall cover all repair costs, including insurance deductibles, unless a proven hidden fault of the vehicle owner is established.</p>

    <h2 class="article">ARTICLE 10: Delays and Penalties</h2>
    <p>Any delay in returning the vehicle shall result in a penalty of 5,000 Frw per hour. In case of unpaid dues, the contract is automatically terminated, and legal proceedings will follow.</p>

    <h2 class="article">ARTICLE 11: Legal Violations</h2>
    <p>Any offense committed using the vehicle, including criminal acts, traffic violations, or driving under the influence of alcohol or drugs, shall be the sole responsibility of the renter, both financially and legally.</p>

    <h2 class="article">ARTICLE 12: Security Deposit Conditions</h2>
    <p>The deposit may be fully or partially withheld to cover:</p>
    <ul class="terms">
        <li>Damages to the vehicle</li>
        <li>Outstanding rental fees</li>
        <li>Unreturned fuel</li>
        <li>Unclean vehicle conditions</li>
    </ul>

    <h2 class="article">ARTICLE 14: Unauthorized Driver Penalty</h2>
    <p>If the renter allows the vehicle to be used by any individual not explicitly mentioned and approved in this contract, the renter shall pay a penalty of <strong>100,000 Frw</strong> (one hundred thousand Rwandan francs). This clause applies regardless of whether damage occurs or not, and the renter remains fully responsible for any incident or infraction committed by the unauthorized driver.</p>

    <p class="note-flag">NB: Any damage during rental will be at the entire charge of the client.</p>

    <h2 class="article">Charges Summary</h2>
    <table class="details">
        <tr><th>Rental charges</th><td>{{ number_format($charges['subtotal']) }} Frw</td></tr>
        @if($reservation->with_driver)
        <tr><th>Driver fee</th><td>{{ number_format($charges['driver_fee']) }} Frw</td></tr>
        @endif
        @if($reservation->isDelivery())
        <tr><th>Delivery fee</th><td>{{ number_format($reservation->delivery_fee) }} Frw</td></tr>
        @endif
        <tr><th>Security deposit (caution)</th><td>{{ number_format($charges['deposit']) }} Frw</td></tr>
        <tr><th>Total price</th><td><strong>{{ number_format($reservation->total_price) }} Frw</strong></td></tr>
    </table>

    <h2 class="article">Payment Situation</h2>
    <table class="details">
        <tr><th>Amount paid</th><td>{{ number_format($reservation->amountPaid()) }} Frw</td></tr>
        <tr><th>Amount remaining</th><td>{{ number_format($reservation->balanceDue()) }} Frw</td></tr>
        <tr>
            <th>Status</th>
            <td>
                @if($reservation->payment_status === 'paid')
                    <strong>Paid in full.</strong> No balance remains due.
                @elseif($reservation->payment_status === 'deposit_paid')
                    <strong>{{ number_format($reservation->amountPaid()) }} Frw paid in advance.</strong>
                    The remaining balance of <strong>{{ number_format($reservation->balanceDue()) }} Frw</strong> is due at pickup.
                @else
                    <strong>No payment received yet.</strong> The full amount of {{ number_format($reservation->total_price) }} Frw is due to confirm this reservation.
                @endif
                @if($reservation->latestPayment && $reservation->latestPayment->transaction_ref)
                    <br><span style="font-size:9.5px;color:#666;">Last payment reference: {{ $reservation->latestPayment->transaction_ref }}</span>
                @endif
            </td>
        </tr>
    </table>

    <h2 class="article">Vehicle Condition Diagram</h2>
    <p class="condition-note">Show any scratch or damage using an X on the relevant view at pickup and at return.</p>
    <table class="condition-grid">
        <tr>
            <td>Front</td>
            <td>Rear</td>
            <td>Left side</td>
            <td>Right side</td>
            <td>Top</td>
        </tr>
    </table>

    <div class="comment-box">
        <div class="label">Comments</div>
        <div class="line">&nbsp;</div>
        <div class="line">&nbsp;</div>
        <div class="line">&nbsp;</div>
    </div>

    <p class="done-line">Done in Kigali, on {{ now()->format('d/m/Y') }}.</p>

    <table class="signatures">
        <tr>
            <td>
                <strong>Owner</strong><br>
                Name: {{ $reservation->vehicle->partner?->publicLabel() ?? 'FlexiRent' }}
                @if($reservation->vehicle->partner)
                    <br><span style="font-size:9px;color:#777;">(partner of FlexiRent)</span>
                @endif
                <div class="sig-line">Signature</div>
            </td>
            <td>
                <strong>Renter</strong><br>
                Name: {{ $reservation->client->name }}
                <div class="sig-line">Signature</div>
            </td>
        </tr>
    </table>
</body>
</html>
