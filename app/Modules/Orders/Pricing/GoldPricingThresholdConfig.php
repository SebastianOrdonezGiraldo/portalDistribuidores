<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Shared\Enums\GoldThresholdBasis;

/**
 * Typed view of the gold pricing threshold rule from the commercial snapshot.
 */
final readonly class GoldPricingThresholdConfig
{
    public function __construct(
        public bool $enabled,
        public int $amountPesos,
        public GoldThresholdBasis $basis,
        public ?int $ruleId,
    ) {}

    public function amountCents(): int
    {
        return $this->amountPesos * 100;
    }
}
