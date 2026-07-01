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
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Mostrar formulario de registro publico.
     *
     * Solo existe cuando `AUTH_ALLOW_PUBLIC_REGISTRATION=true`.
     *
     * @group Autenticacion
     *
     * @unauthenticated
     *
     * @response 200 {"content":"Vista HTML de registro"}
     * @response 404 {"message":"Registro publico deshabilitado"}
     */
    public function create(): View
    {
        $this->ensurePublicRegistrationIsEnabled();

        return view('auth.register');
    }

    /**
     * Mostrar estado de registro pendiente.
     *
     * @group Autenticacion
     *
     * @unauthenticated
     *
     * @response 200 {"content":"Vista HTML de registro pendiente"}
     * @response 404 {"message":"Registro publico deshabilitado"}
     */
    public function pending(): View
    {
        $this->ensurePublicRegistrationIsEnabled();

        return view('auth.register-pending');
    }

    /**
     * Registrar distribuidor.
     *
     * Crea distribuidor pendiente, usuario asociado y envia codigo de verificacion de correo.
     *
     * @group Autenticacion
     *
     * @unauthenticated
     *
     * @bodyParam name string required Nombre del contacto. Example: Ana Perez
     * @bodyParam email string required Correo unico. Example: ana@example.com
     * @bodyParam password string required Contrasena con confirmacion. Example: secret123
     * @bodyParam password_confirmation string required Confirmacion de contrasena. Example: secret123
     * @bodyParam company_name string required Nombre de la empresa. Example: Distribuciones Medicas SAS
     * @bodyParam nit string required NIT solo numerico y unico. Example: 900123456
     * @bodyParam city string required Ciudad solo letras. Example: Bogota
     * @bodyParam address string Direccion. Example: Calle 100 # 10-20
     * @bodyParam phone string required Telefono solo numerico. Example: 3001234567
     *
     * @response 302 {"redirect":"register.verify-email"}
     * @response 404 {"message":"Registro publico deshabilitado"}
     * @response 422 {"message":"Datos invalidos"}
     * @response 429 {"message":"Has realizado demasiados intentos."}
     *
     * @throws ValidationException
     */
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
        ], [
            'nit.regex' => 'El NIT debe contener solo números.',
            'city.regex' => 'La ciudad debe contener solo letras.',
            'phone.regex' => 'El teléfono debe contener solo números.',
        ]);

        $distributor = DB::transaction(function () use ($payload): Distributor {
            $distributor = Distributor::create([
                'name' => $payload['company_name'],
                'status' => DistributorStatus::PendingReview,
                'nit' => $payload['nit'],
                'address' => $payload['address'] ?: null,
                'city' => $payload['city'],
                'phone' => $payload['phone'],
                'contact_email' => $payload['email'],
                'contact_name' => $payload['name'],
            ]);

            User::create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => Hash::make($payload['password']),
                'role' => UserRole::Distributor,
                'distributor_id' => $distributor->id,
                'is_active' => true,
            ]);

            return $distributor;
        });

        $this->notifyAdminOfNewRegistration($distributor);

        $user = $distributor->user;
        $code = $user->generateEmailVerificationCode();

        $this->sendVerificationCode($user, $code);

        $request->session()->put('verify_email_user_id', $user->id);

        return redirect()->route('register.verify-email');
    }

    private function sendVerificationCode(User $user, string $code): void
    {
        try {
            Mail::to($user->email)->send(new EmailVerificationCodeMail($user, $code));
        } catch (\Throwable $exception) {
            Log::error('email_verification_code.send.failed', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function ensurePublicRegistrationIsEnabled(): void
    {
        abort_unless(config('auth.allow_public_registration'), 404);
    }

    private function notifyAdminOfNewRegistration(Distributor $distributor): void
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
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
