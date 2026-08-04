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
            DistributorTier::Gold,
            73_500_000,
            new TierMinimumOrderConfig(false, 100_000_000, true, 80_000_000, 1),
        );

        $this->assertFalse($decision->allowed);
        $this->assertSame('minimum_not_reached', $decision->reason);
        $this->assertSame(6_500_000, $decision->missingAmountCents);
    }

    public function test_order_pricing_calculator_gold_examples_from_plan(): void
    {
        DB::table('commerce_pricing_rules')->delete();
        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
            'silver_min_order_enabled' => false,
            'silver_min_order_amount' => 1_000_000,
            'gold_min_order_enabled' => true,
            'gold_min_order_amount' => 800_000,
            'gold_pricing_threshold_enabled' => true,
            'gold_pricing_threshold_amount' => 1_000_000,
            'gold_pricing_threshold_basis' => 'gold_candidate',
            'created_by_id' => null,
        ]);

        $calculator = app(OrderPricingCalculator::class);

        // Example 1: gold candidate 700k → plata effective 735k → checkout blocked
        $productLow = Product::factory()->create(['price' => 700_000, 'stock' => 10, 'is_active' => true]);
        $resultLow = $calculator->calculate(DistributorTier::Gold, [
            ['product' => $productLow, 'qty' => 1],
        ]);
        $this->assertFalse($resultLow->goldPricingApplied);
        $this->assertSame(73_500_000, $resultLow->effectiveTotalCents);
        $this->assertFalse($resultLow->checkoutAllowed());

        // Example 2: gold candidate 850k → plata ~893k (5% + redondeo) → checkout allowed, pays plata
        $productMid = Product::factory()->create(['price' => 850_000, 'stock' => 10, 'is_active' => true]);
        $resultMid = $calculator->calculate(DistributorTier::Gold, [
            ['product' => $productMid, 'qty' => 1],
        ]);
        $this->assertFalse($resultMid->goldPricingApplied);
        $this->assertSame(89_300_000, $resultMid->effectiveTotalCents);
        $this->assertTrue($resultMid->checkoutAllowed());

        // Example 3: gold candidate 1.05M → gold applied, checkout allowed
        $productHigh = Product::factory()->create(['price' => 1_050_000, 'stock' => 10, 'is_active' => true]);
        $resultHigh = $calculator->calculate(DistributorTier::Gold, [
            ['product' => $productHigh, 'qty' => 1],
        ]);
        $this->assertTrue($resultHigh->goldPricingApplied);
        $this->assertSame(105_000_000, $resultHigh->effectiveTotalCents);
        $this->assertTrue($resultHigh->checkoutAllowed());
    }

    public function test_silver_minimum_blocks_and_allows_at_exact_threshold(): void
    {
        DB::table('commerce_pricing_rules')->delete();
        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
            'silver_min_order_enabled' => true,
            'silver_min_order_amount' => 200_000,
            'gold_min_order_enabled' => false,
            'gold_min_order_amount' => 1_000_000,
            'gold_pricing_threshold_enabled' => false,
            'gold_pricing_threshold_amount' => 1_000_000,
            'gold_pricing_threshold_basis' => 'gold_candidate',
            'created_by_id' => null,
        ]);

        $calculator = app(OrderPricingCalculator::class);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 10, 'is_active' => true]);

        $blocked = $calculator->calculate(DistributorTier::Silver, [
            ['product' => $product, 'qty' => 1],
        ]);
        $this->assertFalse($blocked->checkoutAllowed());
        $this->assertSame(10_500_000, $blocked->effectiveTotalCents);
        $this->assertFalse($blocked->goldPricingApplied);

        $allowed = $calculator->calculate(DistributorTier::Silver, [
            ['product' => $product, 'qty' => 2],
        ]);
        $this->assertTrue($allowed->checkoutAllowed());
        $this->assertSame(21_000_000, $allowed->effectiveTotalCents);
        $this->assertSame('minimum_reached', $allowed->minimumOrderDecision->reason);
    }

    public function test_gold_threshold_exact_equality_applies_gold_prices(): void
    {
        DB::table('commerce_pricing_rules')->delete();
        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
            'silver_min_order_enabled' => false,
            'silver_min_order_amount' => 1_000_000,
            'gold_min_order_enabled' => false,
            'gold_min_order_amount' => 1_000_000,
            'gold_pricing_threshold_enabled' => true,
            'gold_pricing_threshold_amount' => 500_000,
            'gold_pricing_threshold_basis' => 'gold_candidate',
            'created_by_id' => null,
        ]);

        $product = Product::factory()->create(['price' => 500_000, 'stock' => 10, 'is_active' => true]);
        $result = app(OrderPricingCalculator::class)->calculate(DistributorTier::Gold, [
            ['product' => $product, 'qty' => 1],
        ]);

        $this->assertTrue($result->goldPricingApplied);
        $this->assertSame(50_000_000, $result->effectiveTotalCents);
        $this->assertSame(0, $result->goldPricingDecision->missingAmountCents);
        $this->assertGreaterThan(0, $result->goldSavingsCents);
        $this->assertTrue($result->lines->every(
            fn ($line) => $line->effectiveUnitPriceCents === $line->goldUnitPriceCents
        ));
    }

    public function test_gold_pricing_applied_but_minimum_still_blocks_checkout(): void
    {
        DB::table('commerce_pricing_rules')->delete();
        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
            'silver_min_order_enabled' => false,
            'silver_min_order_amount' => 1_000_000,
            'gold_min_order_enabled' => true,
            'gold_min_order_amount' => 2_000_000,
            'gold_pricing_threshold_enabled' => true,
            'gold_pricing_threshold_amount' => 500_000,
            'gold_pricing_threshold_basis' => 'gold_candidate',
            'created_by_id' => null,
        ]);

        $product = Product::factory()->create(['price' => 600_000, 'stock' => 10, 'is_active' => true]);
        $result = app(OrderPricingCalculator::class)->calculate(DistributorTier::Gold, [
            ['product' => $product, 'qty' => 1],
        ]);

        $this->assertTrue($result->goldPricingApplied);
        $this->assertSame(60_000_000, $result->effectiveTotalCents);
        $this->assertFalse($result->checkoutAllowed());
        $this->assertSame('minimum_not_reached', $result->minimumOrderDecision->reason);
    }

    public function test_evaluation_basis_switch_changes_gold_eligibility(): void
    {
        $product = Product::factory()->create(['price' => 960_000, 'stock' => 10, 'is_active' => true]);
        // Plata ≈ 1.008.000 (5% + redondeo), Oro = 960.000
        // threshold 1_000_000: gold_candidate → no; silver_candidate → yes

        DB::table('commerce_pricing_rules')->delete();
        CommercePricingRule::query()->create([
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
            'silver_min_order_enabled' => false,
            'silver_min_order_amount' => 1_000_000,
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
            'silver_min_order_amount' => 1_000_000,
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
