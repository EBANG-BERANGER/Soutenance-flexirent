<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function notice()
    {
        if (auth()->user()->hasVerifiedEmail()) {
            return redirect()->route('vehicles.index');
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('vehicles.index')->with('status', 'Email already verified.');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->route('vehicles.index')->with('status', 'Your email address has been verified.');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('vehicles.index');
        }

        $user = $request->user();
        dispatch(function () use ($user) {
            \App\Support\MailRetry::attempt(fn () => $user->sendEmailVerificationNotification());
        });

        return back()->with('status', 'A new verification link has been sent to your email address.');
    }
}
