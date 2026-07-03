<?php

namespace App\Modules\Inventory\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
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

        if (! isset($payload['category_id'])) {
            $payload['category_id'] = $this->resolveDefaultCategoryId();
        }

        $product = Product::query()
            ->where('sku', $sku)
            ->lockForUpdate()
            ->first();

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

    private function resolveDefaultCategoryId(): int
    {
        $categoryId = (int) config('services.inventree.default_category_id', 0);

        if ($categoryId > 0) {
            return $categoryId;
        }

        $category = Category::query()->first();

        if ($category !== null) {
            return $category->id;
        }

        return Category::create([
            'name' => 'InvenTree',
            'slug' => 'inventree',
            'is_active' => true,
        ])->id;
    }

    private function hasChanges(Product $product, array $payload): bool
    {
        $fillable = $product->getFillable();

        foreach ($fillable as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $current = $product->{$field};
            $incoming = $payload[$field];

            if (in_array($field, ['price', 'stock', 'inventree_stock', 'reserved_stock'], true)) {
                if (abs((float) ($current ?? 0) - (float) ($incoming ?? 0)) > 0.0001) {
                    return true;
                }

                continue;
            }

            if ((string) $current !== (string) $incoming) {
                return true;
            }
        }

        return false;
    }
}
