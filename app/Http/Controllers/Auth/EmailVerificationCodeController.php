<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\AuthAccess\Mail\DistributorRegistrationNotificationMail;
use App\Modules\AuthAccess\Mail\EmailVerificationCodeMail;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\DistributorStatus;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class EmailVerificationCodeController extends Controller
{
    public function show(Request $request): RedirectResponse|View
    {
        $pending = $this->pending($request);

        if (! $pending) {
            return redirect()->route('register');
        }

        return view('auth.verify-email-code', ['email' => $pending['email'], 'resendCooldown' => RateLimiter::availableIn($this->key($pending['email']))]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'digits:4']]);
        $pending = $this->pending($request);

        if (! $pending) {
            return redirect()->route('register');
        }

        if ($pending['expires_at'] <= now()->timestamp || ! Hash::check($request->string('code')->toString(), $pending['code'])) {
            return back()->withErrors(['code' => 'El código es incorrecto o ha expirado.']);
        }

        $distributor = DB::transaction(function () use ($pending): Distributor {
            $distributor = Distributor::create(['name' => $pending['company_name'], 'status' => DistributorStatus::PendingReview, 'nit' => $pending['nit'], 'address' => $pending['address'], 'city' => $pending['city'], 'phone' => $pending['phone'], 'contact_email' => $pending['email'], 'contact_name' => $pending['name']]);
            User::create(['name' => $pending['name'], 'email' => $pending['email'], 'password' => $pending['password'], 'role' => UserRole::Distributor, 'distributor_id' => $distributor->id, 'is_active' => true, 'email_verified_at' => now()]);

            return $distributor;
        });
        $this->notifyAdmin($distributor);
        $request->session()->forget('pending_registration');
        RateLimiter::clear($this->key($pending['email']));

        return redirect()->route('register.pending')->with('status', 'Recibimos tu solicitud. Validaremos tus datos y activaremos tu acceso.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $pending = $this->pending($request);

        if (! $pending) {
            return redirect()->route('register');
        }
        $key = $this->key($pending['email']);

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->with('status', 'Espera un momento antes de solicitar otro código.');
        }
        $code = (string) random_int(1000, 9999);

        try {
            Mail::to($pending['email'])->send(new EmailVerificationCodeMail($pending['name'], $code));
        } catch (\Throwable $exception) {
            Log::error('email_verification_code.resend.failed', [
                'email' => $pending['email'],
                'mailer' => config('mail.default'),
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'resend' => 'No pudimos reenviar el código. Inténtalo nuevamente en unos segundos.',
            ]);
        }

        $pending['code'] = Hash::make($code);
        $pending['expires_at'] = now()->addMinutes(15)->timestamp;
        $request->session()->put('pending_registration', $pending);
        RateLimiter::hit($key, 60);

        return back()->with('status', 'Te enviamos un nuevo código de verificación.');
    }

    private function pending(Request $request): ?array
    {
        $data = $request->session()->get('pending_registration');

        return is_array($data) ? $data : null;
    }

    private function key(string $email): string
    {
        return 'registration-email-verification-resend:'.hash('sha256', $email);
    }

    private function notifyAdmin(Distributor $distributor): void
    {
        $to = trim((string) config('mail.registration_notification_to'));

        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($to)->send(new DistributorRegistrationNotificationMail($distributor));
        } catch (\Throwable $exception) {
            Log::error('distributor.registration_notification_email.failed', [
                'distributor_id' => $distributor->id,
                'mailer' => config('mail.default'),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
