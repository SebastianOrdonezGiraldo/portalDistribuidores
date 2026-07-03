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
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $parts = $this->apiClient->getParts([
            'active' => true,
            'category_detail' => true,
        ]);

        $stats['total'] = count($parts);

        foreach ($parts as $index => $part) {
            try {
                $result = DB::transaction(function () use ($part): string {
                    return $this->syncPart($part);
                });

                match ($result) {
                    'created' => $stats['created']++,
                    'updated' => $stats['updated']++,
                    default => $stats['skipped']++,
                };
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
        $payload = $this->mapper->partToProductFillable($part);
        $sku = $payload['sku'];

        if ($sku === '') {
            return 'skipped';
        }

        $product = Product::where('sku', $sku)->first();

        if ($product === null) {
            Product::create($payload);

            return 'created';
        }

        $needsUpdate = $this->hasChanges($product, $payload);

        if (! $needsUpdate) {
            return 'skipped';
        }

        $product->update($payload);

        return 'updated';
    }

    private function hasChanges(Product $product, array $payload): bool
    {
        $fillable = $product->getFillable();

        foreach ($fillable as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $current = (string) $product->{$field};
            $incoming = (string) $payload[$field];

            if ($current !== $incoming) {
                return true;
            }
        }

        return false;
    }
}
