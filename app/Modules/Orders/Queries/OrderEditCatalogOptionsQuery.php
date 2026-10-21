<?php

namespace App\Modules\Orders\Queries;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Pricing\DistributorPriceCalculator;
use App\Modules\Orders\Support\OrderLineVat;
use App\Modules\Shared\Enums\DistributorTier;

/**
 * Catalog products and variants that can be added while editing an order,
 * priced at the order's tier and with their effective available stock.
 */
class OrderEditCatalogOptionsQuery
{
    public function __construct(
        private readonly DistributorPriceCalculator $priceCalculator,
    ) {}

    /**
     * @return array<int, array{ref:string,label:string,price:float,stock:int|null}>
     */
    public function execute(Order $order): array
    {
        $tier = $this->resolveOrderTier($order);

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
            ->get(['id', 'name', 'sku', 'price', 'silver_price', 'stock', 'reserved_stock', 'is_vat_excluded']);

        $effectivePrice = fn (int|string $goldPrice, int|string $silverPrice): float => (float) $this->priceCalculator
            ->calculateFromDecimal($goldPrice, $silverPrice, $tier)
            ->effectivePriceDecimal();

        return $products
            ->flatMap(function (Product $product) use ($effectivePrice) {
                $baseLabel = trim("{$product->sku} · {$product->name}");
                $taxLabel = OrderLineVat::label((bool) $product->is_vat_excluded);

                if ((int) ($product->active_variants_count ?? 0) === 0) {
                    return [[
                        'ref' => 'p:'.$product->id,
                        'label' => "{$baseLabel} · {$taxLabel}",
                        'price' => $effectivePrice((string) $product->price, (string) $product->silver_price),
                        'stock' => $this->availableCatalogStock($product),
                    ]];
                }

                return $product->variants->map(function (ProductVariant $variant) use ($product, $baseLabel, $taxLabel, $effectivePrice): array {
                    $attributeName = $variant->attributeValue?->attribute?->name ?? 'Variante';
                    $attributeValue = $variant->attributeValue?->value ?? ('#'.$variant->id);

                    return [
                        'ref' => 'v:'.$variant->id,
                        'label' => "{$baseLabel} · {$attributeName}: {$attributeValue} · {$taxLabel}",
                        'price' => $effectivePrice((string) $variant->price, (string) $variant->silver_price),
                        'stock' => $this->availableCatalogStock($product, $variant),
                    ];
                })->all();
            })
            ->values()
            ->all();
    }

    private function resolveOrderTier(Order $order): DistributorTier
    {
        return $order->distributor_tier_snapshot
            ?? $order->distributor?->tier
            ?? DistributorTier::Silver;
    }

    private function availableCatalogStock(Product $product, ?ProductVariant $variant = null): ?int
    {
        $available = collect([$variant?->available_stock, $product->available_stock])
            ->filter(fn (mixed $stock): bool => is_numeric($stock))
            ->map(fn (mixed $stock): float => max(0, (float) $stock));

        return $available->isEmpty()
            ? null
            : max(0, (int) floor((float) $available->min()));
    }
}
