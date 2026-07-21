<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Company\Http\Requests\UpdateCompanyOrderRequest;
use App\Modules\Orders\Actions\UpdateOrderAction;
use App\Modules\Orders\Jobs\GenerateOrderPdfJob;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Orders\Services\OrderPdfGenerator;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Orders\Support\OrderLineVat;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyOrderController extends Controller
{
    /**
     * Listar pedidos de empresa.
     *
     * @group Empresa
     *
     * @authenticated
     *
     * @queryParam q string Busqueda por OC, empresa o contacto. Example: OC-2026
     * @queryParam status string Estado exacto del pedido. Example: submitted
     * @queryParam group string Agrupacion visual: all, active, delivered o negative. Example: active
     * @queryParam date_from date Fecha inicial de creacion. Example: 2026-07-01
     * @queryParam date_to date Fecha final de creacion. Example: 2026-07-31
     *
     * @response 200 {"content":"Vista HTML de pedidos"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Filtros invalidos"}
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        /** @var User $user */
        $user = auth()->user();

        $statusOptions = array_map(fn (OrderStatus $s) => $s->value, OrderStatus::cases());
        $statusGroups = [
            'all' => OrderStatus::cases(),
            'active' => [
                OrderStatus::Draft,
                OrderStatus::PendingApproval,
                OrderStatus::Submitted,
                OrderStatus::Sending,
                OrderStatus::Sold,
                OrderStatus::Sent,
                OrderStatus::Dispatched,
            ],
            'delivered' => [OrderStatus::Delivered],
            'negative' => [OrderStatus::Cancelled, OrderStatus::Rejected, OrderStatus::Failed],
        ];

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::in($statusOptions)],
            'group' => ['nullable', 'string', Rule::in(array_keys($statusGroups))],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::when($request->filled('date_from'), ['after_or_equal:date_from']),
            ],
        ]);

        $filters = array_merge([
            'q' => null,
            'status' => null,
            'group' => 'all',
            'date_from' => null,
            'date_to' => null,
        ], $filters);

        $scopeQuery = Order::query()
            ->where('distributor_id', $user->distributor_id)
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim((string) $filters['q']);
                $query->where(function ($sub) use ($term) {
                    $sub->where('oc_number', 'like', "%{$term}%")
                        ->orWhere('company_name', 'like', "%{$term}%")
                        ->orWhere('contact_name', 'like', "%{$term}%");
                });
            })
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']));

        $baseQuery = (clone $scopeQuery)
            ->when(
                ! empty($filters['status']),
                fn ($query) => $query->where('status', $filters['status']),
                function ($query) use ($filters, $statusGroups): void {
                    if ($filters['group'] !== 'all') {
                        $query->whereIn('status', array_map(
                            fn (OrderStatus $status) => $status->value,
                            $statusGroups[$filters['group']],
                        ));
                    }
                },
            );

        $orders = (clone $baseQuery)
            ->with(['user', 'statusHistory.actor'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statusCounts = (clone $scopeQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusSummary = collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $s) => [$s->value => (int) ($statusCounts[$s->value] ?? 0)]);

        $groupSummary = collect($statusGroups)
            ->map(fn (array $statuses) => collect($statuses)->sum(
                fn (OrderStatus $status) => (int) ($statusSummary[$status->value] ?? 0),
            ));

        $metrics = [
            'total' => (clone $baseQuery)->count(),
            'amount' => (float) (clone $baseQuery)->sum('total_amount'),
        ];

        return view('empresa.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'statusSummary' => $statusSummary,
            'groupSummary' => $groupSummary,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Ver pedido de empresa.
     *
     * @group Empresa
     *
     * @authenticated
     *
     * @urlParam order integer required ID de pedido propio. Example: 100
     *
     * @response 200 {"content":"Vista HTML del pedido"}
     * @response 403 {"message":"No autorizado"}
     * @response 404 {"message":"Pedido no encontrado"}
     */
    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items', 'distributor', 'user', 'statusHistory.actor');

        $items = $order->items;
        $totals = [
            'items_count' => $items->count(),
            'units_total' => (float) $items->sum('qty'),
            'subtotals_total' => (float) $items->sum('subtotal'),
            'average_unit_price' => $items->isNotEmpty() ? (float) $items->avg('price_each') : 0.0,
        ];

        $hasFinancialGap = abs($totals['subtotals_total'] - (float) $order->total_amount) > 0.01;

        return view('empresa.orders.show', [
            'order' => $order,
            'totals' => $totals,
            'hasFinancialGap' => $hasFinancialGap,
        ]);
    }

    public function edit(Order $order): View|RedirectResponse
    {
        $this->authorize('update', $order);

        if (! $order->status->canBeEditedByCompany()) {
            return redirect()
                ->route('empresa.orders.show', $order)
                ->withErrors('Solo puedes editar cotizaciones en revisión o rechazadas.');
        }

        $order->load('items');

        return view('empresa.orders.edit', [
            'order' => $order,
            'departments' => config('locations.colombia_departments', []),
            'catalogOptions' => $this->catalogOptions(),
        ]);
    }

    /**
     * Actualizar cotizacion de empresa.
     *
     * Solo aplica a pedidos en estados editables por empresa.
     *
     * @group Empresa
     *
     * @authenticated
     *
     * @urlParam order integer required ID de pedido propio. Example: 100
     *
     * @bodyParam contact_name string required Contacto. Example: Ana Perez
     * @bodyParam contact_email string required Correo. Example: ana@example.com
     * @bodyParam phone string required Telefono. Example: 3001234567
     * @bodyParam company_name string required Empresa. Example: Distribuciones Medicas SAS
     * @bodyParam company_nit string required NIT. Example: 900123456
     * @bodyParam company_address string required Direccion. Example: Calle 100 # 10-20
     * @bodyParam city string required Ciudad. Example: Bogota
     * @bodyParam department string required Departamento. Example: Cundinamarca
     * @bodyParam items array required Items existentes de la cotizacion.
     * @bodyParam new_items array Items nuevos desde catalogo.
     *
     * @response 302 {"redirect":"empresa.orders.show"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Datos invalidos"}
     */
    public function update(
        UpdateCompanyOrderRequest $request,
        Order $order,
        UpdateOrderAction $updateOrderAction,
        OrderStatusTransitionService $transitionService,
    ): RedirectResponse {
        $this->authorize('update', $order);

        if (! $order->status->canBeEditedByCompany()) {
            return redirect()
                ->route('empresa.orders.show', $order)
                ->withErrors('Solo puedes editar cotizaciones en revisión o rechazadas.');
        }

        $wasRejected = $order->status->isRejected();

        try {
            $order = $updateOrderAction->execute($order, $request->validated(), $request->user());

            if ($wasRejected) {
                /** @var User $actor */
                $actor = $request->user();

                $order = $transitionService->transition(
                    $order,
                    OrderStatus::PendingApproval,
                    $actor,
                    'Cotización ajustada y reenviada para aprobación interna.'
                );

                $order->update([
                    'approval_note' => null,
                ]);
            }
        } catch (DomainException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->getMessage());
        }

        $statusMessage = $wasRejected
            ? "Cotización {$order->oc_number} actualizada y reenviada para aprobación."
            : "Cotización {$order->oc_number} actualizada correctamente.";

        return redirect()
            ->route('empresa.orders.show', $order)
            ->with('status', $statusMessage);
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
            ->get(['id', 'name', 'sku', 'price', 'is_vat_excluded']);

        return $products
            ->flatMap(function (Product $product) {
                $baseLabel = trim("{$product->sku} · {$product->name}");
                $taxLabel = OrderLineVat::label((bool) $product->is_vat_excluded);

                if ((int) ($product->active_variants_count ?? 0) === 0) {
                    return [[
                        'ref' => 'p:'.$product->id,
                        'label' => "{$baseLabel} · {$taxLabel}",
                        'price' => (float) $product->price,
                    ]];
                }

                return $product->variants->map(function (ProductVariant $variant) use ($baseLabel, $taxLabel): array {
                    $attributeName = $variant->attributeValue?->attribute?->name ?? 'Variante';
                    $attributeValue = $variant->attributeValue?->value ?? ('#'.$variant->id);

                    return [
                        'ref' => 'v:'.$variant->id,
                        'label' => "{$baseLabel} · {$attributeName}: {$attributeValue} · {$taxLabel}",
                        'price' => (float) $variant->price,
                    ];
                });
            })
            ->values()
            ->all();
    }

    /**
     * Descargar PDF de pedido de empresa.
     *
     * @group Empresa
     *
     * @authenticated
     *
     * @urlParam order integer required ID de pedido propio. Example: 100
     *
     * @response 200 {"content":"Descarga binaria PDF"}
     * @response 302 {"redirect":"back","message":"PDF en generacion"}
     * @response 403 {"message":"No autorizado"}
     */
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

    /**
     * Reordenar pedido.
     *
     * Agrega al carrito los productos activos disponibles del pedido.
     *
     * @group Empresa
     *
     * @authenticated
     *
     * @urlParam order integer required ID de pedido propio. Example: 100
     *
     * @response 302 {"redirect":"cart.index|catalog.index"}
     * @response 403 {"message":"Tu rol no permite reordenar."}
     */
    public function reorder(Order $order, CartService $cartService): RedirectResponse
    {
        $this->authorize('view', $order);

        /** @var User $user */
        $user = auth()->user();

        abort_unless($user->canReorder(), 403, 'Tu rol no permite reordenar.');

        $order->loadMissing('items');

        if ($order->items->isEmpty()) {
            return back()->with('warning', 'Este pedido no tiene ítems para reordenar.');
        }

        $productIds = $order->items
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $variantIds = $order->items
            ->pluck('product_variant_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $activeProducts = Product::active()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $activeVariants = $variantIds
            ? ProductVariant::active()->whereIn('id', $variantIds)->get()->keyBy('id')
            : collect();

        $added = 0;
        $skipped = [];

        foreach ($order->items as $item) {
            $product = $activeProducts->get($item->product_id);

            if (! $product) {
                $skipped[] = $item->product_name_snapshot.' (no disponible)';

                continue;
            }

            $variant = null;

            if ($item->product_variant_id) {
                $variant = $activeVariants->get($item->product_variant_id);

                if (! $variant) {
                    $skipped[] = $item->product_name_snapshot.' (variante no disponible)';

                    continue;
                }
            } elseif ($product->hasConfigurableVariants()) {
                $skipped[] = $item->product_name_snapshot.' (requiere elegir variante)';

                continue;
            }

            $cartService->add(
                product: $product,
                qty: max(1, (int) $item->qty),
                unitLabel: $item->unit_label ?? 'unidad',
                variant: $variant,
            );

            $added++;
        }

        if ($added === 0) {
            return redirect()->route('catalog.index')
                ->with('warning', 'No se pudo agregar ningún producto al carrito. Puede que todos estén inactivos o requieran selección de variante.');
        }

        $statusMessage = "Se agregaron {$added} producto(s) al carrito desde {$order->oc_number}.";

        if (! empty($skipped)) {
            $skippedList = implode(', ', $skipped);

            return redirect()->route('cart.index')
                ->with('status', $statusMessage)
                ->with('warning', "No se pudieron agregar: {$skippedList}");
        }

        return redirect()->route('cart.index')->with('status', $statusMessage);
    }
}
