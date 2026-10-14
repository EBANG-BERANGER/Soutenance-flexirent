<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates (or updates) the platform's real admin account from
 * ADMIN_EMAIL / ADMIN_PASSWORD in .env. Unlike the demo accounts below,
 * this is a genuine personal credential, so it stays out of source control
 * instead of being hardcoded here.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set in .env, skipping admin account.');

            return;
        }

        $attributes = [
            'name' => 'EBANG Beranger',
            'password' => Hash::make($password),
            'role' => 'admin',
            'address' => 'Kigali, Rwanda',
            'email_verified_at' => now(),
            'is_suspended' => false,
        ];

        $existing = User::where('email', $email)->first();

        if ($existing) {
            $existing->update($attributes);
            $this->command?->info("Admin account updated: {$existing->email}");

            return;
        }

        $username = 'ebang.beranger';
        $suffix = 1;
        while (User::where('username', $username)->exists()) {
            $username = 'ebang.beranger' . $suffix++;
        }

        User::create($attributes + ['email' => $email, 'username' => $username]);
        $this->command?->info("Admin account created: {$email}");
    }
}
