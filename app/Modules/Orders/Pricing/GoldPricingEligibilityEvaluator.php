<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\GoldThresholdBasis;

/**
 * Decides whether a Gold distributor may pay Gold (base) prices.
 */
final class GoldPricingEligibilityEvaluator
{
    public function evaluate(
        DistributorTier $tier,
        GoldPricingThresholdConfig $config,
        int $silverCandidateTotalCents,
        int $goldCandidateTotalCents,
    ): GoldPricingDecision {
        if ($tier !== DistributorTier::Gold) {
            return new GoldPricingDecision(
                eligible: false,
                ruleEnabled: $config->enabled,
                evaluationBasis: $config->basis,
                thresholdAmountCents: $config->amountCents(),
                evaluatedAmountCents: 0,
                missingAmountCents: 0,
                ruleId: $config->ruleId,
                reason: 'not_gold_distributor',
            );
        }

        if (! $config->enabled) {
            return new GoldPricingDecision(
                eligible: true,
                ruleEnabled: false,
                evaluationBasis: $config->basis,
                thresholdAmountCents: $config->amountCents(),
                evaluatedAmountCents: $this->evaluatedAmount($config->basis, $silverCandidateTotalCents, $goldCandidateTotalCents),
                missingAmountCents: 0,
                ruleId: $config->ruleId,
                reason: 'rule_disabled',
            );
        }

        $evaluated = $this->evaluatedAmount($config->basis, $silverCandidateTotalCents, $goldCandidateTotalCents);
        $threshold = $config->amountCents();
        $missing = max(0, $threshold - $evaluated);

        if ($evaluated >= $threshold) {
            return new GoldPricingDecision(
                eligible: true,
                ruleEnabled: true,
                evaluationBasis: $config->basis,
                thresholdAmountCents: $threshold,
                evaluatedAmountCents: $evaluated,
                missingAmountCents: 0,
                ruleId: $config->ruleId,
                reason: 'threshold_reached',
            );
        }

        return new GoldPricingDecision(
            eligible: false,
            ruleEnabled: true,
            evaluationBasis: $config->basis,
            thresholdAmountCents: $threshold,
            evaluatedAmountCents: $evaluated,
            missingAmountCents: $missing,
            ruleId: $config->ruleId,
            reason: 'threshold_not_reached',
        );
    }

    private function evaluatedAmount(
        GoldThresholdBasis $basis,
        int $silverCandidateTotalCents,
        int $goldCandidateTotalCents,
    ): int {
        return match ($basis) {
            GoldThresholdBasis::GoldCandidate => $goldCandidateTotalCents,
            GoldThresholdBasis::SilverCandidate => $silverCandidateTotalCents,
        };
    }
}
