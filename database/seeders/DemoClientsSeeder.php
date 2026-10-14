<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo client accounts for the presentation, with realistic names drawn
 * from real naming conventions in Gabon, Rwanda and Chad (not random
 * placeholders), so the platform's client list looks authentic.
 */
class DemoClientsSeeder extends Seeder
{
    private const PASSWORD = 'Demo@2026!';

    public function run(): void
    {
        $names = [
            // Gabon: Bantu patronymic surnames (Ndong, Nguema, Mba, Ondo...) with French first names
            ['Sylvie Nguema', 'GA'],
            ['Landry Ondo', 'GA'],
            ['Aurélie Mba', 'GA'],
            ['Judicaël Ndong', 'GA'],
            ['Prisca Moussavou', 'GA'],
            ['Steevy Obame', 'GA'],
            ['Rosalie Koumba', 'GA'],
            ['Ghislain Abessolo', 'GA'],
            ['Carelle Mabicka', 'GA'],
            ['Fabrice Ngoma', 'GA'],
            ['Christevie Boussoughou', 'GA'],

            // Rwanda: Kinyarwanda meaningful names, often paired with a French/Christian first name
            ['Jean Habimana', 'RW'],
            ['Marie Uwimana', 'RW'],
            ['Eric Hakizimana', 'RW'],
            ['Alice Ingabire', 'RW'],
            ['Emmanuel Mugisha', 'RW'],
            ['Diane Uwase', 'RW'],
            ['Claude Niyonsaba', 'RW'],
            ['Aline Mukamana', 'RW'],
            ['Patrick Nshuti', 'RW'],
            ['Grace Iradukunda', 'RW'],
            ['Fabrice Twizeyimana', 'RW'],

            // Chad: Arabic/Islamic patronymic names, common as both first and last names
            ['Mahamat Ali', 'TD'],
            ['Fatimé Issa', 'TD'],
            ['Youssouf Oumar', 'TD'],
            ['Khadidja Hassan', 'TD'],
            ['Idriss Brahim', 'TD'],
            ['Achta Moussa', 'TD'],
            ['Hissein Ahmat', 'TD'],
            ['Zara Abdoulaye', 'TD'],
            ['Ousmane Mahamat', 'TD'],
            ['Halimé Youssouf', 'TD'],
        ];

        $phonePrefix = ['GA' => '+241', 'RW' => '+250', 'TD' => '+235'];

        $emailDomains = ['gmail.com', 'outlook.com'];

        foreach ($names as $index => [$fullName, $country]) {
            $slug = Str::slug($fullName, '.');
            $username = $slug . '_' . random_int(10, 99);
            $email = $slug . '@' . $emailDomains[$index % count($emailDomains)];

            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $fullName,
                    'username' => $username,
                    'password' => Hash::make(self::PASSWORD),
                    'role' => 'client',
                    'phone' => $phonePrefix[$country] . ' ' . random_int(600000000, 799999999),
                    'address' => 'Kigali, Rwanda',
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command?->info('Seeded ' . count($names) . ' demo client accounts (password: ' . self::PASSWORD . ').');
    }
}
