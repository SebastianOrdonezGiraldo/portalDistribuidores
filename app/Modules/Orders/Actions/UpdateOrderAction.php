<?php

namespace App\Modules\Orders\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Pricing\CommercePricingRules;
use App\Modules\Orders\Pricing\CommercePricingRulesProvider;
use App\Modules\Orders\Pricing\OrderPricingCalculator;
use App\Modules\Orders\Pricing\OrderPricingLine;
use App\Modules\Orders\Pricing\OrderPricingResult;
use App\Modules\Orders\Pricing\OrderPricingSnapshotMapper;
use App\Modules\Orders\Pricing\TierPrice;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Orders\Support\OrderLineVat;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Updates an existing quotation/order and fully reprices every final line through
 * OrderPricingCalculator using the commercial rule originally associated to the
 * order (or the current rule for historical orders without a FK).
 */
class UpdateOrderAction
{
    public function __construct(
        private readonly OrderInventoryService $orderInventoryService,
        private readonly OrderPricingCalculator $orderPricingCalculator,
        private readonly CommercePricingRulesProvider $rulesProvider,
        private readonly OrderPricingSnapshotMapper $snapshotMapper,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(
        Order $order,
        array $payload,
        ?User $actor = null,
        bool $allowSubmittedEdit = false,
    ): Order {
        $tier = $this->resolveCommercialTier($order);
        $pricingTier = $tier ?? DistributorTier::Silver;
        $lineSpecs = $this->resolveFinalLineSpecs(
            $order,
            $payload['items'] ?? [],
            $payload['new_items'] ?? [],
        );

        $rules = $this->resolveRulesForOrder($order);
        $pricing = $this->orderPricingCalculator->calculate(
            $tier,
            $this->toCalculatorInput($lineSpecs),
            $rules,
        );

        if ($pricing->effectiveTotalCents <= 0) {
            throw new DomainException('La cotización debe conservar al menos un ítem con cantidad mayor a cero.');
        }

        $this->assertMinimumOrderAllowsUpdate($order, $pricing);

        $preparedItems = $this->mapPricedLines($lineSpecs, $pricing);

        return DB::transaction(function () use ($order, $payload, $preparedItems, $pricing, $pricingTier, $actor, $allowSubmittedEdit): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $isEditable = $allowSubmittedEdit
                ? $lockedOrder->status->canBeEditedByAdmin()
                : $lockedOrder->status->canBeEditedByCompany();

            if (! $isEditable) {
                throw new DomainException('Esta cotización no puede editarse en su estado actual.');
            }

            $statusConsumesInventory = $this->statusConsumesInventory($lockedOrder->status);

            if ($statusConsumesInventory) {
                $this->orderInventoryService->increaseForOrder($lockedOrder);
            }

            $lockedOrder->items()->delete();

            foreach ($preparedItems as $itemData) {
                $lockedOrder->items()->create($itemData);
            }

            $lockedOrder->update(array_merge([
                'contact_name' => $payload['contact_name'],
                'contact_email' => $payload['contact_email'],
                'phone' => $payload['phone'],
                'company_name' => $payload['company_name'],
                'company_nit' => $payload['company_nit'],
                'company_address' => $payload['company_address'],
                'city' => $payload['city'],
                'department' => $payload['department'],
                'notes' => $payload['notes'] ?? null,
                'distributor_tier_snapshot' => $pricingTier,
                'total_amount' => $pricing->effectiveTotalDecimal(),
                'pdf_path' => null,
            ], $this->snapshotMapper->headerAttributes($pricing)));

            $lockedOrder->statusHistory()->create([
                'from_status' => $lockedOrder->status->value,
                'to_status' => $lockedOrder->status->value,
                'changed_by_user_id' => $actor?->id,
                'note' => $allowSubmittedEdit
                    ? 'Cotización actualizada por administrador.'
                    : 'Cotización actualizada por el cliente.',
                'metadata' => [
                    'type' => 'order_updated',
                    'scope' => $allowSubmittedEdit ? 'admin' : 'company',
                ],
            ]);

            if ($statusConsumesInventory) {
                $this->orderInventoryService->decreaseForOrder($lockedOrder);
            }

            return $lockedOrder->refresh();
        });
    }

    /**
     * Commercial tier subject to minimums: only when the order belongs to a distributor.
     * Guests/admin orders (no distributor_id) are priced as Plata but not subject to minimums.
     */
    private function resolveCommercialTier(Order $order): ?DistributorTier
    {
        if ($order->distributor_id === null) {
            return null;
        }

        return $order->distributor_tier_snapshot
            ?? $order->distributor?->tier
            ?? DistributorTier::Silver;
    }

    private function resolveRulesForOrder(Order $order): CommercePricingRules
    {
        if ($order->commerce_pricing_rule_id !== null) {
            return $this->rulesProvider->forId((int) $order->commerce_pricing_rule_id);
        }

        return $this->rulesProvider->current();
    }

    private function assertMinimumOrderAllowsUpdate(Order $order, OrderPricingResult $pricing): void
    {
        if ($pricing->checkoutAllowed()) {
            return;
        }

        // Drafts may remain incomplete; submission/approval paths enforce the minimum later.
        if ($order->status === OrderStatus::Draft) {
            return;
        }

        $missing = TierPrice::centsToDecimal($pricing->minimumOrderMissingAmountCents());
        $minimum = TierPrice::centsToDecimal($pricing->minimumOrderAmountCents());
        $tierLabel = ($pricing->minimumOrderDecision->tier ?? DistributorTier::Silver)->badgeLabel();
        [$missingWhole] = array_pad(explode('.', $missing, 2), 2, '0');
        [$minimumWhole] = array_pad(explode('.', $minimum, 2), 2, '0');

        throw new DomainException(
            "Pedido mínimo para {$tierLabel}: \$".number_format((int) $minimumWhole, 0, ',', '.')
            .'. Te faltan \$'.number_format((int) $missingWhole, 0, ',', '.').'.'
        );
    }

    /**
     * @return list<array{
     *   product: Product,
     *   variant: ProductVariant|null,
     *   qty: int,
     *   unit_label: string,
     *   product_name_snapshot: string,
     *   sku_snapshot: string,
     *   variant_attribute_snapshot: string|null,
     *   variant_value_snapshot: string|null,
     *   is_vat_excluded_snapshot: bool,
     *   vat_rate_snapshot: float
     * }>
     */
    private function resolveFinalLineSpecs(Order $order, mixed $rawItems, mixed $rawNewItems): array
    {
        if (! is_array($rawItems) || $rawItems === []) {
            throw new DomainException('Debes enviar los ítems de la cotización.');
        }

        /** @var Collection<int, OrderItem> $existingItems */
        $existingItems = $order->items()->get()->keyBy('id');
        $kept = [];
        $seen = [];
        $productIds = [];

        foreach ($rawItems as $row) {
            if (! is_array($row)) {
                continue;
            }

            $itemId = (int) ($row['id'] ?? 0);
            $qty = max(0, (int) ($row['qty'] ?? 0));
            $unitLabel = trim((string) ($row['unit_label'] ?? 'unidades'));

            if ($itemId <= 0 || isset($seen[$itemId])) {
                continue;
            }

            $seen[$itemId] = true;
            $existing = $existingItems->get($itemId);

            if (! $existing) {
                throw new DomainException('Se enviaron ítems inválidos para esta cotización.');
            }

            if ($qty <= 0) {
                continue;
            }

            $productIds[] = (int) $existing->product_id;
            $kept[] = [
                'existing' => $existing,
                'qty' => $qty,
                'unit_label' => $unitLabel === '' ? 'unidades' : $unitLabel,
            ];
        }

        $products = Product::query()
            ->whereIn('id', array_values(array_unique($productIds)))
            ->get()
            ->keyBy('id');

        $variants = ProductVariant::query()
            ->whereIn('id', $existingItems->pluck('product_variant_id')->filter()->unique()->all())
            ->with('attributeValue.attribute')
            ->get()
            ->keyBy('id');

        $specs = [];

        foreach ($kept as $keptRow) {
            /** @var OrderItem $existing */
            $existing = $keptRow['existing'];
            $product = $products->get((int) $existing->product_id);

            if (! $product) {
                throw new DomainException('Uno de los productos de la cotización ya no está disponible.');
            }

            $variant = $existing->product_variant_id
                ? $variants->get((int) $existing->product_variant_id)
                : null;

            $specs[] = [
                'product' => $product,
                'variant' => $variant,
                'qty' => $keptRow['qty'],
                'unit_label' => $keptRow['unit_label'],
                'product_name_snapshot' => $existing->product_name_snapshot,
                'sku_snapshot' => $existing->sku_snapshot,
                'variant_attribute_snapshot' => $existing->variant_attribute_snapshot,
                'variant_value_snapshot' => $existing->variant_value_snapshot,
                'is_vat_excluded_snapshot' => (bool) ($existing->is_vat_excluded_snapshot ?? false),
                'vat_rate_snapshot' => (float) ($existing->vat_rate_snapshot ?? OrderLineVat::DEFAULT_RATE),
            ];
        }

        $specs = [...$specs, ...$this->resolveNewLineSpecs($rawNewItems)];

        if ($specs === []) {
            throw new DomainException('La cotización debe conservar al menos un ítem con cantidad mayor a cero.');
        }

        return $specs;
    }

    /**
     * @return list<array{
     *   product: Product,
     *   variant: ProductVariant|null,
     *   qty: int,
     *   unit_label: string,
     *   product_name_snapshot: string,
     *   sku_snapshot: string,
     *   variant_attribute_snapshot: string|null,
     *   variant_value_snapshot: string|null,
     *   is_vat_excluded_snapshot: bool,
     *   vat_rate_snapshot: float
     * }>
     */
    private function resolveNewLineSpecs(mixed $rawNewItems): array
    {
        if (! is_array($rawNewItems) || $rawNewItems === []) {
            return [];
        }

        $rows = [];
        $productIds = [];
        $variantIds = [];

        foreach ($rawNewItems as $row) {
            if (! is_array($row)) {
                continue;
            }

            $catalogRef = trim((string) ($row['catalog_ref'] ?? ''));
            $qty = max(0, (int) ($row['qty'] ?? 0));
            $unitLabel = trim((string) ($row['unit_label'] ?? 'unidades'));

            if ($catalogRef === '' || $qty <= 0) {
                continue;
            }

            if ($unitLabel === '') {
                $unitLabel = 'unidades';
            }

            $parsedRef = $this->parseCatalogRef($catalogRef);

            if (! $parsedRef) {
                throw new DomainException('Se enviaron productos nuevos inválidos para esta cotización.');
            }

            $rows[] = [
                'type' => $parsedRef['type'],
                'id' => $parsedRef['id'],
                'qty' => $qty,
                'unit_label' => $unitLabel,
            ];

            if ($parsedRef['type'] === 'product') {
                $productIds[] = $parsedRef['id'];
            } else {
                $variantIds[] = $parsedRef['id'];
            }
        }

        if ($rows === []) {
            return [];
        }

        $products = Product::query()
            ->active()
            ->whereIn('id', array_values(array_unique($productIds)))
            ->withCount(['variants as active_variants_count' => fn ($query) => $query->where('is_active', true)])
            ->get()
            ->keyBy('id');

        $variants = ProductVariant::query()
            ->active()
            ->whereIn('id', array_values(array_unique($variantIds)))
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->with('product', 'attributeValue.attribute')
            ->get()
            ->keyBy('id');

        $specs = [];

        foreach ($rows as $row) {
            if ($row['type'] === 'product') {
                /** @var Product|null $product */
                $product = $products->get($row['id']);

                if (! $product) {
                    throw new DomainException('Uno de los productos seleccionados ya no está disponible.');
                }

                if ((int) ($product->active_variants_count ?? 0) > 0) {
                    throw new DomainException("El producto {$product->sku} requiere seleccionar una variante.");
                }

                $vat = OrderLineVat::snapshotAttributes($product);
                $specs[] = [
                    'product' => $product,
                    'variant' => null,
                    'qty' => $row['qty'],
                    'unit_label' => $row['unit_label'],
                    'product_name_snapshot' => $product->name,
                    'sku_snapshot' => $product->sku,
                    'variant_attribute_snapshot' => null,
                    'variant_value_snapshot' => null,
                    'is_vat_excluded_snapshot' => $vat['is_vat_excluded_snapshot'],
                    'vat_rate_snapshot' => $vat['vat_rate_snapshot'],
                ];

                continue;
            }

            /** @var ProductVariant|null $variant */
            $variant = $variants->get($row['id']);

            if (! $variant) {
                throw new DomainException('Una de las variantes seleccionadas ya no está disponible.');
            }

            /** @var Product|null $product */
            $product = $variant->product;

            if (! $product) {
                throw new DomainException('No fue posible resolver el producto de la variante seleccionada.');
            }

            $vat = OrderLineVat::snapshotAttributes($product);
            $specs[] = [
                'product' => $product,
                'variant' => $variant,
                'qty' => $row['qty'],
                'unit_label' => $row['unit_label'],
                'product_name_snapshot' => $product->name,
                'sku_snapshot' => $product->sku,
                'variant_attribute_snapshot' => $variant->attributeValue?->attribute?->name,
                'variant_value_snapshot' => $variant->attributeValue?->value,
                'is_vat_excluded_snapshot' => $vat['is_vat_excluded_snapshot'],
                'vat_rate_snapshot' => $vat['vat_rate_snapshot'],
            ];
        }

        return $specs;
    }

    /**
     * @param  list<array{product: Product, variant: ProductVariant|null, qty: int, unit_label: string}>  $specs
     * @return list<array{product: Product, variant: ProductVariant|null, qty: int, unit_label: string}>
     */
    private function toCalculatorInput(array $specs): array
    {
        return array_map(fn (array $spec): array => [
            'product' => $spec['product'],
            'variant' => $spec['variant'],
            'qty' => $spec['qty'],
            'unit_label' => $spec['unit_label'],
        ], $specs);
    }

    /**
     * @param  list<array<string, mixed>>  $specs
     * @return list<array<string, mixed>>
     */
    private function mapPricedLines(array $specs, OrderPricingResult $pricing): array
    {
        if ($pricing->lines->count() !== count($specs)) {
            throw new DomainException('No fue posible recalcular los precios de la cotización.');
        }

        $prepared = [];

        foreach ($pricing->lines->values() as $index => $line) {
            /** @var OrderPricingLine $line */
            $spec = $specs[$index];
            $prepared[] = array_merge([
                'product_id' => $line->product->id,
                'product_variant_id' => $line->variant?->id,
                'product_name_snapshot' => $spec['product_name_snapshot'],
                'sku_snapshot' => $spec['sku_snapshot'],
                'variant_attribute_snapshot' => $spec['variant_attribute_snapshot'],
                'variant_value_snapshot' => $spec['variant_value_snapshot'],
                'qty' => $line->qty,
                'unit_label' => $line->unitLabel,
                'is_vat_excluded_snapshot' => $spec['is_vat_excluded_snapshot'],
                'vat_rate_snapshot' => $spec['vat_rate_snapshot'],
            ], $this->snapshotMapper->linePriceAttributes($line));
        }

        return $prepared;
    }

    /**
     * @return array{type: 'product'|'variant', id: int}|null
     */
    private function parseCatalogRef(string $catalogRef): ?array
    {
        if (! preg_match('/^(p|v):(\d+)$/', $catalogRef, $matches)) {
            return null;
        }

        return [
            'type' => $matches[1] === 'p' ? 'product' : 'variant',
            'id' => (int) $matches[2],
        ];
    }

    private function statusConsumesInventory(OrderStatus $status): bool
    {
        return in_array($status, OrderStatus::inventoryConsuming(), true);
    }
}
