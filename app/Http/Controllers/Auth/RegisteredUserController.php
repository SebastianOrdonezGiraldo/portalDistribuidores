<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\AuthAccess\Mail\EmailVerificationCodeMail;
use App\Modules\Shared\Support\EmailMasker;
use App\Rules\TurnstileToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $this->ensurePublicRegistrationIsEnabled();

        return view('auth.register');
    }

    public function pending(): View
    {
        $this->ensurePublicRegistrationIsEnabled();

        return view('auth.register-pending');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensurePublicRegistrationIsEnabled();

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => ['required', 'string', 'max:120'],
            'nit' => ['required', 'string', 'max:40', 'regex:/^\d+$/', 'unique:distributors,nit'],
            'city' => ['required', 'string', 'max:120', 'regex:/^[\pL\s]+$/u'],
            'address' => ['nullable', 'string', 'max:180'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^\d+$/'],
            'privacy_accepted' => ['accepted'],
            'cf-turnstile-response' => [new TurnstileToken],
        ], [
            'privacy_accepted.accepted' => 'Debes autorizar el tratamiento de datos personales para continuar.',
        ]);

        $code = (string) random_int(1000, 9999);
        $pendingRegistration = [
            'name' => $payload['name'], 'email' => $payload['email'], 'password' => Hash::make($payload['password']),
            'company_name' => $payload['company_name'], 'nit' => $payload['nit'], 'city' => $payload['city'],
            'address' => $payload['address'] ?: null, 'phone' => $payload['phone'],
            'privacy_accepted_at' => now()->toIso8601String(),
            'privacy_policy_version' => (string) config('legal.privacy_policy_version', '2026-07'),
            'code' => Hash::make($code), 'expires_at' => now()->addMinutes(15)->timestamp,
        ];

        $request->session()->forget('pending_registration');

        try {
            Mail::to($payload['email'])->send(new EmailVerificationCodeMail($payload['name'], $code));
        } catch (\Throwable $exception) {
            Log::error('email_verification_code.send.failed', [
                'email' => EmailMasker::mask($payload['email']),
                'mailer' => config('mail.default'),
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['email' => 'No pudimos enviar el código de verificación. Inténtalo nuevamente en unos segundos.']);
        }

        $request->session()->put('pending_registration', $pendingRegistration);
        RateLimiter::hit($this->resendKey($payload['email']), 60);

        return redirect()->route('register.verify-email');
    }

    private function resendKey(string $email): string
    {
        return 'registration-email-verification-resend:'.hash('sha256', $email);
    }

    private function ensurePublicRegistrationIsEnabled(): void
    {
        abort_unless(config('auth.allow_public_registration'), 404);
    }
}
