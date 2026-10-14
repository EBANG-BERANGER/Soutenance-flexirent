<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background:#F3F6FB; font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F6FB; padding:32px 16px;">
        <tr>
            <td align="center">
                <table width="480" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 10px 28px rgba(16,23,40,.08);">
                    <tr>
                        <td style="background:#101728; padding:22px 28px;" align="left">
                            @if(file_exists(public_path('images/logo-icon.png')))
                                <img src="{{ $message->embed(public_path('images/logo-icon.png')) }}" width="32" height="32" style="border-radius:50%; vertical-align:middle;">
                            @endif
                            <span style="color:#ffffff; font-size:20px; font-weight:700; vertical-align:middle; margin-left:10px;">FlexiRent</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 16px; font-size:18px; color:#101728;">Your rental contract is ready</h1>
                            <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#3a4256;">
                                Reservation #{{ $reservation->id }} for the <strong>{{ $reservation->vehicle->model }}</strong>
                                ({{ $reservation->start_date->format('d/m/Y') }} &ndash; {{ $reservation->end_date->format('d/m/Y') }}).
                                The signed agreement is attached as a PDF. Keep it as your proof of booking.
                            </p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F6FB; border-radius:10px; margin:0 0 14px;">
                                <tr>
                                    <td style="padding:14px 16px; font-size:13px; color:#3a4256;">
                                        <strong>Total price:</strong> {{ number_format($reservation->total_price) }} Frw<br>
                                        <strong>Paid:</strong> {{ number_format($reservation->amountPaid()) }} Frw<br>
                                        <strong>Remaining:</strong> {{ number_format($reservation->balanceDue()) }} Frw
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#3a4256;">
                                If any balance remains, it is due at pickup. Thank you for booking with FlexiRent.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px; background:#F3F6FB; font-size:12px; color:#5B6472;">
                            FlexiRent &middot; Kigali, Rwanda &middot; This is an automated message, please do not reply.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
