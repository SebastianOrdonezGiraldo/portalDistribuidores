<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Shared\Enums\DistributorTier;
use InvalidArgumentException;

/**
 * Single source of truth for selecting manually configured tier prices.
 *
 * Both prices are stored on the product or variant and arrive here already
 * resolved. The former percentage and rounding rule is intentionally unused.
 */
final class DistributorPriceCalculator
{
    public function calculate(int $goldCents, int $silverCents, DistributorTier $tier): TierPrice
    {
        if ($goldCents < 0 || $silverCents < 0) {
            throw new InvalidArgumentException('Los precios Oro y Plata no pueden ser negativos.');
        }

        $effectiveCents = $tier === DistributorTier::Gold ? $goldCents : $silverCents;

        return new TierPrice(
            tier: $tier,
            basePriceCents: $goldCents,
            silverPriceCents: $silverCents,
            effectivePriceCents: $effectiveCents,
            unitSavingsCents: max(0, $silverCents - $effectiveCents),
        );
    }

    public function calculateFromDecimal(
        int|string $goldPrice,
        int|string $silverPrice,
        DistributorTier $tier,
    ): TierPrice {
        return $this->calculate(
            $this->decimalToCents($goldPrice),
            $this->decimalToCents($silverPrice),
            $tier,
        );
    }

    /** Convert a decimal money value to integer cents without float arithmetic. */
    public function decimalToCents(int|string $value): int
    {
        $string = is_string($value) ? trim($value) : (string) $value;

        if ($string === '') {
            $string = '0';
        }

        $negative = str_starts_with($string, '-');
        $string = ltrim($string, '+-');

        [$whole, $fraction] = array_pad(explode('.', $string, 2), 2, '');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);

        if (! ctype_digit($whole) || ! ctype_digit($fraction)) {
            throw new InvalidArgumentException("Precio decimal inválido: {$value}");
        }

        $cents = ((int) $whole) * 100 + (int) $fraction;

        return $negative ? -$cents : $cents;
    }
}
