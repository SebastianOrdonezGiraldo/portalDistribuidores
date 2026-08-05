<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Support\Collection;

/**
 * Central pricing engine for cart, order creation and order edits.
 *
 * Silver distributors always pay Silver prices. Gold distributors always pay
 * Gold prices. The tier minimum order is evaluated against that effective total.
 *
 * Pass $rules explicitly to price against a historical commerce snapshot;
 * omit it to use the currently published rules.
 */
final class OrderPricingCalculator
{
    public function __construct(
        private readonly CommercePricingRulesProvider $rulesProvider,
        private readonly MinimumOrderEvaluator $minimumOrderEvaluator,
    ) {}

    /**
     * @param  list<array{
     *   product: Product,
     *   variant?: ProductVariant|null,
     *   qty: int,
     *   unit_label?: string
     * }>  $items
     * @param  DistributorTier|null  $distributorTier  Null for guests/admins (no minimum; priced as Plata).
     */
    public function calculate(
        ?DistributorTier $distributorTier,
        array $items,
        ?CommercePricingRules $rules = null,
    ): OrderPricingResult {
        $rules ??= $this->rulesProvider->current();
        // Historical edits must use the snapshot's markup, not the container binding.
        $priceCalculator = new DistributorPriceCalculator($rules);
        $candidateLines = [];

        foreach ($items as $item) {
            /** @var Product $product */
            $product = $item['product'];
            $variant = $item['variant'] ?? null;
            $qty = max(1, (int) $item['qty']);
            $unitLabel = $item['unit_label'] ?? 'unidades';
            $basePrice = $variant?->price ?? $product->price;

            $priced = $priceCalculator->calculateFromDecimal((string) $basePrice, DistributorTier::Gold);
            $goldUnit = $priced->basePriceCents;
            $silverUnit = $priced->silverPriceCents;

            $candidateLines[] = [
                'product' => $product,
                'variant' => $variant instanceof ProductVariant ? $variant : null,
                'qty' => $qty,
                'unit_label' => $unitLabel,
                'gold_unit' => $goldUnit,
                'silver_unit' => $silverUnit,
                'gold_subtotal' => $goldUnit * $qty,
                'silver_subtotal' => $silverUnit * $qty,
            ];
        }

        $silverCandidateTotal = array_sum(array_column($candidateLines, 'silver_subtotal'));
        $goldCandidateTotal = array_sum(array_column($candidateLines, 'gold_subtotal'));

        $applyGold = $distributorTier === DistributorTier::Gold;

        $lines = collect($candidateLines)->map(function (array $row) use ($applyGold): OrderPricingLine {
            $effectiveUnit = $applyGold ? $row['gold_unit'] : $row['silver_unit'];
            $effectiveSubtotal = $effectiveUnit * $row['qty'];
            $saving = max(0, $row['silver_subtotal'] - $effectiveSubtotal);

            return new OrderPricingLine(
                product: $row['product'],
                variant: $row['variant'],
                qty: $row['qty'],
                unitLabel: $row['unit_label'],
                silverUnitPriceCents: $row['silver_unit'],
                goldUnitPriceCents: $row['gold_unit'],
                effectiveUnitPriceCents: $effectiveUnit,
                silverSubtotalCents: $row['silver_subtotal'],
                goldSubtotalCents: $row['gold_subtotal'],
                effectiveSubtotalCents: $effectiveSubtotal,
                savingCents: $saving,
            );
        });

        /** @var Collection<int, OrderPricingLine> $lines */
        $effectiveTotal = (int) $lines->sum(fn (OrderPricingLine $line) => $line->effectiveSubtotalCents);
        $goldSavings = (int) $lines->sum(fn (OrderPricingLine $line) => $line->savingCents);

        $minimumDecision = $this->minimumOrderEvaluator->evaluate(
            $distributorTier,
            $effectiveTotal,
            $rules->tierMinimumOrderConfig(),
        );

        return new OrderPricingResult(
            lines: $lines->values(),
            goldPricingDecision: $this->goldPricingDecision(
                $distributorTier,
                $rules->goldPricingThresholdConfig(),
                $goldCandidateTotal,
            ),
            minimumOrderDecision: $minimumDecision,
            silverCandidateTotalCents: $silverCandidateTotal,
            goldCandidateTotalCents: $goldCandidateTotal,
            effectiveTotalCents: $effectiveTotal,
            goldPricingApplied: $applyGold,
            goldSavingsCents: $goldSavings,
            commercePricingRuleId: $rules->ruleId,
        );
    }

    /**
     * Snapshot metadata for orders. Gold prices are always applied for Gold
     * distributors; threshold config is recorded but no longer gates pricing.
     */
    private function goldPricingDecision(
        ?DistributorTier $distributorTier,
        GoldPricingThresholdConfig $config,
        int $goldCandidateTotalCents,
    ): GoldPricingDecision {
        if ($distributorTier !== DistributorTier::Gold) {
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

        return new GoldPricingDecision(
            eligible: true,
            ruleEnabled: $config->enabled,
            evaluationBasis: $config->basis,
            thresholdAmountCents: $config->amountCents(),
            evaluatedAmountCents: $goldCandidateTotalCents,
            missingAmountCents: 0,
            ruleId: $config->ruleId,
            reason: 'always_applied',
        );
    }
}
