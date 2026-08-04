<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Shared\Enums\DistributorTier;

/**
 * Immutable outcome of tier minimum-order evaluation.
 */
final readonly class MinimumOrderDecision
{
    public function __construct(
        public bool $allowed,
        public bool $enabled,
        public int $minimumAmountCents,
        public int $evaluatedAmountCents,
        public int $missingAmountCents,
        public string $reason,
        public ?DistributorTier $tier,
    ) {}
}
