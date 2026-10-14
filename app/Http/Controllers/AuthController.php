<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeMail;
use App\Models\BannedContact;
use App\Models\Partner;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // Accept either an email address or a username as the identifier,
        // and authenticate against whichever field it actually matches.
        $field = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Told apart deliberately (account not found vs. wrong password)
        // instead of one generic "credentials don't match" message, per
        // product decision: this app doesn't need to guard against
        // account-enumeration the way a bank login would.
        $user = User::where($field, $validated['login'])->first();

        if (! $user) {
            return back()->withErrors([
                'login' => $field === 'email' ? 'No account found with that email address.' : 'No account found with that username.',
            ])->onlyInput('login');
        }

        if (! Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'login' => 'Incorrect password.',
            ])->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));

        if (Auth::user()->isAdmin()) {
            Auth::logout();

            return back()->withErrors([
                'login' => 'Administrator accounts must sign in through the admin portal.',
            ])->onlyInput('login');
        }

        if (Auth::user()->isBanned()) {
            Auth::logout();

            return back()->withErrors([
                'login' => 'This account has been permanently banned from FlexiRent.',
            ])->onlyInput('login');
        }

        if (Auth::user()->is_suspended) {
            Auth::logout();

            return back()->withErrors([
                'login' => 'This account has been suspended. Contact FlexiRent support for assistance.',
            ])->onlyInput('login');
        }

        $request->session()->regenerate();

        // If the user was redirected here from a protected link (e.g. an
        // email verification link opened in a fresh browser session),
        // send them back there instead of always landing on the dashboard.
        return redirect()->intended($this->homeUrlForRole(Auth::user()));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $isPartner = $request->input('role') === 'partner';
        $isAgency = $isPartner && $request->input('partner_type') === 'agency';
        $isIndividualPartner = $isPartner && $request->input('partner_type') === 'individual';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+[1-9]\d{0,3}[\s\d]{6,14}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'role' => ['required', 'in:client,partner'],
            'partner_type' => ['required_if:role,partner', 'nullable', 'in:agency,individual'],
            'company_name' => [$isAgency ? 'required' : 'nullable', 'string', 'max:255'],
            'tin' => [$isAgency ? 'required' : 'nullable', 'string', 'max:50'],
            'national_id_number' => [$isIndividualPartner ? 'required' : 'nullable', 'string', 'max:50'],
            'documents_path' => [$isPartner ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ], [
            'phone.regex' => 'Enter the phone number with its country code, e.g. +250 78 000 0000.',
            'username.alpha_dash' => 'Username may only contain letters, numbers, dashes and underscores.',
            'company_name.required' => 'Company name is required for an agency account.',
            'tin.required' => 'TIN is required for an agency account.',
            'national_id_number.required' => 'National ID number is required for an individual owner account.',
            'documents_path.required' => 'A supporting document (ID or business registration) is required to register as a partner.',
        ]);

        if (BannedContact::matches($validated['email'], $validated['phone'] ?? null)) {
            return back()->withErrors([
                'email' => 'This email or phone number is no longer permitted to register on FlexiRent.',
            ])->onlyInput('email', 'phone');
        }

        $photoPath = $request->hasFile('profile_photo')
            ? ImageOptimizer::store($request->file('profile_photo'), 'profile-photos', 'public', 512)
            : null;

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'profile_photo_path' => $photoPath,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        if ($validated['role'] === 'partner') {
            $documentsPath = $request->file('documents_path')->store('partner-documents', 'local');

            $partner = Partner::create([
                'user_id' => $user->id,
                'partner_type' => $validated['partner_type'],
                'company_name' => $validated['company_name'] ?? null,
                'tin' => $validated['tin'] ?? null,
                'national_id_number' => $validated['national_id_number'] ?? null,
                'documents_path' => $documentsPath,
                'status' => 'pending',
            ]);

            $this->notificationService->notifyAdminsNewPartnerRegistration($partner);
        }

        // dispatch() here is meant to run this off the request/response path
        // via the queue, so a slow/hanging SMTP connection can't fatally
        // time out the whole registration request. That only works with a
        // real queue worker running (QUEUE_CONNECTION=database + `php
        // artisan queue:work`); without one, jobs pile up in the `jobs`
        // table and never send. This app currently runs with
        // QUEUE_CONNECTION=sync (no worker process available), so dispatch()
        // executes immediately inline instead: switch back to `database`
        // (and keep a queue:work process running) once one exists.
        dispatch(function () use ($user) {
            \App\Support\MailRetry::attempt(fn () => $user->sendEmailVerificationNotification());
        });

        try {
            Mail::to($user->email)->send(new WelcomeMail($user));
        } catch (\Throwable $e) {
            Log::warning('Failed to send welcome email: ' . $e->getMessage());
        }

        Auth::login($user);

        return redirect()->intended($this->homeUrlForRole($user));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function homeUrlForRole(User $user): string
    {
        return match ($user->role) {
            'admin' => route('admin.dashboard'),
            'partner' => route('partner.dashboard'),
            default => route('vehicles.index'),
        };
    }
}
