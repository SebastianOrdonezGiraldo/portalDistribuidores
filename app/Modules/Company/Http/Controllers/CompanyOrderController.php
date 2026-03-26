<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Jobs\GenerateOrderPdfJob;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Orders\Services\OrderPdfGenerator;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyOrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        /** @var User $user */
        $user = auth()->user();

        $statusOptions = array_map(fn (OrderStatus $s) => $s->value, OrderStatus::cases());

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::in($statusOptions)],
        ]);

        $filters = array_merge(['q' => null, 'status' => null], $filters);

        $baseQuery = Order::query()
            ->where('distributor_id', $user->distributor_id)
            ->with('user')
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim((string) $filters['q']);
                $query->where(function ($sub) use ($term) {
                    $sub->where('oc_number', 'like', "%{$term}%")
                        ->orWhere('company_name', 'like', "%{$term}%")
                        ->orWhere('contact_name', 'like', "%{$term}%");
                });
            })
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']));

        $orders = (clone $baseQuery)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusSummary = collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $s) => [$s->value => (int) ($statusCounts[$s->value] ?? 0)]);

        $metrics = [
            'total' => (clone $baseQuery)->count(),
            'amount' => (float) (clone $baseQuery)->sum('total_amount'),
        ];

        return view('empresa.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'statusSummary' => $statusSummary,
            'metrics' => $metrics,
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items', 'distributor', 'user');

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
