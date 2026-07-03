<?php

namespace App\Modules\Inventory\Actions;

use App\Modules\Catalog\Models\Product;
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

                $product = Product::where('sku', (string) $sku)->first();

                if ($product === null) {
                    $stats['unmatched']++;
                    continue;
                }

                $stats['matched']++;

                DB::transaction(function () use ($product, $invenTreeStock): void {
                    $currentStock = (float) ($product->stock ?? 0);
                    $newStock = (float) $invenTreeStock;

                    if (abs($currentStock - $newStock) > 0.001) {
                        $product->update(['stock' => $newStock]);
                    }
                });

                $stats['updated']++;
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
}
