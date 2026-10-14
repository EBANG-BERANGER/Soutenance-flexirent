<?php

namespace App\Http\Controllers;

use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username,' . $user->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+[1-9]\d{0,3}[\s\d]{6,14}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo_path'] = ImageOptimizer::store($request->file('profile_photo'), 'profile-photos', 'public', 512);
        }
        unset($validated['profile_photo']);

        $emailChanged = $validated['email'] !== $user->email;

        $user->update($validated);

        if ($emailChanged && $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail) {
            $user->email_verified_at = null;
            $user->save();
            dispatch(function () use ($user) {
                \App\Support\MailRetry::attempt(fn () => $user->sendEmailVerificationNotification());
            });
        }

        return back()->with('status', 'Your profile has been updated.');
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            // old "remember me" cookies stop working
            'remember_token' => \Illuminate\Support\Str::random(60),
        ])->save();

        // new session id after a password change
        $request->session()->regenerate();

        return back()->with('status', 'Your password has been changed.');
    }
}
