<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Http\Requests\UpdateAdminOrderRequest;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Actions\UpdateOrderAction;
use App\Modules\Orders\Jobs\GenerateOrderPdfJob;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderPdfGenerator;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $statusOptions = array_map(fn (OrderStatus $status) => $status->value, OrderStatus::cases());
        $sortOptions = ['newest', 'oldest', 'amount_desc', 'amount_asc'];
        $perPageOptions = [20, 50, 100];

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::in($statusOptions)],
            'distributor_id' => ['nullable', 'integer', 'exists:distributors,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string', Rule::in($sortOptions)],
            'per_page' => ['nullable', 'integer', Rule::in($perPageOptions)],
        ]);

        $filters = array_merge([
            'q' => null,
            'status' => null,
            'distributor_id' => null,
            'date_from' => null,
            'date_to' => null,
            'sort' => 'newest',
            'per_page' => 20,
        ], $filters);

        $filteredQuery = Order::query()
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim((string) $filters['q']);

                $query->where(function ($subQuery) use ($term) {
                    $subQuery
                        ->where('oc_number', 'like', "%{$term}%")
                        ->orWhere('company_name', 'like', "%{$term}%")
                        ->orWhere('contact_name', 'like', "%{$term}%")
                        ->orWhere('contact_email', 'like', "%{$term}%");
                });
            })
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['distributor_id']), fn ($query) => $query->where('distributor_id', $filters['distributor_id']))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']));

        $orders = (clone $filteredQuery)
            ->with('distributor', 'user')
            ->when($filters['sort'] === 'newest', fn ($query) => $query->latest())
            ->when($filters['sort'] === 'oldest', fn ($query) => $query->oldest())
            ->when($filters['sort'] === 'amount_desc', fn ($query) => $query->orderByDesc('total_amount')->orderByDesc('created_at'))
            ->when($filters['sort'] === 'amount_asc', fn ($query) => $query->orderBy('total_amount')->orderByDesc('created_at'))
            ->paginate((int) $filters['per_page'])
            ->withQueryString();

        $statusCounts = (clone $filteredQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusSummary = collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $status) => [$status->value => (int) ($statusCounts[$status->value] ?? 0)]);

        $metrics = [
            'total_orders' => (clone $filteredQuery)->count(),
            'total_amount' => (float) (clone $filteredQuery)->sum('total_amount'),
            'pending_pdf' => (clone $filteredQuery)->whereNull('pdf_path')->count(),
            'in_progress' => (int) (($statusSummary[OrderStatus::Sold->value] ?? 0) + ($statusSummary[OrderStatus::Dispatched->value] ?? 0)),
        ];

        $activeFiltersCount = collect([
            $filters['q'],
            $filters['status'],
            $filters['distributor_id'],
            $filters['date_from'],
            $filters['date_to'],
        ])->filter(fn ($value) => filled($value))->count();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'sortOptions' => $sortOptions,
            'perPageOptions' => $perPageOptions,
            'distributors' => Distributor::query()->orderBy('name')->get(['id', 'name']),
            'statusSummary' => $statusSummary,
            'metrics' => $metrics,
            'activeFiltersCount' => $activeFiltersCount,
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items', 'distributor', 'user', 'statusHistory.actor');

        $items = $order->items;

        $totals = [
            'items_count' => $items->count(),
            'units_total' => (float) $items->sum('qty'),
            'subtotals_total' => (float) $items->sum('subtotal'),
            'average_unit_price' => $items->isNotEmpty()
                ? (float) $items->avg('price_each')
                : 0.0,
        ];

        $hasFinancialGap = abs($totals['subtotals_total'] - (float) $order->total_amount) > 0.01;
        $nextStatuses = collect($order->status->nextAllowedStatuses())
            ->map(fn (OrderStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'requires_note' => $status->requiresTransitionNote(),
                'cta' => $this->transitionCtaLabel($status),
            ])
            ->values();

        $timeline = $this->buildOrderTimeline($order);
        $recommendedAction = $this->buildRecommendedAction($order->status, $nextStatuses);
        $secondaryActions = collect([
            [
                'label' => 'Editar pedido',
                'href' => route('admin.orders.edit', $order),
                'visible' => $order->status->canBeEditedByAdmin(),
            ],
            [
                'label' => 'Enviar correo',
                'href' => $order->contact_email ? 'mailto:'.$order->contact_email : null,
                'visible' => filled($order->contact_email),
            ],
        ])->filter(fn (array $action) => ! empty($action['visible']) && filled($action['href']))
            ->values();

        $dangerActions = collect([
            [
                'label' => 'Eliminar pedido',
                'action' => route('admin.orders.destroy', $order),
                'confirm' => "¿Eliminar {$order->oc_number}? Esta acción no se puede deshacer.",
            ],
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'totals' => $totals,
            'hasFinancialGap' => $hasFinancialGap,
            'nextStatuses' => $nextStatuses,
            'timeline' => $timeline,
            'recommendedAction' => $recommendedAction,
            'secondaryActions' => $secondaryActions,
            'dangerActions' => $dangerActions,
        ]);
    }

    private function buildOrderTimeline(Order $order): Collection
    {
        $timeline = $order->statusHistory
            ->map(function ($event): array {
                $status = OrderStatus::tryFrom((string) $event->to_status);
                $actor = $event->actor?->name ? ' por '.$event->actor->name : ' por sistema';

                return [
                    'title' => 'Estado: '.($status?->label() ?? strtoupper((string) $event->to_status)),
                    'description' => $event->note ?: 'Cambio registrado'.$actor.'.',
                    'status' => $event->to_status,
                    'at' => $event->created_at,
                ];
            })
            ->values();

        if ($timeline->isEmpty()) {
            $timeline = collect([
                [
                    'title' => 'Pedido creado',
                    'description' => 'Registro inicial en el portal.',
                    'status' => $order->status,
                    'at' => $order->created_at,
                ],
            ]);
        }

        return $timeline->sortByDesc('at')->values();
    }

    private function buildRecommendedAction(OrderStatus $currentStatus, Collection $nextStatuses): ?array
    {
        $recommendedTransition = match ($currentStatus) {
            OrderStatus::PendingApproval => OrderStatus::Submitted->value,
            OrderStatus::Submitted => OrderStatus::Sold->value,
            OrderStatus::Sold => OrderStatus::Dispatched->value,
            OrderStatus::Dispatched => OrderStatus::Delivered->value,
            OrderStatus::Rejected => OrderStatus::PendingApproval->value,
            default => null,
        };

        if ($recommendedTransition === null) {
            return null;
        }

        $target = $nextStatuses->firstWhere('value', $recommendedTransition);
        if (! is_array($target)) {
            return null;
        }

        return $target;
    }

    private function transitionCtaLabel(OrderStatus $targetStatus): string
    {
        return match ($targetStatus) {
            OrderStatus::Submitted => 'Registrar pedido',
            OrderStatus::Sold => 'Marcar como vendido',
            OrderStatus::Dispatched => 'Marcar como despachado',
            OrderStatus::Delivered => 'Marcar como entregado',
            OrderStatus::PendingApproval => 'Reingresar a revisión',
            default => 'Actualizar estado',
        };
    }

    public function edit(Order $order): View|RedirectResponse
    {
        $this->authorize('update', $order);

        if (! $order->status->canBeEditedByAdmin()) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->withErrors('Este pedido no puede editarse en su estado actual.');
        }

        $order->load('items');

        return view('admin.orders.edit', [
            'order' => $order,
            'departments' => config('locations.colombia_departments', []),
            'catalogOptions' => $this->catalogOptions(),
        ]);
    }

    public function update(
        UpdateAdminOrderRequest $request,
        Order $order,
        UpdateOrderAction $updateOrderAction,
    ): RedirectResponse {
        $this->authorize('update', $order);

        if (! $order->status->canBeEditedByAdmin()) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->withErrors('Este pedido no puede editarse en su estado actual.');
        }

        try {
            $order = $updateOrderAction->execute($order, $request->validated(), $request->user(), true);
        } catch (DomainException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->getMessage());
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', "Pedido {$order->oc_number} actualizado correctamente.");
    }

    /**
     * @return array<int, array{ref:string,label:string,price:float}>
     */
    private function catalogOptions(): array
    {
        $products = Product::query()
            ->active()
            ->withCount(['variants as active_variants_count' => fn ($query) => $query->active()])
            ->with([
                'variants' => fn ($query) => $query
                    ->active()
                    ->with('attributeValue.attribute')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price']);

        return $products
            ->flatMap(function (Product $product) {
                $baseLabel = trim("{$product->sku} · {$product->name}");

                if ((int) ($product->active_variants_count ?? 0) === 0) {
                    return [[
                        'ref' => 'p:'.$product->id,
                        'label' => $baseLabel,
                        'price' => (float) $product->price,
                    ]];
                }

                return $product->variants->map(function (ProductVariant $variant) use ($baseLabel): array {
                    $attributeName = $variant->attributeValue?->attribute?->name ?? 'Variante';
                    $attributeValue = $variant->attributeValue?->value ?? ('#'.$variant->id);

                    return [
                        'ref' => 'v:'.$variant->id,
                        'label' => "{$baseLabel} · {$attributeName}: {$attributeValue}",
                        'price' => (float) $variant->price,
                    ];
                });
            })
            ->values()
            ->all();
    }

    public function updateStatus(
        Request $request,
        Order $order,
        OrderStatusTransitionService $transitionService,
    ): RedirectResponse {
        $this->authorize('update', $order);

        $payload = $request->validate([
            'status' => ['required', 'string', Rule::in(array_map(fn (OrderStatus $status) => $status->value, OrderStatus::cases()))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $targetStatus = OrderStatus::from($payload['status']);

        /** @var \App\Models\User $actor */
        $actor = $request->user();

        try {
            $transitionService->transition(
                $order,
                $targetStatus,
                $actor,
                $payload['note'] ?? null
            );
        } catch (DomainException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->getMessage());
        }

        return back()->with('status', "Pedido {$order->oc_number} actualizado a {$targetStatus->label()}.");
    }

    public function downloadPdf(Order $order, OrderPdfGenerator $pdfGenerator): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $order);

        $path = $pdfGenerator->generate($order);

        if ($order->pdf_path !== $path) {
            $order->update(['pdf_path' => $path]);
        }

        $disk = Storage::disk(OrderPdfGenerator::diskName());

        if (! $disk->exists($path)) {
            GenerateOrderPdfJob::dispatch($order->id);

            return back()->withErrors('PDF en generación. Intenta nuevamente en unos segundos.');
        }

        return $disk->download($path, $order->oc_number.'.pdf');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);

        $pathsToDelete = collect([
            $order->pdf_path,
            'orders/'.$order->oc_number.'.pdf',
        ])->filter()->unique()->values();

        $orderNumber = $order->oc_number;

        try {
            $order->delete();
        } catch (QueryException $exception) {
            $sqlState = (string) ($exception->errorInfo[0] ?? '');

            if (in_array($sqlState, ['23000', '23001', '23503'], true)) {
                return back()->with('error', 'No se pudo eliminar el pedido porque está relacionado con otros registros.');
            }

            throw $exception;
        }

        foreach ($pathsToDelete as $path) {
            foreach (collect([OrderPdfGenerator::diskName(), 'public'])->unique() as $diskName) {
                if (Storage::disk($diskName)->exists($path)) {
                    Storage::disk($diskName)->delete($path);
                }
            }
        }

        return redirect()->route('admin.orders.index')->with('status', "Pedido {$orderNumber} eliminado.");
    }
}
