<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductVariantSyncService
{
    public function sync(Product $product, array $payload): void
    {
        $hasVariants = filter_var($payload['has_variants'] ?? false, FILTER_VALIDATE_BOOL);

        if (! $hasVariants) {
            $product->variants()->delete();

            if ($product->variant_attribute_id !== null) {
                $product->update(['variant_attribute_id' => null]);
            }

            return;
        }

        $rows = $this->normalizeRows((array) ($payload['variants'] ?? []));

        if ($rows->isEmpty()) {
            $product->variants()->delete();
            $product->update(['variant_attribute_id' => null]);

            return;
        }

        $attribute = $this->resolveAttribute($payload);
        $keptVariantIds = [];

        foreach ($rows as $index => $row) {
            $value = ProductAttributeValue::query()->firstOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'slug' => $row['value_slug'],
                ],
                [
                    'value' => $row['value'],
                ],
            );

            $variant = ProductVariant::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'product_attribute_value_id' => $value->id,
                ],
                [
                    'price' => $row['price'],
                    'stock' => $row['stock'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );

            $keptVariantIds[] = (int) $variant->id;
        }

        $product->variants()
            ->whereNotIn('id', $keptVariantIds)
            ->delete();

        if ($keptVariantIds !== []) {
            $product->variants()
                ->whereIn('id', $keptVariantIds)
                ->update(['is_active' => true]);
        }

        $minPrice = (float) ($rows->min('price') ?? 0);
        $stockValues = $rows->pluck('stock');
        $hasAnyStock = $stockValues->contains(fn ($stock) => $stock !== null);
        $totalStock = $hasAnyStock
            ? (float) $stockValues->filter(fn ($stock) => $stock !== null)->sum()
            : null;

        $product->update([
            'variant_attribute_id' => $attribute->id,
            'price' => $minPrice,
            'stock' => $totalStock,
        ]);
    }

    private function resolveAttribute(array $payload): ProductAttribute
    {
        $existingId = Arr::get($payload, 'variant_attribute_id');

        if ($existingId) {
            return ProductAttribute::query()->findOrFail((int) $existingId);
        }

        $name = (string) Arr::get($payload, 'new_variant_attribute_name', '');
        $normalizedName = Str::of($name)->squish()->toString();
        $slug = Str::slug(Str::lower($normalizedName));

        return ProductAttribute::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $normalizedName],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return Collection<int, array{value:string,value_slug:string,price:float,stock:float|null}>
     */
    private function normalizeRows(array $rows): Collection
    {
        return collect($rows)
            ->map(function (mixed $row) {
                $value = trim((string) data_get($row, 'value'));
                $valueSlug = Str::slug(Str::lower($value));

                if ($value === '' || $valueSlug === '') {
                    return null;
                }

                $price = round((float) data_get($row, 'price', 0), 2);
                $rawStock = data_get($row, 'stock');
                $stock = $rawStock === '' || $rawStock === null
                    ? null
                    : round((float) $rawStock, 2);

                return [
                    'value' => $value,
                    'value_slug' => $valueSlug,
                    'price' => max(0, $price),
                    'stock' => $stock !== null ? max(0, $stock) : null,
                ];
            })
            ->filter()
            ->unique('value_slug')
            ->values();
    }
}

