<?php

namespace App\Modules\Orders\Pricing;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\CommercePricingRule;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\GoldThresholdBasis;
use Illuminate\Support\Facades\DB;

/**
 * Publishes a new immutable version of commercial pricing rules when values change.
 */
final class CommercePricingRulesService
{
    public function __construct(
        private readonly CommercePricingRulesProvider $provider,
    ) {}

    public function publish(
        int $silverMarkupBasisPoints,
        int $silverRoundingMultiple,
        User $actor,
        ?bool $silverMinOrderEnabled = null,
        ?int $silverMinOrderAmount = null,
        ?bool $goldMinOrderEnabled = null,
        ?int $goldMinOrderAmount = null,
        ?bool $goldPricingThresholdEnabled = null,
        ?int $goldPricingThresholdAmount = null,
        ?GoldThresholdBasis $goldPricingThresholdBasis = null,
    ): CommercePricingRulesPublishResult {
        $bootstrap = $this->provider->current();

        $silverMinOrderEnabled ??= $bootstrap->silverMinOrderEnabled;
        $silverMinOrderAmount ??= $bootstrap->silverMinOrderAmount;
        $goldMinOrderEnabled ??= $bootstrap->goldMinOrderEnabled;
        $goldMinOrderAmount ??= $bootstrap->goldMinOrderAmount;
        $goldPricingThresholdEnabled ??= $bootstrap->goldPricingThresholdEnabled;
        $goldPricingThresholdAmount ??= $bootstrap->goldPricingThresholdAmount;
        $goldPricingThresholdBasis ??= $bootstrap->goldPricingThresholdBasis;

        // DTO invariants are also asserted here so invalid values never reach DB.
        new CommercePricingRules(
            silverMarkupBasisPoints: $silverMarkupBasisPoints,
            silverRoundingMultiple: $silverRoundingMultiple,
            silverMinOrderEnabled: $silverMinOrderEnabled,
            silverMinOrderAmount: $silverMinOrderAmount,
            goldMinOrderEnabled: $goldMinOrderEnabled,
            goldMinOrderAmount: $goldMinOrderAmount,
            goldPricingThresholdEnabled: $goldPricingThresholdEnabled,
            goldPricingThresholdAmount: $goldPricingThresholdAmount,
            goldPricingThresholdBasis: $goldPricingThresholdBasis,
        );

        $result = DB::transaction(function () use (
            $silverMarkupBasisPoints,
            $silverRoundingMultiple,
            $actor,
            $silverMinOrderEnabled,
            $silverMinOrderAmount,
            $goldMinOrderEnabled,
            $goldMinOrderAmount,
            $goldPricingThresholdEnabled,
            $goldPricingThresholdAmount,
            $goldPricingThresholdBasis,
        ): CommercePricingRulesPublishResult {
            /** @var CommercePricingRule|null $latest */
            $latest = CommercePricingRule::query()
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (
                $latest
                && (int) $latest->silver_markup_basis_points === $silverMarkupBasisPoints
                && (int) $latest->silver_rounding_multiple === $silverRoundingMultiple
                && (bool) $latest->silver_min_order_enabled === $silverMinOrderEnabled
                && (int) $latest->silver_min_order_amount === $silverMinOrderAmount
                && (bool) $latest->gold_min_order_enabled === $goldMinOrderEnabled
                && (int) $latest->gold_min_order_amount === $goldMinOrderAmount
                && (bool) $latest->gold_pricing_threshold_enabled === $goldPricingThresholdEnabled
                && (int) $latest->gold_pricing_threshold_amount === $goldPricingThresholdAmount
                && (string) $latest->gold_pricing_threshold_basis === $goldPricingThresholdBasis->value
            ) {
                return new CommercePricingRulesPublishResult(rule: $latest, changed: false);
            }

            $created = CommercePricingRule::query()->create([
                'silver_markup_basis_points' => $silverMarkupBasisPoints,
                'silver_rounding_multiple' => $silverRoundingMultiple,
                'silver_min_order_enabled' => $silverMinOrderEnabled,
                'silver_min_order_amount' => $silverMinOrderAmount,
                'gold_min_order_enabled' => $goldMinOrderEnabled,
                'gold_min_order_amount' => $goldMinOrderAmount,
                'gold_pricing_threshold_enabled' => $goldPricingThresholdEnabled,
                'gold_pricing_threshold_amount' => $goldPricingThresholdAmount,
                'gold_pricing_threshold_basis' => $goldPricingThresholdBasis->value,
                'created_by_id' => $actor->id,
            ]);

            return new CommercePricingRulesPublishResult(rule: $created, changed: true);
        });

        if ($result->changed) {
            $this->provider->forgetCache();
            $this->refreshLegacySilverPrices($result->rule);
        }

        return $result;
    }

    /**
     * Keep pre-ContaPyme catalog rows usable while they are being migrated.
     * Explicitly synchronized rows are never overwritten by an admin rule.
     */
    private function refreshLegacySilverPrices(CommercePricingRule $rule): void
    {
        $rules = new CommercePricingRules(
            silverMarkupBasisPoints: (int) $rule->silver_markup_basis_points,
            silverRoundingMultiple: (int) $rule->silver_rounding_multiple,
            silverMinOrderEnabled: (bool) $rule->silver_min_order_enabled,
            silverMinOrderAmount: (int) $rule->silver_min_order_amount,
            goldMinOrderEnabled: (bool) $rule->gold_min_order_enabled,
            goldMinOrderAmount: (int) $rule->gold_min_order_amount,
            goldPricingThresholdEnabled: (bool) $rule->gold_pricing_threshold_enabled,
            goldPricingThresholdAmount: (int) $rule->gold_pricing_threshold_amount,
            goldPricingThresholdBasis: GoldThresholdBasis::from((string) $rule->gold_pricing_threshold_basis),
            ruleId: (int) $rule->id,
        );
        $calculator = new DistributorPriceCalculator($rules);

        Product::query()
            ->whereNull('price_synced_at')
            ->whereNotNull('silver_price')
            ->chunkById(100, function ($products) use ($calculator): void {
                foreach ($products as $product) {
                    $product->forceFill([
                        'silver_price' => $calculator
                            ->calculateFromDecimal((string) $product->price, DistributorTier::Silver)
                            ->silverPriceDecimal(),
                    ])->saveQuietly();
                }
            });

        ProductVariant::query()
            ->whereNull('price_synced_at')
            ->whereNotNull('silver_price')
            ->chunkById(100, function ($variants) use ($calculator): void {
                foreach ($variants as $variant) {
                    $variant->forceFill([
                        'silver_price' => $calculator
                            ->calculateFromDecimal((string) $variant->price, DistributorTier::Silver)
                            ->silverPriceDecimal(),
                    ])->saveQuietly();
                }
            });
    }
}
