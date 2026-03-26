<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_can_be_disabled_by_configuration(): void
    {
        $previous = getenv('AUTH_ALLOW_PUBLIC_REGISTRATION');

        putenv('AUTH_ALLOW_PUBLIC_REGISTRATION=false');
        $_ENV['AUTH_ALLOW_PUBLIC_REGISTRATION'] = 'false';
        $_SERVER['AUTH_ALLOW_PUBLIC_REGISTRATION'] = 'false';

        $this->refreshApplication();

        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        if ($previous === false) {
            putenv('AUTH_ALLOW_PUBLIC_REGISTRATION');
            unset($_ENV['AUTH_ALLOW_PUBLIC_REGISTRATION'], $_SERVER['AUTH_ALLOW_PUBLIC_REGISTRATION']);
        } else {
            putenv("AUTH_ALLOW_PUBLIC_REGISTRATION={$previous}");
            $_ENV['AUTH_ALLOW_PUBLIC_REGISTRATION'] = $previous;
            $_SERVER['AUTH_ALLOW_PUBLIC_REGISTRATION'] = $previous;
        }

        $this->refreshApplication();
    }
}
