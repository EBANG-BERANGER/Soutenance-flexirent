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

        // Told apart deliberately (account not found vs. wrong password)
        // instead of one generic "credentials don't match" message, per
        // product decision: this app doesn't need to guard against
        // account-enumeration the way a bank login would.
        $user = User::where('email', $credentials['email'])->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'No account found with that email address.',
            ])->onlyInput('email');
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'Incorrect password.',
            ])->onlyInput('email');
        }

        Auth::login($user);

        if (! Auth::user()->isAdmin()) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'This portal is for administrators only. Use the regular login page instead.',
            ])->onlyInput('email');
        }

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

        if (! User::where('email', $request->email)->where('role', 'admin')->exists()) {
            return back()->withErrors(['email' => self::NOT_ADMIN_MESSAGE]);
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
