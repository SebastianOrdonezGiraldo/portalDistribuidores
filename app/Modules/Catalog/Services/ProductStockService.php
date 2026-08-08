<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Handles admin-managed stock updates for products and variants.
 *
 * Manual stock updates are available only while the product is not managed by
 * ContaPyme. Every allowed change is serialized and recorded in StockMovement.
 */
class ProductStockService
{
    /**
     * Update stock for a product that has no active variants.
     *
     * @return bool false when the product uses variant-level stock
     */
    public function updateSimpleProductStock(Product $product, ?float $stock): bool
    {
        return DB::transaction(function () use ($product, $stock): bool {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->withCount([
                    'variants as active_variants_count' => fn ($query) => $query->where('is_active', true),
                ])
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertManualStockIsAllowed($lockedProduct);

            if ((int) $lockedProduct->active_variants_count > 0) {
                return false;
            }

            $previousStock = (float) ($lockedProduct->stock ?? 0);
            $lockedProduct->forceFill([
                'stock' => $this->normalizeStock($stock),
            ])->save();

            StockMovement::record(
                product: $lockedProduct,
                previousStock: $previousStock,
                newStock: (float) ($lockedProduct->stock ?? 0),
                source: 'admin_manual',
            );

            return true;
        });
    }

    /**
     * Update active variant stock and mirror the aggregate total to the parent product.
     *
     * @param  array<int, array{id:int, stock:float|null}>  $rows
     * @return bool false when there are no active variants to update
     */
    public function updateVariantStocks(Product $product, array $rows): bool
    {
        return DB::transaction(function () use ($product, $rows): bool {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertManualStockIsAllowed($lockedProduct);

            $variants = ProductVariant::query()
                ->where('product_id', $lockedProduct->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($variants->isEmpty()) {
                return false;
            }

            foreach ($rows as $row) {
                $variantId = (int) ($row['id'] ?? 0);
                /** @var ProductVariant|null $variant */
                $variant = $variants->get($variantId);

                if (! $variant) {
                    continue;
                }

                $previousStock = (float) ($variant->stock ?? 0);
                $variant->stock = $this->normalizeStock($row['stock'] ?? null);
                $variant->save();

                StockMovement::record(
                    product: $lockedProduct,
                    variant: $variant,
                    previousStock: $previousStock,
                    newStock: (float) ($variant->stock ?? 0),
                    source: 'admin_manual',
                );
            }

            $stockValues = $variants
                ->map(fn (ProductVariant $variant): ?float => is_numeric($variant->stock) ? (float) $variant->stock : null)
                ->values();

            $hasAnyStock = $stockValues->contains(fn (?float $value): bool => $value !== null);
            $totalStock = $hasAnyStock
                ? (float) round($stockValues->filter(fn (?float $value): bool => $value !== null)->sum(), 2)
                : null;

            $lockedProduct->forceFill([
                'stock' => $totalStock,
            ])->save();

            return true;
        });
    }

    /**
     * Normalize manual stock input to the stored decimal precision.
     */
    private function normalizeStock(?float $stock): ?float
    {
        if ($stock === null) {
            return null;
        }

        return (float) round(max(0, $stock), 2);
    }

    private function assertManualStockIsAllowed(Product $product): void
    {
        if (! $product->isStockManagedByContaPyme()) {
            return;
        }

        throw ValidationException::withMessages([
            'stock' => 'El stock de este producto es administrado por ContaPyme.',
        ]);
    }
}
