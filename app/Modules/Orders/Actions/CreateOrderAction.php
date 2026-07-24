<?php

namespace App\Modules\Orders\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\DTOs\CreateOrderData;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Pricing\DistributorPriceCalculator;
use App\Modules\Orders\Pricing\DistributorTierResolver;
use App\Modules\Orders\Pricing\PricedOrderLine;
use App\Modules\Orders\Pricing\TierPrice;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Orders\Services\Payment\OrderPaymentService;
use App\Modules\Orders\Support\OrderLineVat;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Enums\PaymentStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates the canonical order from checkout data.
 *
 * This action owns the order transaction: it resolves active products/variants,
 * prices each line once through the central pricing engine, snapshots line data
 * (including tier pricing), assigns the final OC number, decreases stock for
 * submitted orders and dispatches OrderPlaced only when no internal approval is
 * pending.
 */
class CreateOrderAction
{
    public function __construct(
        private readonly OrderStatusTransitionService $orderStatusTransitionService,
        private readonly OrderInventoryService $orderInventoryService,
        private readonly DistributorPriceCalculator $priceCalculator,
        private readonly DistributorTierResolver $tierResolver,
        private readonly OrderPaymentService $orderPaymentService,
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

        // Resolve the tier once and price every line a single time. These priced
        // lines are the only source of truth for both the total and the items.
        $tier = $this->tierResolver->resolve($user);
        $pricedLines = $this->resolvePricedLines($data->items, $products, $variants, $tier);

        $totalCents = $pricedLines->sum(fn (PricedOrderLine $line) => $line->subtotalCents());

        if ($totalCents <= 0) {
            throw new DomainException('El carrito no puede generar una orden vacía.');
        }

        if ($data->isPayIntent() && $data->paymentMethod === null) {
            throw new DomainException('Selecciona un método de pago para continuar.');
        }

        $order = DB::transaction(function () use ($user, $data, $pricedLines, $tier, $totalCents) {
            $status = $data->requiresApproval
                ? OrderStatus::PendingApproval
                : OrderStatus::Submitted;

            $isPay = $data->isPayIntent() && $data->paymentMethod !== null;
            $paymentStatus = $isPay ? PaymentStatus::PendingUpload : PaymentStatus::NotApplicable;
            $reservationExpiresAt = $isPay
                ? now()->addMinutes($this->orderPaymentService->reservationTtlMinutes())
                : null;

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
                'payment_status' => $paymentStatus,
                'payment_method' => $isPay ? $data->paymentMethod : null,
                'payment_reservation_expires_at' => $reservationExpiresAt,
                'distributor_tier_snapshot' => $tier,
                'total_amount' => 0,
            ]);

            foreach ($pricedLines as $line) {
                $order->items()->create(array_merge([
                    'product_id' => $line->product->id,
                    'product_variant_id' => $line->variant?->id,
                    'product_name_snapshot' => $line->product->name,
                    'sku_snapshot' => $line->product->sku,
                    'variant_attribute_snapshot' => $line->variant?->attributeValue?->attribute?->name,
                    'variant_value_snapshot' => $line->variant?->attributeValue?->value,
                    'qty' => $line->qty,
                    'unit_label' => $line->unitLabel,
                    'price_each' => $line->price->effectivePriceDecimal(),
                    'base_unit_price' => $line->price->basePriceDecimal(),
                    'silver_unit_price' => $line->price->silverPriceDecimal(),
                    'unit_savings' => $line->price->unitSavingsDecimal(),
                    'subtotal' => $line->subtotalDecimal(),
                    'line_savings' => $line->lineSavingsDecimal(),
                ], OrderLineVat::snapshotAttributes($line->product)));
            }

            $order->update([
                'total_amount' => TierPrice::centsToDecimal($totalCents),
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
     * Build the collection of valid, priced order lines.
     *
     * Applies the same product/variant eligibility rules used historically
     * (inactive/foreign variants and configurable products without a chosen
     * variant are skipped) and prices each line once with the central engine.
     *
     * @param  array<int, array{product_id:int, variant_id?:int|null, qty:int, unit_label?:string}>  $items
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, ProductVariant>  $variants
     * @return Collection<int, PricedOrderLine>
     */
    private function resolvePricedLines(
        array $items,
        Collection $products,
        Collection $variants,
        DistributorTier $tier,
    ): Collection {
        $lines = collect();

        foreach ($items as $item) {
            $product = $products->get((int) $item['product_id']);

            if (! $product) {
                continue;
            }

            $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;
            $variant = null;
            $basePrice = $product->price;

            if ($variantId > 0) {
                $variant = $variants->get($variantId);

                if (! $variant || (int) $variant->product_id !== (int) $product->id) {
                    continue;
                }

                $basePrice = $variant->price;
            } elseif ((int) ($product->active_variants_count ?? 0) > 0) {
                // Prevent creating a line without a chosen variant when product requires one.
                continue;
            }

            $lines->push(new PricedOrderLine(
                product: $product,
                variant: $variant,
                qty: max(1, (int) $item['qty']),
                unitLabel: $item['unit_label'] ?? 'unidades',
                price: $this->priceCalculator->calculateFromDecimal((string) $basePrice, $tier),
            ));
        }

        return $lines;
    }
}
