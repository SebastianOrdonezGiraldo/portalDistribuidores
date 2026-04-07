<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Shared\Enums\CompanyRole;
use App\Modules\Shared\Enums\DistributorStatus;
use App\Modules\Shared\Enums\UserRole;
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
            'name' => 'Juan Pérez',
            'company_name' => 'Distribuciones Prueba SAS',
            'nit' => '900123456-7',
            'city' => 'Bogotá',
            'address' => 'Calle 1 # 2-3',
            'phone' => '3001234567',
            'email' => 'registro@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('register.pending', absolute: false));

        $user = User::query()->where('email', 'registro@example.com')->firstOrFail();
        $this->assertSame(UserRole::Distributor, $user->role);
        $this->assertSame(CompanyRole::AdminEmpresa, $user->company_role);
        $this->assertNotNull($user->distributor_id);

        $this->assertDatabaseHas('distributors', [
            'id' => $user->distributor_id,
            'name' => 'Distribuciones Prueba SAS',
            'status' => DistributorStatus::PendingReview->value,
            'nit' => '900123456-7',
            'city' => 'Bogotá',
            'address' => 'Calle 1 # 2-3',
            'phone' => '3001234567',
            'contact_name' => 'Juan Pérez',
            'contact_email' => 'registro@example.com',
        ]);
    }

    public function test_registration_can_be_disabled_by_configuration(): void
    {
        config()->set('auth.allow_public_registration', false);

        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }
}
