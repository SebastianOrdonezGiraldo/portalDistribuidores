<?php

namespace App\Modules\Orders\Support;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\OrderItem;

class OrderLineVat
{
    public const DEFAULT_RATE = 0.13;

    /**
     * @return array{is_vat_excluded_snapshot: bool, vat_rate_snapshot: float}
     */
    public static function snapshotAttributes(Product $product): array
    {
        $isVatExcluded = (bool) $product->is_vat_excluded;

        return [
            'is_vat_excluded_snapshot' => $isVatExcluded,
            'vat_rate_snapshot' => $isVatExcluded ? 0.0 : self::DEFAULT_RATE,
        ];
    }

    /**
     * @return array{valorUnit: float, valorIva: float, valorTotal: float, valorTotalLinea: float}
     */
    public static function amountsForItem(OrderItem $item): array
    {
        $priceEach = round((float) $item->price_each, 2);
        $rate = max(0.0, (float) ($item->vat_rate_snapshot ?? self::DEFAULT_RATE));
        $isVatExcluded = (bool) ($item->is_vat_excluded_snapshot ?? false);

        if ($isVatExcluded || $rate <= 0.0) {
            $valorUnit = $priceEach;
            $valorIva = 0.0;
            $valorTotal = $priceEach;
        } else {
            $vatDivisor = 1 + $rate;
            $valorUnit = round($priceEach / $vatDivisor, 2);
            $valorIva = round($priceEach - $valorUnit, 2);
            $valorTotal = round($valorUnit + $valorIva, 2);
        }

        return [
            'valorUnit' => $valorUnit,
            'valorIva' => $valorIva,
            'valorTotal' => $valorTotal,
            'valorTotalLinea' => round((float) $item->qty * $valorTotal, 2),
        ];
    }

    public static function label(bool $isVatExcluded): string
    {
        return $isVatExcluded ? 'Excluido de IVA' : 'IVA incluido';
    }
}
