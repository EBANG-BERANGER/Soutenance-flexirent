<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A couple of easy-to-remember, clearly-labeled demo accounts for UAT
 * testers, on top of the larger realistic-but-easy-to-lose-track-of pool
 * from DemoClientsSeeder / DemoFleetSeeder. Real-looking @gmail.com
 * addresses (fictional people, not real ones) so a tester can log in
 * without hunting through seeder output for one of ~30 generated accounts.
 * The admin account itself is already covered by AdminSeeder (a real
 * credential from .env, not a demo one).
 */
class UatDemoSeeder extends Seeder
{
    private const PASSWORD = 'Demo@2026!';

    public function run(): void
    {
        $client = User::firstOrCreate(
            ['email' => 'flexirent.demo.client@gmail.com'],
            [
                'name' => 'Divine Uwera',
                'username' => 'divine.uwera.demo',
                'password' => Hash::make(self::PASSWORD),
                'role' => 'client',
                'phone' => '+250 788 000 001',
                'address' => 'Kigali, Rwanda',
                'email_verified_at' => now(),
            ]
        );

        $partnerUser = User::firstOrCreate(
            ['email' => 'flexirent.demo.partner@gmail.com'],
            [
                'name' => 'Eric Nshimiyimana',
                'username' => 'eric.nshimiyimana.demo',
                'password' => Hash::make(self::PASSWORD),
                'role' => 'partner',
                'phone' => '+250 788 000 002',
                'address' => 'Kigali, Rwanda',
                'email_verified_at' => now(),
            ]
        );

        Partner::firstOrCreate(
            ['user_id' => $partnerUser->id],
            [
                'partner_type' => 'individual',
                'company_name' => null,
                'tin' => null,
                'national_id_number' => (string) random_int(1000000000000000, 1999999999999999),
                'status' => 'validated',
                'is_suspended' => false,
            ]
        );

        $this->command?->info('Seeded 2 UAT demo accounts (client: ' . $client->email . ', partner: ' . $partnerUser->email . '), password: ' . self::PASSWORD);
    }
}
