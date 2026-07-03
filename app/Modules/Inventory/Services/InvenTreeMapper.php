<?php

namespace App\Modules\Inventory\Services;

class InvenTreeMapper
{
    public function partToProductData(array $part): array
    {
        $sku = $part['IPN'] ?? $part['pk'] ?? '';

        return [
            'name' => $part['name'] ?? '',
            'sku' => $sku,
            'description' => $part['description'] ?? '',
            'stock' => $part['total_in_stock'] ?? $part['in_stock'] ?? 0,
            'is_active' => $part['active'] ?? true,
            'price' => $this->parsePrice($part),
            'brand' => $this->extractBrand($part),
        ];
    }

    public function partToProductFillable(array $part): array
    {
        $data = $this->partToProductData($part);

        return [
            'name' => $data['name'],
            'sku' => $data['sku'],
            'description' => $data['description'],
            'stock' => $data['stock'],
            'is_active' => $data['is_active'],
            'price' => $data['price'],
        ];
    }

    private function parsePrice(array $part): float
    {
        $price = $part['pricing_min'] ?? null;

        if ($price !== null && $price !== '') {
            return (float) $price;
        }

        return 0;
    }

    private function extractBrand(array $part): ?string
    {
        if (! empty($part['keywords'])) {
            $keywords = $part['keywords'];

            if (stripos($keywords, 'brand:') === 0) {
                return trim(substr($keywords, 6));
            }
        }

        return null;
    }
}
