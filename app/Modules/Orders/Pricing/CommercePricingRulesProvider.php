<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Orders\Models\CommercePricingRule;
use App\Modules\Shared\Enums\GoldThresholdBasis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

/**
 * Resolves commercial pricing rules from DB with config/commerce.php fallback.
 *
 * Cache stores only primitive values (never Eloquent models).
 * Commercial amount defaults come exclusively from config — never hard-coded here.
 */
final class CommercePricingRulesProvider
{
    public const CACHE_KEY = 'commerce.pricing-rules.current.v2';

    public function current(): CommercePricingRules
    {
        if (app()->environment('testing')) {
            return $this->resolveFresh();
        }

        /** @var array<string, mixed> $payload */
        $payload = Cache::remember(self::CACHE_KEY, now()->addDay(), function (): array {
            return $this->toCachePayload($this->resolveFresh());
        });

        return $this->fromPayload($payload);
    }

    public function forId(int $id): CommercePricingRules
    {
        $rule = CommercePricingRule::query()->find($id);

        if (! $rule) {
            throw new InvalidArgumentException("No existe la regla comercial #{$id}.");
        }

        return $this->fromModel($rule);
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('commerce.pricing-rules.current.v1');
    }

    private function resolveFresh(): CommercePricingRules
    {
        try {
            if (! Schema::hasTable('commerce_pricing_rules')) {
                return $this->fromConfig();
            }

            $latest = CommercePricingRule::query()
                ->orderByDesc('id')
                ->first();

            if (! $latest) {
                return $this->fromConfig();
            }

            return $this->fromModel($latest);
        } catch (Throwable) {
            return $this->fromConfig();
        }
    }

    private function fromModel(CommercePricingRule $rule): CommercePricingRules
    {
        $fallback = $this->fromConfig();

        $basis = GoldThresholdBasis::tryFrom((string) ($rule->gold_pricing_threshold_basis ?: $fallback->goldPricingThresholdBasis->value))
            ?? $fallback->goldPricingThresholdBasis;

        return new CommercePricingRules(
            silverMarkupBasisPoints: (int) $rule->silver_markup_basis_points,
            silverRoundingMultiple: (int) $rule->silver_rounding_multiple,
            silverMinOrderEnabled: (bool) ($rule->silver_min_order_enabled ?? $fallback->silverMinOrderEnabled),
            silverMinOrderAmount: (int) ($rule->silver_min_order_amount ?? $fallback->silverMinOrderAmount),
            goldMinOrderEnabled: (bool) ($rule->gold_min_order_enabled ?? $fallback->goldMinOrderEnabled),
            goldMinOrderAmount: (int) ($rule->gold_min_order_amount ?? $fallback->goldMinOrderAmount),
            goldPricingThresholdEnabled: (bool) ($rule->gold_pricing_threshold_enabled ?? $fallback->goldPricingThresholdEnabled),
            goldPricingThresholdAmount: (int) ($rule->gold_pricing_threshold_amount ?? $fallback->goldPricingThresholdAmount),
            goldPricingThresholdBasis: $basis,
            ruleId: (int) $rule->id,
        );
    }

    private function fromConfig(): CommercePricingRules
    {
        $percent = max(0, (int) config('commerce.tiers.silver_markup_percent'));
        $rounding = max(1, (int) config('commerce.tiers.silver_rounding_multiple'));
        $basis = GoldThresholdBasis::tryFrom((string) config('commerce.tiers.gold_pricing_threshold_basis'))
            ?? GoldThresholdBasis::GoldCandidate;

        return new CommercePricingRules(
            silverMarkupBasisPoints: $percent * 100,
            silverRoundingMultiple: $rounding,
            silverMinOrderEnabled: (bool) config('commerce.tiers.silver_min_order_enabled'),
            silverMinOrderAmount: max(1, (int) config('commerce.tiers.silver_min_order_amount')),
            goldMinOrderEnabled: (bool) config('commerce.tiers.gold_min_order_enabled'),
            goldMinOrderAmount: max(1, (int) config('commerce.tiers.gold_min_order_amount')),
            goldPricingThresholdEnabled: (bool) config('commerce.tiers.gold_pricing_threshold_enabled'),
            goldPricingThresholdAmount: max(1, (int) config('commerce.tiers.gold_pricing_threshold_amount')),
            goldPricingThresholdBasis: $basis,
            ruleId: null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fromPayload(array $payload): CommercePricingRules
    {
        $fallback = $this->fromConfig();
        $basis = GoldThresholdBasis::tryFrom((string) ($payload['gold_pricing_threshold_basis'] ?? $fallback->goldPricingThresholdBasis->value))
            ?? $fallback->goldPricingThresholdBasis;

        return new CommercePricingRules(
            silverMarkupBasisPoints: (int) $payload['silver_markup_basis_points'],
            silverRoundingMultiple: (int) $payload['silver_rounding_multiple'],
            silverMinOrderEnabled: (bool) ($payload['silver_min_order_enabled'] ?? $fallback->silverMinOrderEnabled),
            silverMinOrderAmount: (int) ($payload['silver_min_order_amount'] ?? $fallback->silverMinOrderAmount),
            goldMinOrderEnabled: (bool) ($payload['gold_min_order_enabled'] ?? $fallback->goldMinOrderEnabled),
            goldMinOrderAmount: (int) ($payload['gold_min_order_amount'] ?? $fallback->goldMinOrderAmount),
            goldPricingThresholdEnabled: (bool) ($payload['gold_pricing_threshold_enabled'] ?? $fallback->goldPricingThresholdEnabled),
            goldPricingThresholdAmount: (int) ($payload['gold_pricing_threshold_amount'] ?? $fallback->goldPricingThresholdAmount),
            goldPricingThresholdBasis: $basis,
            ruleId: isset($payload['rule_id']) ? (int) $payload['rule_id'] : null,
        );
    }

    /**
     * @return array<string, int|bool|string|null>
     */
    private function toCachePayload(CommercePricingRules $rules): array
    {
        return [
            'silver_markup_basis_points' => $rules->silverMarkupBasisPoints,
            'silver_rounding_multiple' => $rules->silverRoundingMultiple,
            'silver_min_order_enabled' => $rules->silverMinOrderEnabled,
            'silver_min_order_amount' => $rules->silverMinOrderAmount,
            'gold_min_order_enabled' => $rules->goldMinOrderEnabled,
            'gold_min_order_amount' => $rules->goldMinOrderAmount,
            'gold_pricing_threshold_enabled' => $rules->goldPricingThresholdEnabled,
            'gold_pricing_threshold_amount' => $rules->goldPricingThresholdAmount,
            'gold_pricing_threshold_basis' => $rules->goldPricingThresholdBasis->value,
            'rule_id' => $rules->ruleId,
        ];
    }
}
