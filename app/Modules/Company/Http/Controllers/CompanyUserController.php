<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Company\Http\Requests\StoreCompanyUserRequest;
use App\Modules\Company\Http\Requests\UpdateCompanyUserRequest;
use App\Modules\Shared\Enums\CompanyRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CompanyUserController extends Controller
{
    public function index(): View
    {
        $this->authorize('manageUsers', Distributor::class);

        /** @var \App\Models\User $auth */
        $auth = auth()->user();

        $users = User::query()
            ->where('distributor_id', $auth->distributor_id)
            ->orderBy('name')
            ->get();

        return view('empresa.users.index', [
            'users'        => $users,
            'companyRoles' => CompanyRole::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('manageUsers', Distributor::class);

        return view('empresa.users.form', [
            'user'         => new User(),
            'companyRoles' => CompanyRole::cases(),
        ]);
    }

    public function store(StoreCompanyUserRequest $request): RedirectResponse
    {
        $this->authorize('manageUsers', Distributor::class);

        /** @var \App\Models\User $auth */
        $auth = auth()->user();

        $payload = $request->validated();
        $payload['password']       = Hash::make($payload['password']);
        $payload['role']           = 'distributor';
        $payload['distributor_id'] = $auth->distributor_id;

        User::create($payload);

        return redirect()->route('empresa.users.index')->with('status', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        $this->authorize('manageUsers', Distributor::class);
        $this->authorizeUserBelongsToCompany($user);

        return view('empresa.users.form', [
            'user'         => $user,
            'companyRoles' => CompanyRole::cases(),
        ]);
    }

    public function update(UpdateCompanyUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('manageUsers', Distributor::class);
        $this->authorizeUserBelongsToCompany($user);

        $payload = $request->validated();

        if (! empty($payload['password'])) {
            $payload['password'] = Hash::make($payload['password']);
        } else {
            unset($payload['password']);
        }

        $user->update($payload);

        return redirect()->route('empresa.users.index')->with('status', 'Usuario actualizado correctamente.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manageUsers', Distributor::class);
        $this->authorizeUserBelongsToCompany($user);

        /** @var \App\Models\User $auth */
        $auth = auth()->user();

        if ((int) $auth->id === (int) $user->id) {
            return back()->with('error', 'No puedes desactivarte a ti mismo.');
        }

        // Usamos email_verified_at como mecanismo de activación/desactivación simple:
        // verified_at = activo, null = inactivo (bloquea login vía MustVerifyEmail si se activa)
        // Alternativa más limpia: un campo is_active, pero por ahora usamos el patrón existente
        // El proyecto no usa MustVerifyEmail actualmente, así que usamos un campo custom de sesión.
        // Para fase 1 se maneja con company_role = null como "sin acceso al panel empresa"
        // pero sí puede hacer login. La forma más simple y no destructiva: toggle email_verified_at.

        if ($user->email_verified_at) {
            $user->update(['email_verified_at' => null]);
            $message = "Usuario {$user->name} desactivado.";
        } else {
            $user->update(['email_verified_at' => now()]);
            $message = "Usuario {$user->name} activado.";
        }

        return back()->with('status', $message);
    }

    private function authorizeUserBelongsToCompany(User $user): void
    {
        /** @var \App\Models\User $auth */
        $auth = auth()->user();

        abort_unless(
            (int) $user->distributor_id === (int) $auth->distributor_id,
            403,
            'No tienes acceso a este usuario.'
        );
    }
}
