<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Dedicated admin authentication, kept fully separate from the client/
 * partner login (AuthController) so administrator access never shares a
 * form, session flow, or error path with regular users. This includes its
 * own password reset flow: PasswordResetController explicitly refuses to
 * touch admin accounts, by design, so admins need their own path back in.
 */
class AdminAuthController extends Controller
{
    private const NOT_ADMIN_MESSAGE = 'No administrator account matches that email address.';

    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // SECURITY: the admin portal is the most valuable target, so it
        // never reveals whether an email exists or which half was wrong;
        // one generic message for unknown email, wrong password or a
        // non-admin account (no way to map who the administrators are).
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->isAdmin()) {
            return back()->withErrors([
                'email' => 'These credentials do not match an administrator account.',
            ])->onlyInput('email');
        }

        Auth::login($user);

        if (Auth::user()->is_suspended) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'This admin account has been suspended.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function showForgotForm()
    {
        return view('admin.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        // Same answer whether or not the email belongs to an admin, so the
        // form can't be used to discover administrator addresses.
        $genericStatus = 'If this email belongs to an administrator account, a reset link has been sent to it.';
        if (! User::where('email', $request->email)->where('role', 'admin')->exists()) {
            return back()->with('status', $genericStatus);
        }

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['email' => 'We could not send the reset email right now. Please try again later.']);
        }

        return $status === Password::RESET_LINK_SENT || $status === Password::RESET_THROTTLED
            ? back()->with('status', $genericStatus)
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('admin.reset-password', [
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

        if (! User::where('email', $validated['email'])->where('role', 'admin')->exists()) {
            return back()->withErrors(['email' => self::NOT_ADMIN_MESSAGE]);
        }

        $status = Password::reset($validated, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('admin.login')->with('status', 'Your password has been reset. You can now log in.')
            : back()->withErrors(['email' => __($status)]);
    }
}
