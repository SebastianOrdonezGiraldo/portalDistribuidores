<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Shared\Enums\GoldThresholdBasis;
use InvalidArgumentException;

/**
 * Immutable snapshot of the commercial pricing rules used by the price calculator.
 *
 * Values are stored as integer basis points (500 = 5.00%), peso multiples and
 * whole-COP amounts for minimum-order / gold-threshold rules.
 * Callers must supply resolved values (from DB or config); this DTO does not invent amounts.
 */
final readonly class CommercePricingRules
{
    public function __construct(
        public int $silverMarkupBasisPoints,
        public int $silverRoundingMultiple,
        public bool $silverMinOrderEnabled,
        public int $silverMinOrderAmount,
        public bool $goldMinOrderEnabled,
        public int $goldMinOrderAmount,
        public bool $goldPricingThresholdEnabled,
        public int $goldPricingThresholdAmount,
        public GoldThresholdBasis $goldPricingThresholdBasis,
        public ?int $ruleId = null,
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

    public function goldPricingThresholdConfig(): GoldPricingThresholdConfig
    {
        return new GoldPricingThresholdConfig(
            enabled: $this->goldPricingThresholdEnabled,
            amountPesos: $this->goldPricingThresholdAmount,
            basis: $this->goldPricingThresholdBasis,
            ruleId: $this->ruleId,
        );
    }

    public function tierMinimumOrderConfig(): TierMinimumOrderConfig
    {
        return new TierMinimumOrderConfig(
            silverEnabled: $this->silverMinOrderEnabled,
            silverMinimumAmountCents: $this->silverMinOrderAmount * 100,
            goldEnabled: $this->goldMinOrderEnabled,
            goldMinimumAmountCents: $this->goldMinOrderAmount * 100,
            commercePricingRuleId: $this->ruleId,
        );
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

        if ($this->silverMinOrderAmount <= 0) {
            throw new InvalidArgumentException('silver_min_order_amount debe ser mayor que cero.');
        }

        if ($this->goldMinOrderAmount <= 0) {
            throw new InvalidArgumentException('gold_min_order_amount debe ser mayor que cero.');
        }

        if ($this->goldPricingThresholdAmount <= 0) {
            throw new InvalidArgumentException('gold_pricing_threshold_amount debe ser mayor que cero.');
        }
    }
}
