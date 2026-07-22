<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Shared\Enums\DistributorTier;

/**
 * Immutable result of a tier price calculation.
 *
 * Internal state is kept in integer cents to avoid floating point drift. The
 * *Decimal() accessors render values as "X.XX" strings ready to persist into
 * decimal(_,2) columns without going through float.
 */
final readonly class TierPrice
{
    public function __construct(
        public DistributorTier $tier,
        public int $basePriceCents,
        public int $silverPriceCents,
        public int $effectivePriceCents,
        public int $unitSavingsCents,
    ) {}

    public function basePriceDecimal(): string
    {
        return self::centsToDecimal($this->basePriceCents);
    }

    public function silverPriceDecimal(): string
    {
        return self::centsToDecimal($this->silverPriceCents);
    }

    public function effectivePriceDecimal(): string
    {
        return self::centsToDecimal($this->effectivePriceCents);
    }

    public function unitSavingsDecimal(): string
    {
        return self::centsToDecimal($this->unitSavingsCents);
    }

    /**
     * Render integer cents as a "X.XX" decimal string (no float involved).
     */
    public static function centsToDecimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);
        $whole = intdiv($absolute, 100);
        $fraction = $absolute % 100;

        return $sign.$whole.'.'.str_pad((string) $fraction, 2, '0', STR_PAD_LEFT);
    }
}
