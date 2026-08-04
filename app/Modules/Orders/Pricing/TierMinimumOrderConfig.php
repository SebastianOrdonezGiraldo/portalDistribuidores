<?php

namespace App\Modules\Orders\Pricing;

/**
 * Typed view of per-tier minimum order amounts from the commercial snapshot.
 */
final readonly class TierMinimumOrderConfig
{
    public function __construct(
        public bool $silverEnabled,
        public int $silverMinimumAmountCents,
        public bool $goldEnabled,
        public int $goldMinimumAmountCents,
        public ?int $commercePricingRuleId,
    ) {}
}
