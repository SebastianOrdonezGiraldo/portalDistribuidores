<?php

namespace App\Modules\Inventory\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\InvenTreeApiClient;
use App\Modules\Inventory\Services\InvenTreeMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProductsFromInvenTreeAction
{
    public function __construct(
        private readonly InvenTreeApiClient $apiClient,
        private readonly InvenTreeMapper $mapper,
    ) {}

    public function execute(?callable $onProgress = null): array
    {
        $stats = [
            'total' => 0,
            'updated_price' => 0,
            'updated_stock' => 0,
            'updated_both' => 0,
            'skipped' => 0,
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
                    $stats['skipped']++;

                    if ($onProgress !== null) {
                        $onProgress($index + 1, $stats['total'], $part);
                    }

                    continue;
                }

                $result = DB::transaction(function () use ($part): string {
                    return $this->syncPart($part);
                });

                if ($result === 'updated_price') {
                    $stats['updated_price']++;
                } elseif ($result === 'updated_stock') {
                    $stats['updated_stock']++;
                } elseif ($result === 'updated_both') {
                    $stats['updated_both']++;
                } elseif ($result === 'not_found') {
                    $stats['not_found']++;
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
        $sku = $part['IPN'] ?? $part['pk'] ?? '';

        if ($sku === '') {
            return 'skipped';
        }

        $product = Product::query()
            ->where('sku', $sku)
            ->lockForUpdate()
            ->first();

        if ($product === null) {
            return 'not_found';
        }

        $invenTreePrice = $this->parsePrice($part);
        $invenTreeStock = $part['total_in_stock'] ?? $part['in_stock'] ?? null;

        $priceChanged = false;
        $stockChanged = false;

        if ($invenTreePrice !== null) {
            $currentPrice = (float) ($product->price ?? 0);

            if (abs($currentPrice - $invenTreePrice) > 0.0001) {
                $product->price = $invenTreePrice;
                $priceChanged = true;
            }
        }

        if ($invenTreeStock !== null) {
            $currentStock = (float) ($product->stock ?? 0);

            if (abs($currentStock - $invenTreeStock) > 0.0001) {
                $product->stock = $invenTreeStock;
                $stockChanged = true;
            }
        }

        if (! $priceChanged && ! $stockChanged) {
            return 'skipped';
        }

        $product->save();

        return match (true) {
            $priceChanged && $stockChanged => 'updated_both',
            $priceChanged => 'updated_price',
            $stockChanged => 'updated_stock',
        };
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
