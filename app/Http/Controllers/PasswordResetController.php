<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Client/partner self-service password reset. Deliberately unusable
 * against administrator accounts, for the same reason the admin portal
 * never shares a login form with regular users (see AdminAuthController):
 * this is the "non-admin side" of the site and must never be a path into
 * an admin account, even indirectly via a password reset.
 */
class PasswordResetController extends Controller
{
    private const ADMIN_BLOCKED_MESSAGE = 'Administrator accounts cannot be reset from this page. Contact another administrator to have your password changed.';

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        // SECURITY: an admin email gets the same neutral answer as any other,
        // so this public form can't be used to find out who the admins are.
        if (User::where('email', $request->email)->where('role', 'admin')->exists()) {
            return back()->with('status', 'If an account exists for this email, a password reset link has been sent to it.');
        }

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['email' => 'We could not send the reset email right now. Please try again later.']);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'A password reset link has been sent to your email address.')
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if (User::where('email', $validated['email'])->where('role', 'admin')->exists()) {
            return back()->withErrors(['email' => self::ADMIN_BLOCKED_MESSAGE]);
        }

        $status = Password::reset($validated, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Your password has been reset. You can now log in.')
            : back()->withErrors(['email' => __($status)]);
    }
}
