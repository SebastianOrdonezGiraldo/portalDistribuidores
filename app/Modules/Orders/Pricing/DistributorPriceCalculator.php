<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Shared\Enums\DistributorTier;
use InvalidArgumentException;

/**
 * Single source of truth for tier pricing.
 *
 * The silver price derives from the base (Gold) price using the rule
 * "Precio Plata = techo(Precio Oro * (1 + incremento))" rounded up to the next
 * configured multiple. Every operation is performed on integer cents so no
 * floating point rounding can leak into monetary values.
 */
final class DistributorPriceCalculator
{
    private const PERCENTAGE_BASE = 10_000;

    private readonly int $silverMarkupPercent;

    private readonly int $silverRoundingMultiple;

    public function __construct(?int $silverMarkupPercent = null, ?int $silverRoundingMultiple = null)
    {
        $this->silverMarkupPercent = $silverMarkupPercent
            ?? (int) config('commerce.tiers.silver_markup_percent', 5);
        $this->silverRoundingMultiple = $silverRoundingMultiple
            ?? (int) config('commerce.tiers.silver_rounding_multiple', 1000);

        $this->validateConfig();
    }

    /**
     * Calculate the tier price from a base price expressed in integer cents.
     */
    public function calculate(int $baseCents, DistributorTier $tier): TierPrice
    {
        if ($baseCents < 0) {
            throw new InvalidArgumentException('El precio base no puede ser negativo.');
        }

        $silverCents = $this->silverCents($baseCents);
        $effectiveCents = $tier === DistributorTier::Gold ? $baseCents : $silverCents;
        $unitSavingsCents = $silverCents - $effectiveCents;

        return new TierPrice(
            tier: $tier,
            basePriceCents: $baseCents,
            silverPriceCents: $silverCents,
            effectivePriceCents: $effectiveCents,
            unitSavingsCents: $unitSavingsCents,
        );
    }

    /**
     * Calculate from a decimal base price (e.g. the decimal:2 cast string from
     * Eloquent). The value is parsed from string to cents without using float.
     */
    public function calculateFromDecimal(int|string $basePrice, DistributorTier $tier): TierPrice
    {
        return $this->calculate($this->decimalToCents($basePrice), $tier);
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

    private function silverCents(int $baseCents): int
    {
        $markupBasisPoints = self::PERCENTAGE_BASE + ($this->silverMarkupPercent * 100);

        // techo(base * (1 + incremento)) usando solo enteros.
        $unroundedSilverCents = intdiv(
            ($baseCents * $markupBasisPoints) + self::PERCENTAGE_BASE - 1,
            self::PERCENTAGE_BASE,
        );

        $roundingMultipleCents = $this->silverRoundingMultiple * 100;

        // Redondeo hacia arriba al siguiente múltiplo; un múltiplo exacto no sube.
        return intdiv(
            $unroundedSilverCents + $roundingMultipleCents - 1,
            $roundingMultipleCents,
        ) * $roundingMultipleCents;
    }

    private function validateConfig(): void
    {
        if ($this->silverMarkupPercent < 0) {
            throw new InvalidArgumentException(
                'commerce.tiers.silver_markup_percent no puede ser negativo.',
            );
        }

        if ($this->silverRoundingMultiple <= 0) {
            throw new InvalidArgumentException(
                'commerce.tiers.silver_rounding_multiple debe ser mayor que cero.',
            );
        }
    }
}
