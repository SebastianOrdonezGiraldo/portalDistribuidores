<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Shared\Enums\GoldThresholdBasis;

/**
 * Immutable outcome of gold pricing threshold evaluation.
 */
final readonly class GoldPricingDecision
{
    public function __construct(
        public bool $eligible,
        public bool $ruleEnabled,
        public GoldThresholdBasis $evaluationBasis,
        public int $thresholdAmountCents,
        public int $evaluatedAmountCents,
        public int $missingAmountCents,
        public ?int $ruleId,
        public string $reason,
    ) {}
}
