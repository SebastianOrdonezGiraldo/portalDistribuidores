<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Support\Collection;
use App\Modules\Shared\Exceptions\DomainException;

/**
 * Central pricing engine for cart, order creation and order edits.
 *
 * Computes Plata/Oro candidates, applies the gold pricing threshold, then
 * evaluates the tier minimum order against the effective total.
 *
 * Pass $rules explicitly to price against a historical commerce snapshot;
 * omit it to use the currently published rules.
 */
final class OrderPricingCalculator
{
    public function __construct(
        private readonly CommercePricingRulesProvider $rulesProvider,
        private readonly GoldPricingEligibilityEvaluator $goldPricingEvaluator,
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
        // The rules snapshot still controls thresholds/minimums; prices come
        // exclusively from the product cache synchronized with ContaPyme.
        $priceCalculator = new DistributorPriceCalculator($rules);
        $candidateLines = [];

        foreach ($items as $item) {
            /** @var Product $product */
            $product = $item['product'];
            $variant = $item['variant'] ?? null;
            $qty = max(1, (int) $item['qty']);
            $unitLabel = $item['unit_label'] ?? 'unidades';
            $goldPrice = $variant instanceof ProductVariant ? $variant->price : $product->price;
            // Variants have independent prices. Without a mapped/synchronized
            // Silver value they must fail closed instead of inheriting a parent
            // price that may refer to a different ContaPyme resource.
            $silverPrice = $variant instanceof ProductVariant ? $variant->silver_price : $product->silver_price;

            if ($goldPrice === null || $silverPrice === null || (string) $silverPrice === '') {
                throw new DomainException('El precio Silver de un producto no está sincronizado; no se puede crear el pedido.');
            }

            $priced = $priceCalculator->calculateFromDecimal((string) $goldPrice, (string) $silverPrice, DistributorTier::Gold);
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

        $pricingTier = $distributorTier ?? DistributorTier::Silver;

        $goldDecision = $this->goldPricingEvaluator->evaluate(
            $pricingTier,
            $rules->goldPricingThresholdConfig(),
            $silverCandidateTotal,
            $goldCandidateTotal,
        );

        $applyGold = $distributorTier === DistributorTier::Gold && $goldDecision->eligible;

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
            goldPricingDecision: $goldDecision,
            minimumOrderDecision: $minimumDecision,
            silverCandidateTotalCents: $silverCandidateTotal,
            goldCandidateTotalCents: $goldCandidateTotal,
            effectiveTotalCents: $effectiveTotal,
            goldPricingApplied: $applyGold,
            goldSavingsCents: $goldSavings,
            commercePricingRuleId: $rules->ruleId,
        );
    }
}
