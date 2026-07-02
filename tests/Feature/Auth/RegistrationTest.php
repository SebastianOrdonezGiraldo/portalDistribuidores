<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\AuthAccess\Mail\DistributorRegistrationNotificationMail;
use App\Modules\AuthAccess\Mail\EmailVerificationCodeMail;
use App\Modules\Shared\Enums\DistributorStatus;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_sends_admin_notification_email(): void
    {
        Mail::fake();
        config(['mail.registration_notification_to' => 'ventas-notify@test.com']);

        $this->post('/register', [
            'name' => 'María López',
            'company_name' => 'Distribuidora Notificación SAS',
            'nit' => '9009998887',
            'city' => 'Medellín',
            'address' => 'Carrera 10',
            'phone' => '3112223344',
            'email' => 'notif-dist@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        Mail::assertSent(DistributorRegistrationNotificationMail::class, function (DistributorRegistrationNotificationMail $mail): bool {
            return $mail->distributor->nit === '9009998887'
                && $mail->distributor->name === 'Distribuidora Notificación SAS';
        });
    }

    public function test_new_users_can_register(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Juan Pérez',
            'company_name' => 'Distribuciones Prueba SAS',
            'nit' => '9001234567',
            'city' => 'Bogotá',
            'address' => 'Calle 1 # 2-3',
            'phone' => '3001234567',
            'email' => 'registro@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('register.verify-email', absolute: false));

        $user = User::query()->where('email', 'registro@example.com')->firstOrFail();
        $this->assertSame(UserRole::Distributor, $user->role);
        $this->assertNotNull($user->distributor_id);
        $this->assertNotNull($user->email_verification_code);
        $this->assertNotNull($user->email_verification_code_expires_at);

        Mail::assertSent(EmailVerificationCodeMail::class, function (EmailVerificationCodeMail $mail) use ($user): bool {
            return $mail->user->id === $user->id;
        });

        $this->assertDatabaseHas('distributors', [
            'id' => $user->distributor_id,
            'name' => 'Distribuciones Prueba SAS',
            'status' => DistributorStatus::PendingReview->value,
            'nit' => '9001234567',
            'city' => 'Bogotá',
            'address' => 'Calle 1 # 2-3',
            'phone' => '3001234567',
            'contact_name' => 'Juan Pérez',
            'contact_email' => 'registro@example.com',
        ]);
    }

    public function test_registration_requires_numeric_nit_phone_and_alpha_city(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Juan Pérez',
            'company_name' => 'Distribuciones Prueba SAS',
            'nit' => '900123456-7',
            'city' => 'Bogotá 123',
            'address' => 'Calle 1 # 2-3',
            'phone' => '300-123-4567',
            'email' => 'registro-formato@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['nit', 'city', 'phone']);
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
