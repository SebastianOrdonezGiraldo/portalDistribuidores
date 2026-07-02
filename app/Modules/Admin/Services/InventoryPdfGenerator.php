<?php

namespace App\Modules\Admin\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Shared\Enums\DocumentType;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Support\Collection;

class InventoryPdfGenerator
{
    /**
     * @param  Collection<int, Product>  $products
     * @param  list<string>  $appliedFilters
     */
    public function generate(Collection $products, array $appliedFilters = [], ?string $generatedBy = null): DomPdfWrapper
    {
        $rows = $this->buildRows($products);
        $knownStockRows = $rows->whereNotNull('stock');

        return Pdf::loadView('admin.products.inventory-pdf', [
            'rows' => $rows,
            'appliedFilters' => $appliedFilters,
            'generatedAt' => now()->setTimezone(config('app.timezone')),
            'generatedBy' => $generatedBy,
            'totals' => [
                'products_count' => $products->count(),
                'rows_count' => $rows->count(),
                'known_stock_rows' => $knownStockRows->count(),
                'unknown_stock_rows' => $rows->count() - $knownStockRows->count(),
                'total_stock' => (float) round((float) $knownStockRows->sum('stock'), 2),
            ],
        ])->setPaper('a4', 'landscape');
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return Collection<int, array{
     *     sku:string,
     *     product_name:string,
     *     brand:?string,
     *     category:?string,
     *     variant_attribute:?string,
     *     variant_value:?string,
     *     price:?float,
     *     stock:?float,
     *     status:string,
     *     documents:array<string, bool>
     * }>
     */
    private function buildRows(Collection $products): Collection
    {
        return $products
            ->flatMap(function (Product $product): array {
                $baseRow = [
                    'sku' => (string) $product->sku,
                    'product_name' => (string) $product->name,
                    'brand' => $product->brand,
                    'category' => $product->category?->name,
                    'variant_attribute' => $product->variantAttribute?->name,
                    'status' => $product->is_active ? 'Disponible' : 'Inactivo',
                    'documents' => [
                        'tech_sheet' => $product->documents->contains('type', DocumentType::TechSheet->value),
                        'manual' => $product->documents->contains('type', DocumentType::Manual->value),
                        'invima' => $product->documents->contains('type', DocumentType::Invima->value),
                        'quick_guide' => $product->documents->contains('type', DocumentType::QuickGuide->value),
                        'calibration_document' => $product->documents->contains('type', DocumentType::CalibrationDocument->value),
                        'video' => $product->videos->isNotEmpty(),
                    ],
                ];

                $variants = $product->variants
                    ->where('is_active', true)
                    ->values();

                if ($variants->isEmpty()) {
                    return [
                        array_merge($baseRow, [
                            'variant_value' => null,
                            'price' => $this->normalizeDecimal($product->price),
                            'stock' => $this->normalizeDecimal($product->stock),
                        ]),
                    ];
                }

                return $variants
                    ->map(fn (ProductVariant $variant): array => array_merge($baseRow, [
                        'variant_value' => $variant->attributeValue?->value,
                        'price' => $this->normalizeDecimal($variant->price),
                        'stock' => $this->normalizeDecimal($variant->stock),
                    ]))
                    ->all();
            })
            ->values();
    }

    private function normalizeDecimal(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) round((float) $value, 2);
    }
}
