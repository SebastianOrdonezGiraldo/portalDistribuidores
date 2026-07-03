<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class ProductStockService
{
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

            if ((int) $lockedProduct->active_variants_count > 0) {
                return false;
            }

            if ($lockedProduct->inventree_stock !== null) {
                return false;
            }

            $previousStock = (float) ($lockedProduct->stock ?? 0);
            $lockedProduct->stock = $this->normalizeStock($stock);
            $lockedProduct->save();

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
     * @param  array<int, array{id:int, stock:float|null}>  $rows
     */
    public function updateVariantStocks(Product $product, array $rows): bool
    {
        return DB::transaction(function () use ($product, $rows): bool {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $variants = ProductVariant::query()
                ->where('product_id', $lockedProduct->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($variants->isEmpty()) {
                return false;
            }

            if ($variants->contains(fn (ProductVariant $variant): bool => $variant->inventree_stock !== null)) {
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

            $lockedProduct->stock = $totalStock;
            $lockedProduct->save();

            return true;
        });
    }

    private function normalizeStock(?float $stock): ?float
    {
        if ($stock === null) {
            return null;
        }

        return (float) round(max(0, $stock), 2);
    }
}
