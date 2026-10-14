<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Regression test: partners.company_name was NOT NULL despite individual
     * (non-agency) partners being allowed to register without one, so every
     * individual-partner signup failed with a fatal SQL error.
     */
    public function test_an_individual_partner_can_register_without_a_company_name(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Jane Owner',
            'username' => 'jane_owner',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'partner',
            'partner_type' => 'individual',
            'national_id_number' => 'ID998877',
            'documents_path' => UploadedFile::fake()->create('id-scan.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->partner);
        $this->assertSame('individual', $user->partner->partner_type);
        $this->assertNull($user->partner->company_name);
        $this->assertSame('ID998877', $user->partner->national_id_number);
        $this->assertNotNull($user->partner->documents_path);
        $this->assertSame('pending', $user->partner->status);
    }

    public function test_an_agency_partner_registration_requires_a_company_name(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Big Rentals',
            'username' => 'big_rentals',
            'email' => 'big@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'partner',
            'partner_type' => 'agency',
            'documents_path' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('company_name');
        $this->assertNull(User::where('email', 'big@example.com')->first());
    }

    public function test_a_partner_registration_requires_a_supporting_document(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Jane Owner',
            'username' => 'jane_owner_nodoc',
            'email' => 'jane_nodoc@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'partner',
            'partner_type' => 'individual',
            'national_id_number' => 'ID998877',
        ]);

        $response->assertSessionHasErrors('documents_path');
        $this->assertNull(User::where('email', 'jane_nodoc@example.com')->first());
    }

    public function test_admins_are_notified_when_a_new_partner_registers(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post('/register', [
            'name' => 'Jane Owner',
            'username' => 'jane_owner2',
            'email' => 'jane2@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'partner',
            'partner_type' => 'individual',
            'national_id_number' => 'ID998877',
            'documents_path' => UploadedFile::fake()->create('id-scan.pdf', 100, 'application/pdf'),
        ]);

        $this->assertTrue(
            Notification::where('user_id', $admin->id)
                ->where('title', 'like', 'New partner registration%')
                ->exists()
        );
    }
}
