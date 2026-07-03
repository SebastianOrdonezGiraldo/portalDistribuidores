<?php

namespace App\Modules\Inventory\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\InvenTreeApiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProductsFromInvenTreeAction
{
    public function __construct(
        private readonly InvenTreeApiClient $apiClient,
    ) {}

    public function execute(?callable $onProgress = null): array
    {
        $stats = [
            'total' => 0,
            'matched' => 0,
            'updated_price' => 0,
            'skipped' => 0,
            'skipped_variants' => 0,
            'unmatched' => 0,
            'not_found' => 0,
            'errors' => 0,
        ];

        $parts = $this->apiClient->getParts([
            'active' => true,
            'category_detail' => true,
        ]);

        $stats['total'] = count($parts);

        foreach ($parts as $index => $part) {
            try {
                if (! empty($part['variant_of']) || ! empty($part['is_template'])) {
                    $stats['skipped_variants']++;

                    if ($onProgress !== null) {
                        $onProgress($index + 1, $stats['total'], $part);
                    }

                    continue;
                }

                $result = DB::transaction(function () use ($part): string {
                    return $this->syncPart($part);
                });

                if ($result === 'updated_price') {
                    $stats['matched']++;
                    $stats['updated_price']++;
                } elseif ($result === 'unchanged') {
                    $stats['matched']++;
                    $stats['skipped']++;
                } elseif ($result === 'skipped_variant_product') {
                    $stats['skipped_variants']++;
                } elseif ($result === 'not_found') {
                    $stats['unmatched']++;
                    $stats['not_found']++;
                } elseif ($result === 'missing_sku') {
                    $stats['unmatched']++;
                } else {
                    $stats['skipped']++;
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::error('InvenTree sync error for part', [
                    'part_id' => $part['pk'] ?? 'unknown',
                    'part_name' => $part['name'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }

            if ($onProgress !== null) {
                $onProgress($index + 1, $stats['total'], $part);
            }
        }

        return $stats;
    }

    private function syncPart(array $part): string
    {
        $skuField = config('services.inventree.sku_field', 'IPN');
        $sku = $part[$skuField] ?? $part['pk'] ?? '';

        if ($sku === '') {
            return 'missing_sku';
        }

        $product = Product::query()
            ->where('sku', (string) $sku)
            ->lockForUpdate()
            ->first();

        if ($product === null) {
            return 'not_found';
        }

        if ($product->hasConfigurableVariants()) {
            return 'skipped_variant_product';
        }

        $invenTreePrice = $this->parsePrice($part);
        $priceChanged = false;

        if ($invenTreePrice !== null) {
            $currentPrice = (float) ($product->price ?? 0);

            if (abs($currentPrice - $invenTreePrice) > 0.0001) {
                $product->price = $invenTreePrice;
                $priceChanged = true;
            }
        }

        if (! $priceChanged) {
            return 'unchanged';
        }

        $product->save();

        return 'updated_price';
    }

    private function parsePrice(array $part): ?float
    {
        $price = $part['pricing_min'] ?? null;

        if ($price !== null && $price !== '' && is_numeric($price)) {
            return (float) $price;
        }

        return null;
    }
}
