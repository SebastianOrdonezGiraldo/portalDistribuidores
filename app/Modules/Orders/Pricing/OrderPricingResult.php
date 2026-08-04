<?php

namespace App\Modules\Orders\Pricing;

use Illuminate\Support\Collection;

/**
 * Complete immutable pricing outcome for a cart or order.
 *
 * @property-read Collection<int, OrderPricingLine> $lines
 */
final readonly class OrderPricingResult
{
    /**
     * @param  Collection<int, OrderPricingLine>  $lines
     */
    public function __construct(
        public Collection $lines,
        public GoldPricingDecision $goldPricingDecision,
        public MinimumOrderDecision $minimumOrderDecision,
        public int $silverCandidateTotalCents,
        public int $goldCandidateTotalCents,
        public int $effectiveTotalCents,
        public bool $goldPricingApplied,
        public int $goldSavingsCents,
        public ?int $commercePricingRuleId,
    ) {}

    public function checkoutAllowed(): bool
    {
        return $this->minimumOrderDecision->allowed;
    }

    public function minimumOrderAmountCents(): int
    {
        return $this->minimumOrderDecision->minimumAmountCents;
    }

    public function minimumOrderMissingAmountCents(): int
    {
        return $this->minimumOrderDecision->missingAmountCents;
    }

    public function goldPricingMissingAmountCents(): int
    {
        return $this->goldPricingDecision->missingAmountCents;
    }

    public function effectiveTotalDecimal(): string
    {
        return TierPrice::centsToDecimal($this->effectiveTotalCents);
    }

    public function silverCandidateTotalDecimal(): string
    {
        return TierPrice::centsToDecimal($this->silverCandidateTotalCents);
    }

    public function goldCandidateTotalDecimal(): string
    {
        return TierPrice::centsToDecimal($this->goldCandidateTotalCents);
    }

    public function goldSavingsDecimal(): string
    {
        return TierPrice::centsToDecimal($this->goldSavingsCents);
    }
}
