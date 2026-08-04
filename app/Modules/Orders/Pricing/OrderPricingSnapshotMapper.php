<?php

namespace App\Modules\Orders\Pricing;

/**
 * Maps an OrderPricingResult into order header snapshot attributes.
 */
final class OrderPricingSnapshotMapper
{
    /**
     * @return array<string, mixed>
     */
    public function headerAttributes(OrderPricingResult $pricing): array
    {
        $min = $pricing->minimumOrderDecision;
        $gold = $pricing->goldPricingDecision;

        return [
            'commerce_pricing_rule_id' => $pricing->commercePricingRuleId,
            'minimum_order_tier_snapshot' => $min->tier?->value,
            'minimum_order_enabled_snapshot' => $min->enabled,
            'minimum_order_amount_snapshot' => intdiv($min->minimumAmountCents, 100),
            'minimum_order_evaluated_amount' => TierPrice::centsToDecimal($min->evaluatedAmountCents),
            'minimum_order_reached' => in_array($min->reason, ['minimum_reached', 'minimum_not_required', 'tier_not_subject_to_minimum'], true),
            'minimum_order_decision_reason_snapshot' => $min->reason,
            'gold_pricing_threshold_enabled_snapshot' => $gold->ruleEnabled,
            'gold_pricing_threshold_amount_snapshot' => intdiv($gold->thresholdAmountCents, 100),
            'gold_pricing_threshold_basis_snapshot' => $gold->evaluationBasis->value,
            'gold_pricing_decision_reason_snapshot' => $gold->reason,
            'gold_pricing_applied' => $pricing->goldPricingApplied,
            'silver_candidate_total' => $pricing->silverCandidateTotalDecimal(),
            'gold_candidate_total' => $pricing->goldCandidateTotalDecimal(),
            'gold_savings_total' => $pricing->goldSavingsDecimal(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function linePriceAttributes(OrderPricingLine $line): array
    {
        return [
            'price_each' => $line->effectiveUnitPriceDecimal(),
            'base_unit_price' => $line->goldUnitPriceDecimal(),
            'silver_unit_price' => $line->silverUnitPriceDecimal(),
            'unit_savings' => $line->unitSavingsDecimal(),
            'subtotal' => $line->effectiveSubtotalDecimal(),
            'line_savings' => $line->lineSavingsDecimal(),
        ];
    }
}
