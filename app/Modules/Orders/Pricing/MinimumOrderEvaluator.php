<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Shared\Enums\DistributorTier;

/**
 * Decides whether the effective cart total meets the tier minimum order.
 *
 * Guests, admins and other actors without a distributor tier are not subject
 * to commercial minimums (reason: tier_not_subject_to_minimum).
 */
final class MinimumOrderEvaluator
{
    public function evaluate(
        ?DistributorTier $tier,
        int $effectiveTotalCents,
        TierMinimumOrderConfig $config,
    ): MinimumOrderDecision {
        if ($tier === null) {
            return new MinimumOrderDecision(
                allowed: true,
                enabled: false,
                minimumAmountCents: 0,
                evaluatedAmountCents: $effectiveTotalCents,
                missingAmountCents: 0,
                reason: 'tier_not_subject_to_minimum',
                tier: null,
            );
        }

        [$enabled, $minimumCents] = match ($tier) {
            DistributorTier::Silver => [$config->silverEnabled, $config->silverMinimumAmountCents],
            DistributorTier::Gold => [$config->goldEnabled, $config->goldMinimumAmountCents],
        };

        if (! $enabled) {
            return new MinimumOrderDecision(
                allowed: true,
                enabled: false,
                minimumAmountCents: $minimumCents,
                evaluatedAmountCents: $effectiveTotalCents,
                missingAmountCents: 0,
                reason: 'minimum_not_required',
                tier: $tier,
            );
        }

        $missing = max(0, $minimumCents - $effectiveTotalCents);

        if ($effectiveTotalCents >= $minimumCents) {
            return new MinimumOrderDecision(
                allowed: true,
                enabled: true,
                minimumAmountCents: $minimumCents,
                evaluatedAmountCents: $effectiveTotalCents,
                missingAmountCents: 0,
                reason: 'minimum_reached',
                tier: $tier,
            );
        }

        return new MinimumOrderDecision(
            allowed: false,
            enabled: true,
            minimumAmountCents: $minimumCents,
            evaluatedAmountCents: $effectiveTotalCents,
            missingAmountCents: $missing,
            reason: 'minimum_not_reached',
            tier: $tier,
        );
    }
}
