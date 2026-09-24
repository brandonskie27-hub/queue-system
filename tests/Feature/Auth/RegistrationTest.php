<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_turned_off(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_staff_accounts_are_created_with_the_artisan_command(): void
    {
        $this->artisan('staff:create', ['name' => 'Jane Cruz', 'email' => 'jane@example.com'])
            ->expectsQuestion('Password', 'a-good-password')
            ->expectsOutput('Staff account created for jane@example.com.')
            ->assertSuccessful();

        $user = User::sole();
        $this->assertSame('Jane Cruz', $user->name);
        $this->assertTrue(Hash::check('a-good-password', $user->password));
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertFalse($user->is_admin);
    }

    public function test_the_command_can_create_an_admin(): void
    {
        $this->artisan('staff:create', ['name' => 'Jane Cruz', 'email' => 'jane@example.com', '--admin' => true])
            ->expectsQuestion('Password', 'a-good-password')
            ->expectsOutput('Admin account created for jane@example.com.')
            ->assertSuccessful();

        $this->assertTrue(User::sole()->is_admin);
    }

    public function test_the_command_rejects_an_email_that_is_already_taken(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->artisan('staff:create', ['name' => 'Jane Cruz', 'email' => 'jane@example.com'])
            ->expectsQuestion('Password', 'a-good-password')
            ->expectsOutput('The email has already been taken.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }
}
