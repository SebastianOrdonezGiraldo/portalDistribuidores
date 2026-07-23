<?php

namespace App\Modules\Orders\Pricing;

use InvalidArgumentException;

/**
 * Immutable snapshot of the commercial pricing rules used by the price calculator.
 *
 * Values are stored as integer basis points (500 = 5.00%) and peso multiples.
 */
final readonly class CommercePricingRules
{
    public function __construct(
        public int $silverMarkupBasisPoints,
        public int $silverRoundingMultiple,
    ) {
        $this->assertInvariants();
    }

    /**
     * Decimal percentage string without float (500 -> "5.00").
     */
    public function silverMarkupPercentageDecimal(): string
    {
        $whole = intdiv($this->silverMarkupBasisPoints, 100);
        $fraction = $this->silverMarkupBasisPoints % 100;

        return $whole.'.'.str_pad((string) $fraction, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Display percentage for forms (500 -> "5.00").
     */
    public function silverMarkupPercentageDisplay(): string
    {
        return $this->silverMarkupPercentageDecimal();
    }

    private function assertInvariants(): void
    {
        if ($this->silverMarkupBasisPoints < 0 || $this->silverMarkupBasisPoints > 10_000) {
            throw new InvalidArgumentException(
                'silver_markup_basis_points debe estar entre 0 y 10000.',
            );
        }

        if ($this->silverRoundingMultiple <= 0) {
            throw new InvalidArgumentException(
                'silver_rounding_multiple debe ser mayor que cero.',
            );
        }
    }
}
