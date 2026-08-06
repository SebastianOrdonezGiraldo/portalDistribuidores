<?php

namespace Tests\Unit;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\CommercePricingRule;
use App\Modules\Orders\Pricing\GoldPricingEligibilityEvaluator;
use App\Modules\Orders\Pricing\GoldPricingThresholdConfig;
use App\Modules\Orders\Pricing\MinimumOrderEvaluator;
use App\Modules\Orders\Pricing\OrderPricingCalculator;
use App\Modules\Orders\Pricing\TierMinimumOrderConfig;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\GoldThresholdBasis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderPricingRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_gold_pricing_evaluator_disabled_keeps_gold_eligible(): void
    {
        $decision = app(GoldPricingEligibilityEvaluator::class)->evaluate(
            DistributorTier::Gold,
            new GoldPricingThresholdConfig(false, 1_000_000, GoldThresholdBasis::GoldCandidate, 1),
            silverCandidateTotalCents: 73_500_000,
            goldCandidateTotalCents: 70_000_000,
        );

        $this->assertTrue($decision->eligible);
        $this->assertSame('rule_disabled', $decision->reason);
    }

    public function test_gold_pricing_evaluator_threshold_not_reached(): void
    {
        $decision = app(GoldPricingEligibilityEvaluator::class)->evaluate(
            DistributorTier::Gold,
            new GoldPricingThresholdConfig(true, 1_000_000, GoldThresholdBasis::GoldCandidate, 1),
            silverCandidateTotalCents: 73_500_000,
            goldCandidateTotalCents: 70_000_000,
        );

        $this->assertFalse($decision->eligible);
        $this->assertSame('threshold_not_reached', $decision->reason);
        $this->assertSame(30_000_000, $decision->missingAmountCents);
    }

    public function test_minimum_order_evaluator_blocks_below_threshold(): void
    {
        $decision = app(MinimumOrderEvaluator::class)->evaluate(
            DistributorTier::Silver,
            73_500_000,
            new TierMinimumOrderConfig(true, 80_000_000, false, 100_000_000, 1),
        );

        $this->assertFalse($decision->allowed);
        $this->assertSame('minimum_not_reached', $decision->reason);
        $this->assertSame(6_500_000, $decision->missingAmountCents);
    }

    public function test_gold_below_threshold_pays_silver_and_checkout_allowed(): void
    {
        DB::table('commerce_pricing_rules')->delete();
        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
            'silver_min_order_enabled' => true,
            'silver_min_order_amount' => 800_000,
            'gold_min_order_enabled' => false,
            'gold_min_order_amount' => 1_000_000,
            'gold_pricing_threshold_enabled' => true,
            'gold_pricing_threshold_amount' => 1_000_000,
            'gold_pricing_threshold_basis' => 'gold_candidate',
            'created_by_id' => null,
        ]);

        $calculator = app(OrderPricingCalculator::class);
        $product = Product::factory()->create(['price' => 96_000, 'stock' => 20, 'is_active' => true]);

        // 7 × 96k Oro = 672k → Plata, checkout permitido
        $result7 = $calculator->calculate(DistributorTier::Gold, [
            ['product' => $product, 'qty' => 7],
        ]);
        $this->assertFalse($result7->goldPricingApplied);
        $this->assertSame(67_200_000, $result7->goldCandidateTotalCents);
        $this->assertSame(70_700_000, $result7->effectiveTotalCents);
        $this->assertSame(32_800_000, $result7->goldPricingDecision->missingAmountCents);
        $this->assertTrue($result7->checkoutAllowed());
        $this->assertSame('minimum_not_required', $result7->minimumOrderDecision->reason);
        $this->assertSame(0, $result7->goldSavingsCents);
        $this->assertTrue($result7->lines->every(
            fn ($line) => $line->effectiveUnitPriceCents === $line->silverUnitPriceCents
        ));

        // 10 × 96k Oro = 960k → sigue Plata (base gold_candidate), total Plata 1.010.000
        $result10 = $calculator->calculate(DistributorTier::Gold, [
            ['product' => $product, 'qty' => 10],
        ]);
        $this->assertFalse($result10->goldPricingApplied);
        $this->assertSame(96_000_000, $result10->goldCandidateTotalCents);
        $this->assertSame(101_000_000, $result10->effectiveTotalCents);
        $this->assertSame(4_000_000, $result10->goldPricingDecision->missingAmountCents);
        $this->assertTrue($result10->checkoutAllowed());
        $this->assertSame(0, $result10->goldSavingsCents);

        // 11 × 96k Oro = 1.056.000 → Oro aplicado
        $result11 = $calculator->calculate(DistributorTier::Gold, [
            ['product' => $product, 'qty' => 11],
        ]);
        $this->assertTrue($result11->goldPricingApplied);
        $this->assertSame(105_600_000, $result11->effectiveTotalCents);
        $this->assertTrue($result11->checkoutAllowed());
        $this->assertSame(5_500_000, $result11->goldSavingsCents);
        $this->assertTrue($result11->lines->every(
            fn ($line) => $line->effectiveUnitPriceCents === $line->goldUnitPriceCents
        ));
    }

    public function test_gold_exact_threshold_applies_gold_prices(): void
    {
        DB::table('commerce_pricing_rules')->delete();
        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
            'silver_min_order_enabled' => false,
            'silver_min_order_amount' => 800_000,
            'gold_min_order_enabled' => false,
            'gold_min_order_amount' => 1_000_000,
            'gold_pricing_threshold_enabled' => true,
            'gold_pricing_threshold_amount' => 1_000_000,
            'gold_pricing_threshold_basis' => 'gold_candidate',
            'created_by_id' => null,
        ]);

        $product = Product::factory()->create(['price' => 100_000, 'stock' => 20, 'is_active' => true]);
        $result = app(OrderPricingCalculator::class)->calculate(DistributorTier::Gold, [
            ['product' => $product, 'qty' => 10],
        ]);

        $this->assertTrue($result->goldPricingApplied);
        $this->assertSame(100_000_000, $result->effectiveTotalCents);
        $this->assertSame(0, $result->goldPricingDecision->missingAmountCents);
        $this->assertSame('threshold_reached', $result->goldPricingDecision->reason);
        $this->assertTrue($result->checkoutAllowed());
    }

    public function test_silver_minimum_blocks_and_allows_at_exact_threshold(): void
    {
        DB::table('commerce_pricing_rules')->delete();
        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
            'silver_min_order_enabled' => true,
            'silver_min_order_amount' => 800_000,
            'gold_min_order_enabled' => false,
            'gold_min_order_amount' => 1_000_000,
            'gold_pricing_threshold_enabled' => true,
            'gold_pricing_threshold_amount' => 1_000_000,
            'gold_pricing_threshold_basis' => 'gold_candidate',
            'created_by_id' => null,
        ]);

        $calculator = app(OrderPricingCalculator::class);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 20, 'is_active' => true]);

        $blocked = $calculator->calculate(DistributorTier::Silver, [
            ['product' => $product, 'qty' => 1],
        ]);
        $this->assertFalse($blocked->checkoutAllowed());
        $this->assertSame(10_500_000, $blocked->effectiveTotalCents);
        $this->assertFalse($blocked->goldPricingApplied);

        $allowed = $calculator->calculate(DistributorTier::Silver, [
            ['product' => $product, 'qty' => 8],
        ]);
        $this->assertTrue($allowed->checkoutAllowed());
        $this->assertSame(84_000_000, $allowed->effectiveTotalCents);
        $this->assertSame('minimum_reached', $allowed->minimumOrderDecision->reason);
    }

    public function test_evaluation_basis_switch_changes_gold_eligibility(): void
    {
        $product = Product::factory()->create(['price' => 960_000, 'stock' => 10, 'is_active' => true]);

        DB::table('commerce_pricing_rules')->delete();
        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
            'silver_min_order_enabled' => false,
            'silver_min_order_amount' => 800_000,
            'gold_min_order_enabled' => false,
            'gold_min_order_amount' => 1_000_000,
            'gold_pricing_threshold_enabled' => true,
            'gold_pricing_threshold_amount' => 1_000_000,
            'gold_pricing_threshold_basis' => 'gold_candidate',
            'created_by_id' => null,
        ]);

        $byGold = app(OrderPricingCalculator::class)->calculate(DistributorTier::Gold, [
            ['product' => $product, 'qty' => 1],
        ]);
        $this->assertFalse($byGold->goldPricingApplied);

        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
            'silver_min_order_enabled' => false,
            'silver_min_order_amount' => 800_000,
            'gold_min_order_enabled' => false,
            'gold_min_order_amount' => 1_000_000,
            'gold_pricing_threshold_enabled' => true,
            'gold_pricing_threshold_amount' => 1_000_000,
            'gold_pricing_threshold_basis' => 'silver_candidate',
            'created_by_id' => null,
        ]);

        $bySilver = app(OrderPricingCalculator::class)->calculate(DistributorTier::Gold, [
            ['product' => $product, 'qty' => 1],
        ]);
        $this->assertTrue($bySilver->goldPricingApplied);
        $this->assertNotSame($byGold->goldPricingApplied, $bySilver->goldPricingApplied);
    }

    public function test_missing_amounts_never_negative(): void
    {
        $decision = app(GoldPricingEligibilityEvaluator::class)->evaluate(
            DistributorTier::Gold,
            new GoldPricingThresholdConfig(true, 100_000, GoldThresholdBasis::GoldCandidate, 1),
            silverCandidateTotalCents: 20_000_000,
            goldCandidateTotalCents: 15_000_000,
        );

        $this->assertSame(0, $decision->missingAmountCents);
        $this->assertTrue($decision->eligible);

        $min = app(MinimumOrderEvaluator::class)->evaluate(
            DistributorTier::Silver,
            50_000_000,
            new TierMinimumOrderConfig(true, 10_000_000, false, 10_000_000, 1),
        );
        $this->assertSame(0, $min->missingAmountCents);
        $this->assertGreaterThanOrEqual(0, $min->missingAmountCents);
    }
}
