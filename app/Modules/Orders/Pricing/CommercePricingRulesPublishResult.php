<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Orders\Models\CommercePricingRule;

/**
 * Explicit outcome of publishing commercial pricing rules.
 */
final readonly class CommercePricingRulesPublishResult
{
    public function __construct(
        public CommercePricingRule $rule,
        public bool $changed,
    ) {}
}
