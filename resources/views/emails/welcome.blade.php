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
                        <td style="background:#101728; padding:28px; text-align:center;">
                            @if(file_exists(public_path('images/logo-icon.png')))
                                <img src="{{ $message->embed(public_path('images/logo-icon.png')) }}" width="56" height="56" style="border-radius:50%; display:block; margin:0 auto 10px;">
                            @endif
                            <span style="color:#ffffff; font-size:20px; font-weight:700;">FlexiRent</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 28px;">
                            <h1 style="margin:0 0 16px; font-size:19px; color:#101728;">Welcome to FlexiRent, {{ $user->name }}!</h1>
                            <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#3a4256;">
                                Your account (<strong>{{ '@' . $user->username }}</strong>) has been created successfully.
                                You can now browse our fleet of verified vehicles across Kigali, book a rental, and pay securely with
                                MTN Mobile Money or Airtel Money.
                            </p>
                            <p style="margin:0 0 22px; font-size:14px; line-height:1.6; color:#3a4256;">
                                Before you book, please verify your email address using the link we sent separately. It only takes a second.
                            </p>
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background:#C7962C; border-radius:100px;">
                                        <a href="{{ route('vehicles.fleet') }}" style="display:inline-block; padding:11px 26px; color:#ffffff; font-size:14px; font-weight:600; text-decoration:none;">Browse the fleet</a>
                                    </td>
                                </tr>
                            </table>
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
