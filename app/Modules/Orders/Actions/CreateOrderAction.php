<?php

namespace App\Modules\Orders\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\DTOs\CreateOrderData;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Orders\Support\OrderLineVat;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates the canonical order from checkout data.
 *
 * This action owns the order transaction: it resolves active products/variants,
 * snapshots line data, assigns the final OC number, decreases stock for
 * submitted orders and dispatches OrderPlaced only when no internal approval is
 * pending.
 */
class CreateOrderAction
{
    public function __construct(
        private readonly OrderStatusTransitionService $orderStatusTransitionService,
        private readonly OrderInventoryService $orderInventoryService,
    ) {}

    /**
     * Persist an order and its items from normalized checkout data.
     *
     * Invalid product or variant lines are skipped, but the action rejects the
     * request if no valid line can produce a positive total. This keeps the
     * controller free of catalog and inventory rules.
     *
     * @throws DomainException when the cart cannot become a valid order
     */
    public function execute(?User $user, CreateOrderData $data): Order
    {
        $productIds = collect($data->items)->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->all();
        $variantIds = collect($data->items)
            ->pluck('variant_id')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $products = Product::active()
            ->whereIn('id', $productIds)
            ->withCount(['variants as active_variants_count' => fn ($query) => $query->active()])
            ->get()
            ->keyBy('id');
        $variants = ProductVariant::active()
            ->whereIn('id', $variantIds)
            ->with('attributeValue.attribute')
            ->get()
            ->keyBy('id');

        if ($products->isEmpty()) {
            throw new DomainException('No hay productos válidos en el pedido.');
        }

        // Pre-calculate total outside the transaction to catch empty-cart errors early.
        $preTotal = $this->calculateTotal($data->items, $products, $variants);

        if ($preTotal <= 0) {
            throw new DomainException('El carrito no puede generar una orden vacía.');
        }

        $order = DB::transaction(function () use ($user, $data, $products, $variants, $preTotal) {
            $status = $data->requiresApproval
                ? OrderStatus::PendingApproval
                : OrderStatus::Submitted;

            $order = Order::create([
                'distributor_id' => $user?->distributor_id,
                'user_id' => $user?->id,
                'oc_number' => Order::OC_PREFIX.'TMP-'.Str::upper(Str::random(8)),
                'contact_name' => $data->contactName,
                'contact_email' => $data->contactEmail,
                'phone' => $data->phone,
                'company_name' => $data->companyName,
                'company_nit' => $data->companyNit,
                'company_address' => $data->companyAddress,
                'city' => $data->city,
                'department' => $data->department,
                'notes' => $data->notes,
                'status' => $status,
                'total_amount' => 0,
            ]);

            foreach ($data->items as $item) {
                $product = $products->get((int) $item['product_id']);

                if (! $product) {
                    continue;
                }

                $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;
                $variant = null;
                $priceEach = (float) $product->price;

                if ($variantId > 0) {
                    $variant = $variants->get($variantId);

                    if (! $variant || (int) $variant->product_id !== (int) $product->id) {
                        continue;
                    }

                    $priceEach = (float) $variant->price;
                } elseif ((int) ($product->active_variants_count ?? 0) > 0) {
                    // Prevent creating a line without a chosen variant when product requires one.
                    continue;
                }

                $qty = max(1, (int) $item['qty']);
                $subtotal = $qty * $priceEach;

                $order->items()->create(array_merge([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name_snapshot' => $product->name,
                    'sku_snapshot' => $product->sku,
                    'variant_attribute_snapshot' => $variant?->attributeValue?->attribute?->name,
                    'variant_value_snapshot' => $variant?->attributeValue?->value,
                    'qty' => $qty,
                    'unit_label' => $item['unit_label'] ?? 'unidades',
                    'price_each' => $priceEach,
                    'subtotal' => $subtotal,
                ], OrderLineVat::snapshotAttributes($product)));
            }

            $order->update([
                'total_amount' => $preTotal,
                'oc_number' => sprintf(Order::OC_PREFIX.'%0'.Order::OC_PADDING.'d', $order->id),
            ]);

            if ($status === OrderStatus::Submitted) {
                $this->orderInventoryService->decreaseForOrder($order);
            }

            return $order->refresh();
        });

        $this->orderStatusTransitionService->recordInitialStatus(
            $order,
            $user,
            'Estado inicial registrado al crear la cotización.'
        );

        // Solo disparar el evento si la orden no requiere aprobación interna previa
        if (! $data->requiresApproval) {
            event(new OrderPlaced($order));
        }

        return $order;
    }

    /**
     * Calculate the total using the same product/variant eligibility rules used
     * when rows are persisted.
     *
     * @param  array<int, array{product_id:int, variant_id?:int|null, qty:int}>  $items
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, ProductVariant>  $variants
     */
    private function calculateTotal(
        array $items,
        Collection $products,
        Collection $variants,
    ): float {
        $total = 0.0;

        foreach ($items as $item) {
            $product = $products->get((int) $item['product_id']);

            if (! $product) {
                continue;
            }

            $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;
            $priceEach = (float) $product->price;

            if ($variantId > 0) {
                $variant = $variants->get($variantId);

                if (! $variant || (int) $variant->product_id !== (int) $product->id) {
                    continue;
                }
                $priceEach = (float) $variant->price;
            } elseif ((int) ($product->active_variants_count ?? 0) > 0) {
                continue;
            }

            $total += max(1, (int) $item['qty']) * $priceEach;
        }

        return $total;
    }
}
