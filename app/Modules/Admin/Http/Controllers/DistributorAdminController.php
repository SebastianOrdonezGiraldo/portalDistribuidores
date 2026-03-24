<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Http\Requests\StoreDistributorRequest;
use App\Modules\Admin\Http\Requests\UpdateDistributorRequest;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Enums\DistributorStatus;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DistributorAdminController extends Controller
{
    private const RECENT_ORDERS_LIMIT = 5;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Distributor::class);

        $statusOptions = array_map(
            static fn (DistributorStatus $status) => $status->value,
            DistributorStatus::cases(),
        );
        $relationOptions = ['with_users', 'without_users', 'with_orders', 'without_orders'];
        $sortOptions = ['newest', 'oldest', 'name_asc', 'name_desc', 'users_desc', 'orders_desc'];
        $perPageOptions = [15, 30, 60];

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::in($statusOptions)],
            'relation' => ['nullable', 'string', Rule::in($relationOptions)],
            'sort' => ['nullable', 'string', Rule::in($sortOptions)],
            'per_page' => ['nullable', 'integer', Rule::in($perPageOptions)],
        ]);

        $filters = array_merge([
            'q' => null,
            'status' => null,
            'relation' => null,
            'sort' => 'newest',
            'per_page' => 15,
        ], $filters);

        $filteredQuery = Distributor::query()
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim((string) $filters['q']);

                $query->where('name', 'like', "%{$term}%");
            })
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['relation'] === 'with_users', fn ($query) => $query->has('users'))
            ->when($filters['relation'] === 'without_users', fn ($query) => $query->doesntHave('users'))
            ->when($filters['relation'] === 'with_orders', fn ($query) => $query->has('orders'))
            ->when($filters['relation'] === 'without_orders', fn ($query) => $query->doesntHave('orders'));

        $distributors = (clone $filteredQuery)
            ->withCount(['users', 'orders'])
            ->withSum('orders as orders_total_amount', 'total_amount')
            ->withMax('orders as latest_order_at', 'created_at')
            ->when($filters['sort'] === 'newest', fn ($query) => $query->latest())
            ->when($filters['sort'] === 'oldest', fn ($query) => $query->oldest())
            ->when($filters['sort'] === 'name_asc', fn ($query) => $query->orderBy('name'))
            ->when($filters['sort'] === 'name_desc', fn ($query) => $query->orderByDesc('name'))
            ->when($filters['sort'] === 'users_desc', fn ($query) => $query->orderByDesc('users_count')->orderBy('name'))
            ->when($filters['sort'] === 'orders_desc', fn ($query) => $query->orderByDesc('orders_count')->orderBy('name'))
            ->paginate((int) $filters['per_page'])
            ->withQueryString();

        $this->hydrateRecentOrders($distributors->getCollection());

        $metrics = [
            'total_distributors' => (clone $filteredQuery)->count(),
            'active_distributors' => (clone $filteredQuery)->where('status', DistributorStatus::Active->value)->count(),
            'inactive_distributors' => (clone $filteredQuery)->where('status', DistributorStatus::Suspended->value)->count(),
            'with_users' => (clone $filteredQuery)->has('users')->count(),
            'with_orders' => (clone $filteredQuery)->has('orders')->count(),
        ];

        $activeFiltersCount = collect([
            $filters['q'],
            $filters['status'],
            $filters['relation'],
            $filters['sort'] !== 'newest' ? $filters['sort'] : null,
        ])->filter(fn ($value) => filled($value))->count();

        return view('admin.distributors.index', [
            'distributors' => $distributors,
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'relationOptions' => $relationOptions,
            'sortOptions' => $sortOptions,
            'perPageOptions' => $perPageOptions,
            'metrics' => $metrics,
            'activeFiltersCount' => $activeFiltersCount,
            'recentOrdersLimit' => self::RECENT_ORDERS_LIMIT,
        ]);
    }

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
                    $status = $row->status instanceof \BackedEnum
                        ? $row->status->value
                        : (string) $row->status;

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

        return view('admin.distributors.form', ['distributor' => new Distributor()]);
    }

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
            'distributor' => $distributor->loadCount(['users', 'orders']),
        ]);
    }

    public function update(UpdateDistributorRequest $request, Distributor $distributor): RedirectResponse
    {
        $this->authorize('update', $distributor);
        $distributor->update($request->validated());

        return $this->redirectAfterSave($request, $distributor, false);
    }

    public function destroy(Distributor $distributor): RedirectResponse
    {
        $this->authorize('delete', $distributor);

        $usersCount = $distributor->users()->count();
        if ($usersCount > 0) {
            $label = $usersCount === 1 ? 'usuario asociado' : 'usuarios asociados';

            return back()->with('error', "No se puede eliminar el distribuidor porque tiene {$usersCount} {$label}. Desactívalo o reasigna esos usuarios primero.");
        }

        $ordersCount = $distributor->orders()->count();
        if ($ordersCount > 0) {
            $label = $ordersCount === 1 ? 'pedido asociado' : 'pedidos asociados';

            return back()->with('error', "No se puede eliminar el distribuidor porque tiene {$ordersCount} {$label}. Conserva el historial comercial y desactívalo.");
        }

        $distributor->delete();

        return redirect()->route('admin.distributors.index')->with('status', 'Distribuidor eliminado.');
    }

    public function setStatus(Request $request, Distributor $distributor): RedirectResponse
    {
        $this->authorize('update', $distributor);

        $payload = $request->validate([
            'status' => ['required', 'string', Rule::in($this->statusValues())],
        ]);

        $distributor->update(['status' => $payload['status']]);

        return back()->with(
            'status',
            $payload['status'] === DistributorStatus::Active->value
                ? 'Distribuidor activado.'
                : 'Distribuidor suspendido.'
        );
    }

    private function statusValues(): array
    {
        return array_map(
            static fn (DistributorStatus $status) => $status->value,
            DistributorStatus::cases(),
        );
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
}
