<?php

namespace App\Modules\Inventory\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\InvenTreeApiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncStockFromInvenTreeAction
{
    public function __construct(
        private readonly InvenTreeApiClient $apiClient,
    ) {}

    public function execute(?callable $onProgress = null): array
    {
        $stats = [
            'total' => 0,
            'matched' => 0,
            'updated' => 0,
            'skipped_variants' => 0,
            'unmatched' => 0,
            'errors' => 0,
        ];

        $parts = $this->apiClient->getParts([
            'active' => true,
            'category_detail' => true,
        ]);

        $stats['total'] = count($parts);

        $skuField = config('services.inventree.sku_field', 'IPN');

        foreach ($parts as $index => $part) {
            try {
                if (! empty($part['variant_of']) || ! empty($part['is_template'])) {
                    $stats['skipped_variants']++;

                    if ($onProgress !== null) {
                        $onProgress($index + 1, $stats['total'], $part);
                    }

                    continue;
                }

                $sku = $part[$skuField] ?? $part['pk'] ?? '';

                if ($sku === '') {
                    $stats['unmatched']++;
                    continue;
                }

                $invenTreeStock = $part['total_in_stock'] ?? $part['in_stock'] ?? null;

                if ($invenTreeStock === null) {
                    $stats['unmatched']++;
                    continue;
                }

                $result = $this->syncProductStock((string) $sku, (float) $invenTreeStock);

                if ($result === 'updated') {
                    $stats['matched']++;
                    $stats['updated']++;
                } elseif ($result === 'unchanged') {
                    $stats['matched']++;
                } elseif ($result === 'skipped_variant_product') {
                    $stats['skipped_variants']++;
                } else {
                    $stats['unmatched']++;
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::error('InvenTree stock sync error', [
                    'part_id' => $part['pk'] ?? 'unknown',
                    'sku' => $part[$skuField] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }

            if ($onProgress !== null) {
                $onProgress($index + 1, $stats['total'], $part);
            }
        }

        return $stats;
    }

    private function syncProductStock(string $sku, float $invenTreeStock): string
    {
        return DB::transaction(function () use ($sku, $invenTreeStock): string {
            $product = Product::query()
                ->where('sku', $sku)
                ->lockForUpdate()
                ->first();

            if ($product === null) {
                return 'not_found';
            }

            if ($product->hasConfigurableVariants()) {
                return 'skipped_variant_product';
            }

            $previousStock = (float) ($product->stock ?? 0);
            $previousInvenTree = (float) ($product->inventree_stock ?? 0);

            if (abs($previousInvenTree - $invenTreeStock) < 0.001) {
                return 'unchanged';
            }

            $reserved = (float) ($product->reserved_stock ?? 0);
            $product->inventree_stock = $invenTreeStock;
            $product->stock = round(max(0, $invenTreeStock - $reserved), 2);
            $product->save();

            StockMovement::record(
                product: $product->fresh(),
                previousStock: $previousStock,
                newStock: (float) $product->stock,
                source: 'inventree_sync',
            );

            return 'updated';
        });
    }
}
