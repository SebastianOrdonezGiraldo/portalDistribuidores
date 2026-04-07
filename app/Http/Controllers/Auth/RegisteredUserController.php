<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\CompanyRole;
use App\Modules\Shared\Enums\DistributorStatus;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $this->ensurePublicRegistrationIsEnabled();

        return view('auth.register');
    }

    /**
     * Display the registration submitted view.
     */
    public function pending(): View
    {
        $this->ensurePublicRegistrationIsEnabled();

        return view('auth.register-pending');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensurePublicRegistrationIsEnabled();

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => ['required', 'string', 'max:120'],
            'nit' => ['required', 'string', 'max:40', 'unique:distributors,nit'],
            'city' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:180'],
            'phone' => ['required', 'string', 'max:40'],
        ]);

        DB::transaction(function () use ($payload): void {
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
                'company_role' => CompanyRole::AdminEmpresa,
                'distributor_id' => $distributor->id,
                'is_active' => true,
            ]);
        });

        return redirect()
            ->route('register.pending')
            ->with('status', 'Recibimos tu solicitud. Validaremos tus datos y activaremos tu acceso.');
    }

    private function ensurePublicRegistrationIsEnabled(): void
    {
        abort_unless(config('auth.allow_public_registration'), 404);
    }
}
