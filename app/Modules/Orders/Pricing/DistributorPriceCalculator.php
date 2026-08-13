<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Shared\Enums\DistributorTier;
use InvalidArgumentException;

/**
 * Converts the two locally cached ContaPyme prices into a tier-price snapshot.
 * Silver is never derived from Gold here; both values must be supplied by the
 * synchronization cache.
 */
final class DistributorPriceCalculator
{
    public function __construct(private readonly CommercePricingRules $rules) {}

    /**
     * Calculate the tier price from a base price expressed in integer cents.
     */
    public function calculate(int $goldCents, int $silverCents, DistributorTier $tier): TierPrice
    {
        if ($goldCents < 0 || $silverCents < 0) {
            throw new InvalidArgumentException('Los precios Gold y Silver no pueden ser negativos.');
        }

        if ($silverCents < $goldCents) {
            throw new InvalidArgumentException('El precio Silver no puede ser inferior al precio Gold.');
        }

        $effectiveCents = $tier === DistributorTier::Gold ? $goldCents : $silverCents;
        $unitSavingsCents = $silverCents - $goldCents;

        return new TierPrice(
            tier: $tier,
            basePriceCents: $goldCents,
            silverPriceCents: $silverCents,
            effectivePriceCents: $effectiveCents,
            unitSavingsCents: $unitSavingsCents,
        );
    }

    /**
     * Calculate from a decimal base price (e.g. the decimal:2 cast string from
     * Eloquent). The value is parsed from string to cents without using float.
     */
    public function calculateFromDecimal(int|string $goldPrice, int|string $silverPrice, DistributorTier $tier): TierPrice
    {
        return $this->calculate($this->decimalToCents($goldPrice), $this->decimalToCents($silverPrice), $tier);
    }

    /**
     * Convert a decimal money value into integer cents parsing the string
     * representation, avoiding float precision errors.
     */
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
