<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\AuthAccess\Mail\DistributorRegistrationNotificationMail;
use App\Modules\AuthAccess\Mail\EmailVerificationCodeMail;
use App\Modules\Shared\Enums\DistributorStatus;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_requires_valid_turnstile_when_enabled(): void
    {
        Http::fake([
            'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['missing-input-response'],
            ], 200),
        ]);

        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.site_key' => '1x00000000000000000000AA',
            'services.turnstile.secret_key' => '1x0000000000000000000000000000000AA',
        ]);

        Mail::fake();

        $this->from('/register')->post('/register', [
            'name' => 'Juan Pérez',
            'company_name' => 'Distribuciones Prueba SAS',
            'nit' => '9001234567',
            'city' => 'Bogotá',
            'address' => 'Calle 1 # 2-3',
            'phone' => '3001234567',
            'email' => 'registro-captcha@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors('cf-turnstile-response');

        Mail::assertNothingSent();
    }

    public function test_registration_accepts_valid_turnstile_when_enabled(): void
    {
        Http::fake([
            'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
            ], 200),
        ]);

        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.site_key' => '1x00000000000000000000AA',
            'services.turnstile.secret_key' => '1x0000000000000000000000000000000AA',
        ]);

        Mail::fake();

        $this->post('/register', [
            'name' => 'Juan Pérez',
            'company_name' => 'Distribuciones Captcha SAS',
            'nit' => '9001234599',
            'city' => 'Bogotá',
            'address' => 'Calle 1 # 2-3',
            'phone' => '3001234567',
            'email' => 'registro-ok-captcha@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'cf-turnstile-response' => 'valid-token',
        ])->assertRedirect(route('register.verify-email', absolute: false));

        Mail::assertSent(EmailVerificationCodeMail::class);
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
        ])->assertRedirect(route('register.verify-email', absolute: false));

        $verificationCode = null;
        Mail::assertSent(EmailVerificationCodeMail::class, function (EmailVerificationCodeMail $mail) use (&$verificationCode): bool {
            $verificationCode = $mail->code;

            return $mail->recipientName === 'María López';
        });

        $this->post(route('register.verify-email.store'), [
            'code' => $verificationCode,
        ])->assertRedirect(route('register.pending', absolute: false));

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

        $this->assertDatabaseMissing('users', ['email' => 'registro@example.com']);
        $this->assertDatabaseMissing('distributors', ['nit' => '9001234567']);

        $verificationCode = null;
        Mail::assertSent(EmailVerificationCodeMail::class, function (EmailVerificationCodeMail $mail) use (&$verificationCode): bool {
            $verificationCode = $mail->code;

            return $mail->recipientName === 'Juan Pérez'
                && preg_match('/^\\d{4}$/', $mail->code) === 1;
        });

        $this->post(route('register.verify-email.store'), [
            'code' => $verificationCode,
        ])->assertRedirect(route('register.pending', absolute: false));

        $user = User::query()->where('email', 'registro@example.com')->firstOrFail();
        $this->assertSame(UserRole::Distributor, $user->role);
        $this->assertNotNull($user->distributor_id);
        $this->assertNotNull($user->email_verified_at);

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

    public function test_email_verification_code_resend_has_a_one_minute_cooldown(): void
    {
        Mail::fake();

        $email = 'cooldown@example.com';
        $key = 'registration-email-verification-resend:'.hash('sha256', $email);
        RateLimiter::hit($key, 60);

        $response = $this
            ->withSession(['pending_registration' => [
                'name' => 'Usuario Prueba',
                'email' => $email,
                'password' => 'hashed-password',
                'company_name' => 'Empresa Prueba',
                'nit' => '9000000001',
                'city' => 'Bogotá',
                'address' => null,
                'phone' => '3000000000',
                'code' => 'hashed-code',
                'expires_at' => now()->addMinutes(15)->timestamp,
            ]])
            ->from(route('register.verify-email'))
            ->post(route('register.verify-email.resend'));

        $response
            ->assertRedirect(route('register.verify-email', absolute: false))
            ->assertSessionHas('status', 'Espera un momento antes de solicitar otro código.');
        Mail::assertNothingSent();

        RateLimiter::clear($key);
    }

    public function test_invalid_verification_code_does_not_create_the_registration(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Ana Prueba',
            'company_name' => 'Empresa Sin Verificar SAS',
            'nit' => '9005554443',
            'city' => 'Cali',
            'address' => null,
            'phone' => '3005554443',
            'email' => 'sin-verificar@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->post(route('register.verify-email.store'), [
            'code' => '0000',
        ])->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('users', ['email' => 'sin-verificar@example.com']);
        $this->assertDatabaseMissing('distributors', ['nit' => '9005554443']);
        Mail::assertNotSent(DistributorRegistrationNotificationMail::class);
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
