<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Http\Requests\StoreUserRequest;
use App\Modules\Admin\Http\Requests\UpdateUserRequest;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    /**
     * Listar usuarios.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @queryParam q string Busqueda por nombre o email. Example: admin
     * @queryParam role string Rol. Example: admin
     * @queryParam distributor_link string linked o unlinked. Example: linked
     * @queryParam distributor_id integer ID de distribuidor. Example: 7
     * @queryParam sort string Orden. Example: newest
     * @queryParam per_page integer Tamano de pagina. Example: 15
     *
     * @response 200 {"content":"Vista HTML de usuarios"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Filtros invalidos"}
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $roleOptions = array_map(
            static fn (UserRole $role) => $role->value,
            UserRole::cases()
        );
        $distributorLinkOptions = ['linked', 'unlinked'];
        $sortOptions = ['newest', 'oldest', 'name_asc', 'name_desc', 'email_asc', 'email_desc'];
        $perPageOptions = [15, 30, 60];

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', 'string', Rule::in($roleOptions)],
            'distributor_link' => ['nullable', 'string', Rule::in($distributorLinkOptions)],
            'distributor_id' => ['nullable', 'integer', 'exists:distributors,id'],
            'sort' => ['nullable', 'string', Rule::in($sortOptions)],
            'per_page' => ['nullable', 'integer', Rule::in($perPageOptions)],
        ]);

        $filters = array_merge([
            'q' => null,
            'role' => null,
            'distributor_link' => null,
            'distributor_id' => null,
            'sort' => 'newest',
            'per_page' => 15,
        ], $filters);

        $filteredQuery = User::query()
            ->with('distributor')
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim((string) $filters['q']);

                $query->where(function ($inner) use ($term) {
                    $inner
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when(! empty($filters['role']), fn ($query) => $query->where('role', $filters['role']))
            ->when(! empty($filters['distributor_id']), fn ($query) => $query->where('distributor_id', $filters['distributor_id']))
            ->when($filters['distributor_link'] === 'linked', fn ($query) => $query->whereNotNull('distributor_id'))
            ->when($filters['distributor_link'] === 'unlinked', fn ($query) => $query->whereNull('distributor_id'));

        $users = (clone $filteredQuery)
            ->when($filters['sort'] === 'newest', fn ($query) => $query->latest())
            ->when($filters['sort'] === 'oldest', fn ($query) => $query->oldest())
            ->when($filters['sort'] === 'name_asc', fn ($query) => $query->orderBy('name'))
            ->when($filters['sort'] === 'name_desc', fn ($query) => $query->orderByDesc('name'))
            ->when($filters['sort'] === 'email_asc', fn ($query) => $query->orderBy('email'))
            ->when($filters['sort'] === 'email_desc', fn ($query) => $query->orderByDesc('email'))
            ->paginate((int) $filters['per_page'])
            ->withQueryString();

        $metrics = [
            'total_users' => (clone $filteredQuery)->count(),
            'admin_users' => (clone $filteredQuery)->where('role', UserRole::Admin->value)->count(),
            'distributor_users' => (clone $filteredQuery)->where('role', UserRole::Distributor->value)->count(),
            'linked_distributor' => (clone $filteredQuery)->whereNotNull('distributor_id')->count(),
            'verified_users' => (clone $filteredQuery)->whereNotNull('email_verified_at')->count(),
        ];

        $activeFiltersCount = collect([
            $filters['q'],
            $filters['role'],
            $filters['distributor_link'],
            $filters['distributor_id'],
            $filters['sort'] !== 'newest' ? $filters['sort'] : null,
        ])->filter(fn ($value) => filled($value))->count();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'roleOptions' => $roleOptions,
            'distributorLinkOptions' => $distributorLinkOptions,
            'sortOptions' => $sortOptions,
            'perPageOptions' => $perPageOptions,
            'metrics' => $metrics,
            'activeFiltersCount' => $activeFiltersCount,
            'distributors' => Distributor::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.form', [
            'user' => new User,
            'distributors' => Distributor::query()->where('status', 'active')->orderBy('name')->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * Crear usuario.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @bodyParam name string required Nombre. Example: Admin Import
     * @bodyParam email string required Correo unico. Example: admin@example.com
     * @bodyParam password string required Contrasena minima de 8 caracteres. Example: secret123
     * @bodyParam role string required Rol. Example: distributor
     * @bodyParam distributor_id integer ID requerido si rol es distributor. Example: 7
     *
     * @response 302 {"redirect":"admin.users.index|admin.users.edit|admin.users.create"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Datos invalidos"}
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $payload = $request->validated();
        $payload['password'] = Hash::make($payload['password']);

        if (($payload['role'] ?? null) === UserRole::Admin->value) {
            $payload['distributor_id'] = null;
        }

        $user = User::create($payload);

        return $this->redirectAfterSave($request, $user, true);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.form', [
            'user' => $user->loadCount('orders')->load('distributor'),
            'distributors' => Distributor::query()
                ->where('status', 'active')
                ->orWhere('id', $user->distributor_id)
                ->orderBy('name')
                ->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * Actualizar usuario.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam user integer required ID de usuario. Example: 12
     *
     * @bodyParam name string required Nombre. Example: Admin Import
     * @bodyParam email string required Correo unico. Example: admin@example.com
     * @bodyParam password string Nueva contrasena opcional. Example: secret123
     * @bodyParam role string required Rol. Example: distributor
     * @bodyParam distributor_id integer ID requerido si rol es distributor. Example: 7
     * @bodyParam distributor_status string Estado del distribuidor asociado. Example: active
     *
     * @response 302 {"redirect":"admin.users.index|admin.users.edit|admin.users.create"}
     * @response 403 {"message":"No autorizado"}
     * @response 404 {"message":"Usuario no encontrado"}
     * @response 422 {"message":"Datos invalidos"}
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $payload = $request->validated();

        if (($payload['role'] ?? null) === UserRole::Admin->value) {
            $payload['distributor_id'] = null;
        }

        if (! empty($payload['password'])) {
            $payload['password'] = Hash::make($payload['password']);
        } else {
            unset($payload['password']);
        }

        $distributorStatus = $payload['distributor_status'] ?? null;
        unset($payload['distributor_status']);

        $user->update($payload);

        if ($distributorStatus !== null && $user->distributor !== null) {
            $user->distributor->update(['status' => $distributorStatus]);
        }

        return $this->redirectAfterSave($request, $user, false);
    }

    /**
     * Eliminar usuario.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam user integer required ID de usuario. Example: 12
     *
     * @response 302 {"redirect":"admin.users.index|back"}
     * @response 403 {"message":"No autorizado"}
     * @response 404 {"message":"Usuario no encontrado"}
     * @response 422 {"message":"No puedes eliminar tu propio usuario administrador."}
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ((int) auth()->id() === (int) $user->id) {
            return back()->with('error', 'No puedes eliminar tu propio usuario administrador.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Usuario eliminado.');
    }

    private function redirectAfterSave(Request $request, User $user, bool $created): RedirectResponse
    {
        $status = $created ? 'Usuario creado.' : 'Usuario actualizado.';
        $afterSave = (string) $request->input('after_save', 'index');

        if ($afterSave === 'stay') {
            return redirect()->route('admin.users.edit', $user)->with('status', $status);
        }

        if ($afterSave === 'new') {
            return redirect()->route('admin.users.create')->with('status', $status.' Puedes crear otro.');
        }

        return redirect()->route('admin.users.index')->with('status', $status);
    }
}
