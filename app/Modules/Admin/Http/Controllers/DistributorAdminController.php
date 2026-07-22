<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Http\Requests\StoreDistributorRequest;
use App\Modules\Admin\Http\Requests\UpdateDistributorRequest;
use App\Modules\Admin\Http\Requests\UpdateDistributorTierRequest;
use App\Modules\AuthAccess\Mail\DistributorAccountActivatedMail;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\AuthAccess\Services\DistributorTierService;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\DistributorStatus;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DistributorAdminController extends Controller
{
    private const RECENT_ORDERS_LIMIT = 5;

    /**
     * Listar distribuidores.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @queryParam q string Busqueda por nombre. Example: Distribuciones
     * @queryParam status string Estado exacto. Example: active
     * @queryParam status_group string Grupo de estado. Example: non_active
     * @queryParam relation string with_users, without_users, with_orders, without_orders. Example: with_users
     * @queryParam sort string Orden. Example: newest
     * @queryParam per_page integer Tamano de pagina. Example: 15
     *
     * @response 200 {"content":"Vista HTML de distribuidores"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Filtros invalidos"}
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Distributor::class);

        $statusOptions = $this->statusLabels();
        $relationOptions = ['with_users', 'without_users', 'with_orders', 'without_orders'];
        $statusGroupOptions = ['non_active'];
        $sortOptions = ['newest', 'oldest', 'name_asc', 'name_desc', 'users_desc', 'orders_desc'];
        $perPageOptions = [15, 30, 60];
        $tierOptions = $this->tierLabels();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::in(array_keys($statusOptions))],
            'status_group' => ['nullable', 'string', Rule::in($statusGroupOptions)],
            'relation' => ['nullable', 'string', Rule::in($relationOptions)],
            'tier' => ['nullable', 'string', Rule::enum(DistributorTier::class)],
            'sort' => ['nullable', 'string', Rule::in($sortOptions)],
            'per_page' => ['nullable', 'integer', Rule::in($perPageOptions)],
        ]);

        $filters = array_merge([
            'q' => null,
            'status' => null,
            'status_group' => null,
            'relation' => null,
            'tier' => null,
            'sort' => 'newest',
            'per_page' => 15,
        ], $filters);

        $filteredQuery = Distributor::query()
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim((string) $filters['q']);

                $query->where('name', 'like', "%{$term}%");
            })
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['status_group'] === 'non_active', fn ($query) => $query->where('status', '!=', DistributorStatus::Active->value))
            ->when($filters['relation'] === 'with_users', fn ($query) => $query->has('user'))
            ->when($filters['relation'] === 'without_users', fn ($query) => $query->doesntHave('user'))
            ->when($filters['relation'] === 'with_orders', fn ($query) => $query->has('orders'))
            ->when($filters['relation'] === 'without_orders', fn ($query) => $query->doesntHave('orders'))
            ->when(! empty($filters['tier']), fn ($query) => $query->where('tier', $filters['tier']));

        $distributors = (clone $filteredQuery)
            ->withCount(['user', 'orders'])
            ->withSum('orders as orders_total_amount', 'total_amount')
            ->withMax('orders as latest_order_at', 'created_at')
            ->when($filters['sort'] === 'newest', fn ($query) => $query->latest())
            ->when($filters['sort'] === 'oldest', fn ($query) => $query->oldest())
            ->when($filters['sort'] === 'name_asc', fn ($query) => $query->orderBy('name'))
            ->when($filters['sort'] === 'name_desc', fn ($query) => $query->orderByDesc('name'))
            ->when($filters['sort'] === 'users_desc', fn ($query) => $query->orderByDesc('user_count')->orderBy('name'))
            ->when($filters['sort'] === 'orders_desc', fn ($query) => $query->orderByDesc('orders_count')->orderBy('name'))
            ->paginate((int) $filters['per_page'])
            ->withQueryString();

        /** @var EloquentCollection<int, Distributor> $paginatedItems */
        $paginatedItems = $distributors->getCollection();
        $this->hydrateRecentOrders($paginatedItems);

        $metrics = [
            'total_distributors' => (clone $filteredQuery)->count(),
            'active_distributors' => (clone $filteredQuery)->where('status', DistributorStatus::Active->value)->count(),
            'inactive_distributors' => (clone $filteredQuery)->where('status', '!=', DistributorStatus::Active->value)->count(),
            'with_users' => (clone $filteredQuery)->has('user')->count(),
            'with_orders' => (clone $filteredQuery)->has('orders')->count(),
            'silver_distributors' => (clone $filteredQuery)->where('tier', DistributorTier::Silver->value)->count(),
            'gold_distributors' => (clone $filteredQuery)->where('tier', DistributorTier::Gold->value)->count(),
        ];

        $activeFiltersCount = collect([
            $filters['q'],
            $filters['status'],
            $filters['status_group'],
            $filters['relation'],
            $filters['tier'],
            $filters['sort'] !== 'newest' ? $filters['sort'] : null,
        ])->filter(fn ($value) => filled($value))->count();

        return view('admin.distributors.index', [
            'distributors' => $distributors,
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'relationOptions' => $relationOptions,
            'tierOptions' => $tierOptions,
            'sortOptions' => $sortOptions,
            'perPageOptions' => $perPageOptions,
            'metrics' => $metrics,
            'activeFiltersCount' => $activeFiltersCount,
            'recentOrdersLimit' => self::RECENT_ORDERS_LIMIT,
        ]);
    }

    /**
     * @param  EloquentCollection<int, Distributor>  $distributors
     */
    private function hydrateRecentOrders(EloquentCollection $distributors): void
    {
        if ($distributors->isEmpty()) {
            return;
        }

        $distributorIds = $distributors->pluck('id')->all();

        $recentOrderIds = DB::query()
            ->fromSub(
                Order::query()
                    ->select(['id', 'distributor_id'])
                    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY distributor_id ORDER BY created_at DESC, id DESC) as row_num')
                    ->whereIn('distributor_id', $distributorIds),
                'ranked_orders'
            )
            ->where('row_num', '<=', self::RECENT_ORDERS_LIMIT)
            ->pluck('id');

        $recentOrders = Order::query()
            ->with(['user', 'items'])
            ->withCount('items')
            ->whereIn('id', $recentOrderIds)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('distributor_id');

        $statusSummary = Order::query()
            ->selectRaw('distributor_id, status, COUNT(*) as total')
            ->whereIn('distributor_id', $distributorIds)
            ->groupBy('distributor_id', 'status')
            ->get()
            ->groupBy('distributor_id')
            ->map(function (Collection $rows): array {
                $counts = $rows->mapWithKeys(function ($row): array {
                    $status = $row->status->value;

                    return [$status => (int) $row->total];
                });

                return collect(OrderStatus::cases())
                    ->mapWithKeys(fn (OrderStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)])
                    ->all();
            });

        $distributors->each(function (Distributor $distributor) use ($recentOrders, $statusSummary): void {
            $distributor->setRelation('recent_orders', $recentOrders->get($distributor->id, collect()));
            $distributor->setAttribute('order_status_summary', $statusSummary->get($distributor->id, []));
        });
    }

    public function create(): View
    {
        $this->authorize('create', Distributor::class);

        return view('admin.distributors.form', [
            'distributor' => new Distributor,
            'statusOptions' => $this->statusLabels(),
            'tierOptions' => $this->tierLabels(),
        ]);
    }

    /**
     * Crear distribuidor.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @bodyParam name string required Nombre. Example: Distribuciones Medicas SAS
     * @bodyParam status string required Estado. Example: active
     *
     * @response 302 {"redirect":"admin.distributors.index|admin.distributors.edit|admin.distributors.create"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Datos invalidos"}
     */
    public function store(StoreDistributorRequest $request): RedirectResponse
    {
        $this->authorize('create', Distributor::class);
        $distributor = Distributor::create($request->validated());

        return $this->redirectAfterSave($request, $distributor, true);
    }

    public function edit(Distributor $distributor): View
    {
        $this->authorize('update', $distributor);

        return view('admin.distributors.form', [
            'distributor' => $distributor->loadCount(['user', 'orders'])->load('tierChangedBy'),
            'statusOptions' => $this->statusLabels(),
            'tierOptions' => $this->tierLabels(),
        ]);
    }

    /**
     * Actualizar distribuidor.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam distributor integer required ID de distribuidor. Example: 7
     *
     * @bodyParam name string required Nombre. Example: Distribuciones Medicas SAS
     * @bodyParam status string required Estado. Example: active
     *
     * @response 302 {"redirect":"admin.distributors.index|admin.distributors.edit|admin.distributors.create"}
     * @response 403 {"message":"No autorizado"}
     * @response 404 {"message":"Distribuidor no encontrado"}
     * @response 422 {"message":"Datos invalidos"}
     */
    public function update(UpdateDistributorRequest $request, Distributor $distributor): RedirectResponse
    {
        $this->authorize('update', $distributor);
        $distributor->update($request->validated());

        return $this->redirectAfterSave($request, $distributor, false);
    }

    /**
     * Eliminar distribuidor.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam distributor integer required ID de distribuidor. Example: 7
     *
     * @response 302 {"redirect":"admin.distributors.index|back"}
     * @response 403 {"message":"No autorizado"}
     * @response 404 {"message":"Distribuidor no encontrado"}
     * @response 422 {"message":"No se puede eliminar por usuario o pedidos asociados"}
     */
    public function destroy(Distributor $distributor): RedirectResponse
    {
        $this->authorize('delete', $distributor);

        if ($distributor->user()->exists()) {
            return back()->with('error', 'No se puede eliminar el distribuidor porque tiene un usuario asociado. Desactívalo o elimina ese usuario primero.');
        }

        $ordersCount = $distributor->orders()->count();

        if ($ordersCount > 0) {
            $label = $ordersCount === 1 ? 'pedido asociado' : 'pedidos asociados';

            return back()->with('error', "No se puede eliminar el distribuidor porque tiene {$ordersCount} {$label}. Conserva el historial comercial y desactívalo.");
        }

        $distributor->delete();

        return redirect()->route('admin.distributors.index')->with('status', 'Distribuidor eliminado.');
    }

    /**
     * Cambiar estado de distribuidor.
     *
     * Al activar, intenta notificar al usuario asociado.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam distributor integer required ID de distribuidor. Example: 7
     *
     * @bodyParam status string required Nuevo estado. Example: active
     *
     * @response 302 {"redirect":"back"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Estado invalido"}
     */
    public function setStatus(Request $request, Distributor $distributor): RedirectResponse
    {
        $this->authorize('update', $distributor);

        $payload = $request->validate([
            'status' => ['required', 'string', Rule::in($this->statusValues())],
        ]);

        $previousStatus = $distributor->status;
        $distributor->update(['status' => $payload['status']]);

        $status = DistributorStatus::from($payload['status']);

        if ($previousStatus !== DistributorStatus::Active && $status === DistributorStatus::Active) {
            $this->notifyUsersAccountActivated($distributor->fresh('user') ?? $distributor);
        }

        $message = match ($status) {
            DistributorStatus::Active => 'Distribuidor activado.',
            DistributorStatus::PendingReview => 'Distribuidor marcado como pendiente de revisión.',
            DistributorStatus::Rejected => 'Distribuidor rechazado.',
            DistributorStatus::Suspended => 'Distribuidor suspendido.',
        };

        return back()->with('status', $message);
    }

    /**
     * Cambiar nivel comercial (tier) de distribuidor.
     *
     * Asignación manual por un administrador. No existe asignación automática.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam distributor integer required ID de distribuidor. Example: 7
     *
     * @bodyParam tier string required Nuevo nivel comercial. Example: oro
     *
     * @response 302 {"redirect":"back"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Nivel invalido"}
     */
    public function updateTier(
        UpdateDistributorTierRequest $request,
        Distributor $distributor,
        DistributorTierService $tierService,
    ): RedirectResponse {
        $this->authorize('updateTier', $distributor);

        /** @var User $admin */
        $admin = $request->user();
        $tier = DistributorTier::from($request->validated('tier'));
        $previousTier = $distributor->tier;

        $tierService->changeTier($distributor, $tier, $admin);

        if ($previousTier === $tier) {
            return back()->with('status', "El distribuidor ya estaba en nivel {$tier->label()}.");
        }

        return back()->with('status', "Nivel comercial actualizado a {$tier->label()}.");
    }

    private function statusValues(): array
    {
        return array_keys($this->statusLabels());
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return collect(DistributorStatus::cases())
            ->mapWithKeys(fn (DistributorStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function tierLabels(): array
    {
        return collect(DistributorTier::cases())
            ->mapWithKeys(fn (DistributorTier $tier) => [$tier->value => $tier->label()])
            ->all();
    }

    private function redirectAfterSave(Request $request, Distributor $distributor, bool $created): RedirectResponse
    {
        $status = $created ? 'Distribuidor creado.' : 'Distribuidor actualizado.';
        $afterSave = (string) $request->input('after_save', 'index');

        if ($afterSave === 'stay') {
            return redirect()->route('admin.distributors.edit', $distributor)->with('status', $status);
        }

        if ($afterSave === 'new') {
            return redirect()->route('admin.distributors.create')->with('status', $status.' Puedes crear otro.');
        }

        return redirect()->route('admin.distributors.index')->with('status', $status);
    }

    private function notifyUsersAccountActivated(Distributor $distributor): void
    {
        $user = $distributor->user()->where('is_active', true)->first(['id', 'name', 'email', 'distributor_id']);

        if (! $user || ! filter_var((string) $user->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new DistributorAccountActivatedMail($user, $distributor));
        } catch (\Throwable $exception) {
            Log::error('distributor.activation_email.failed', [
                'distributor_id' => $distributor->id,
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
