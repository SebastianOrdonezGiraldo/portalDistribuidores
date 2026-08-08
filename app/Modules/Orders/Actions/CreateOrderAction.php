<?php

namespace App\Modules\Orders\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\DTOs\CreateOrderData;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Pricing\DistributorTierResolver;
use App\Modules\Orders\Pricing\OrderPricingCalculator;
use App\Modules\Orders\Pricing\OrderPricingSnapshotMapper;
use App\Modules\Orders\Pricing\TierPrice;
use App\Modules\Orders\Services\CommerceTierAdvisorProvider;
use App\Modules\Orders\Services\OrderAdvisorResolver;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Orders\Services\Payment\OrderPaymentService;
use App\Modules\Orders\Support\OrderLineVat;
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
 * prices each line once through OrderPricingCalculator, snapshots line data
 * (including tier pricing and commercial rule decisions), assigns the final OC
 * number, creates local HOLDs for submitted orders and dispatches OrderPlaced only
 * when no internal approval is pending.
 */
class CreateOrderAction
{
    public function __construct(
        private readonly OrderStatusTransitionService $orderStatusTransitionService,
        private readonly OrderInventoryService $orderInventoryService,
        private readonly DistributorTierResolver $tierResolver,
        private readonly OrderPricingCalculator $orderPricingCalculator,
        private readonly OrderPaymentService $orderPaymentService,
        private readonly OrderPricingSnapshotMapper $snapshotMapper,
        private readonly CommerceTierAdvisorProvider $advisorProvider,
        private readonly OrderAdvisorResolver $orderAdvisorResolver,
    ) {}

    /**
     * Persist an order and its items from normalized checkout data.
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

        // Null for guests/admins: priced as Plata, not subject to commercial minimums.
        $distributorTier = $user?->distributor?->tier;
        $pricingTier = $distributorTier ?? $this->tierResolver->resolve($user);
        $calculatorInput = $this->buildCalculatorInput($data->items, $products, $variants);

        if ($calculatorInput === []) {
            throw new DomainException('El carrito no puede generar una orden vacía.');
        }

        $pricing = $this->orderPricingCalculator->calculate($distributorTier, $calculatorInput);

        // Pricing fallback and advisor identity are intentionally independent.
        // Guests/admins can be priced as Plata without having a real distributor tier.
        $advisorTier = $user?->isDistributor() ? $user->distributor?->tier : null;
        $advisor = $advisorTier !== null
            ? $this->advisorProvider->forTier($advisorTier)
            : null;

        if ($pricing->effectiveTotalCents <= 0) {
            throw new DomainException('El carrito no puede generar una orden vacía.');
        }

        if (! $pricing->checkoutAllowed()) {
            $missing = TierPrice::centsToDecimal($pricing->minimumOrderMissingAmountCents());
            $minimum = TierPrice::centsToDecimal($pricing->minimumOrderAmountCents());
            $tierLabel = ($pricing->minimumOrderDecision->tier ?? $pricingTier)->badgeLabel();

            throw new DomainException(
                "Pedido mínimo para {$tierLabel}: \${$this->formatPesosFromDecimal($minimum)}. Te faltan \${$this->formatPesosFromDecimal($missing)}."
            );
        }

        if ($data->isPayIntent() && $data->paymentMethod === null) {
            throw new DomainException('Selecciona un método de pago para continuar.');
        }

        $order = DB::transaction(function () use ($user, $data, $pricing, $pricingTier, $advisor) {
            $status = $data->requiresApproval
                ? OrderStatus::PendingApproval
                : OrderStatus::Submitted;

            $isPay = $data->isPayIntent() && $data->paymentMethod !== null;
            $paymentStatus = $isPay ? PaymentStatus::PendingUpload : PaymentStatus::NotApplicable;
            $reservationExpiresAt = $isPay
                ? now()->addMinutes($this->orderPaymentService->reservationTtlMinutes())
                : null;

            $order = Order::create(array_merge([
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
                'distributor_tier_snapshot' => $pricingTier,
                'total_amount' => 0,
            ], $this->snapshotMapper->headerAttributes($pricing), $this->orderAdvisorResolver->snapshotAttributes($advisor)));

            foreach ($pricing->lines as $line) {
                $order->items()->create(array_merge([
                    'product_id' => $line->product->id,
                    'product_variant_id' => $line->variant?->id,
                    'product_name_snapshot' => $line->product->name,
                    'sku_snapshot' => $line->product->sku,
                    'variant_attribute_snapshot' => $line->variant?->attributeValue?->attribute?->name,
                    'variant_value_snapshot' => $line->variant?->attributeValue?->value,
                    'qty' => $line->qty,
                    'unit_label' => $line->unitLabel,
                ], $this->snapshotMapper->linePriceAttributes($line), OrderLineVat::snapshotAttributes($line->product)));
            }

            $order->update([
                'total_amount' => $pricing->effectiveTotalDecimal(),
                'oc_number' => sprintf(Order::OC_PREFIX.'%0'.Order::OC_PADDING.'d', $order->id),
            ]);

            if ($status === OrderStatus::Submitted) {
                $this->orderInventoryService->holdForOrder($order, $user, 'checkout_submitted');
            }

            return $order->refresh();
        });

        $this->orderStatusTransitionService->recordInitialStatus(
            $order,
            $user,
            'Estado inicial registrado al crear la cotización.'
        );

        if (! $data->requiresApproval) {
            event(new OrderPlaced($order));
        }

        return $order;
    }

    /**
     * @param  array<int, array{product_id:int, variant_id?:int|null, qty:int, unit_label?:string}>  $items
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, ProductVariant>  $variants
     * @return list<array{product: Product, variant: ProductVariant|null, qty: int, unit_label: string}>
     */
    private function buildCalculatorInput(array $items, Collection $products, Collection $variants): array
    {
        $input = [];

        foreach ($items as $item) {
            $product = $products->get((int) $item['product_id']);

            if (! $product) {
                continue;
            }

            $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;
            $variant = null;

            if ($variantId > 0) {
                $variant = $variants->get($variantId);

                if (! $variant || (int) $variant->product_id !== (int) $product->id) {
                    continue;
                }
            } elseif ((int) ($product->active_variants_count ?? 0) > 0) {
                continue;
            }

            $input[] = [
                'product' => $product,
                'variant' => $variant,
                'qty' => max(1, (int) $item['qty']),
                'unit_label' => $item['unit_label'] ?? 'unidades',
            ];
        }

        return $input;
    }

    private function formatPesosFromDecimal(string $decimal): string
    {
        [$whole] = array_pad(explode('.', $decimal, 2), 2, '0');

        return number_format((int) $whole, 0, ',', '.');
    }
}
