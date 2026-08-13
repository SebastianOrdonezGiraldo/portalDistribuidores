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
     * Calculate a tier price from integer cents.
     *
     * The two-argument form is retained for callers that still calculate a
     * Silver price from the published legacy rule. Runtime catalog/order code
     * should pass the explicit ContaPyme Silver value as the second argument.
     */
    public function calculate(
        int $goldCents,
        DistributorTier|int $silverCentsOrTier,
        ?DistributorTier $tier = null,
    ): TierPrice {
        if ($silverCentsOrTier instanceof DistributorTier) {
            $tier = $silverCentsOrTier;
            $silverCents = $this->calculateLegacySilverCents($goldCents);
        } else {
            $silverCents = $silverCentsOrTier;
        }

        if (! $tier instanceof DistributorTier) {
            throw new InvalidArgumentException('Debe indicar el nivel comercial del precio.');
        }

        if ($goldCents < 0 || $silverCents < 0) {
            throw new InvalidArgumentException('Los precios Gold y Silver no pueden ser negativos.');
        }

        if ($silverCents < $goldCents) {
            throw new InvalidArgumentException('El precio Silver no puede ser inferior al precio Gold.');
        }

        $effectiveCents = $tier === DistributorTier::Gold ? $goldCents : $silverCents;
        $unitSavingsCents = $tier === DistributorTier::Gold
            ? $silverCents - $goldCents
            : 0;

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
    public function calculateFromDecimal(
        int|string $basePrice,
        DistributorTier|int|string $silverPriceOrTier,
        ?DistributorTier $tier = null,
    ): TierPrice {
        $goldCents = $this->decimalToCents($basePrice);

        if ($silverPriceOrTier instanceof DistributorTier) {
            return $this->calculate($goldCents, $silverPriceOrTier);
        }

        return $this->calculate($goldCents, $this->decimalToCents($silverPriceOrTier), $tier);
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

    private function calculateLegacySilverCents(int $goldCents): int
    {
        if ($goldCents < 0) {
            throw new InvalidArgumentException('Los precios Gold y Silver no pueden ser negativos.');
        }

        $markup = intdiv($goldCents * $this->rules->silverMarkupBasisPoints, 10_000);
        $candidate = $goldCents + $markup;
        $rounding = $this->rules->silverRoundingMultiple * 100;

        return intdiv($candidate + $rounding - 1, $rounding) * $rounding;
    }
}
