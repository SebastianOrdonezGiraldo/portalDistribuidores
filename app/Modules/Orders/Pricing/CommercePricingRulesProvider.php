<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Orders\Models\CommercePricingRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Resolves the current commercial pricing rules from DB with config fallback.
 *
 * Cache stores only primitive integers (never Eloquent models).
 */
final class CommercePricingRulesProvider
{
    public const CACHE_KEY = 'commerce.pricing-rules.current.v1';

    public function current(): CommercePricingRules
    {
        if (app()->environment('testing')) {
            return $this->resolveFresh();
        }

        /** @var array{silver_markup_basis_points: int, silver_rounding_multiple: int} $payload */
        $payload = Cache::remember(self::CACHE_KEY, now()->addDay(), function (): array {
            return $this->toCachePayload($this->resolveFresh());
        });

        return new CommercePricingRules(
            silverMarkupBasisPoints: (int) $payload['silver_markup_basis_points'],
            silverRoundingMultiple: (int) $payload['silver_rounding_multiple'],
        );
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function resolveFresh(): CommercePricingRules
    {
        try {
            if (! Schema::hasTable('commerce_pricing_rules')) {
                return $this->fromConfig();
            }

            $latest = CommercePricingRule::query()
                ->orderByDesc('id')
                ->first(['silver_markup_basis_points', 'silver_rounding_multiple']);

            if (! $latest) {
                return $this->fromConfig();
            }

            return new CommercePricingRules(
                silverMarkupBasisPoints: (int) $latest->silver_markup_basis_points,
                silverRoundingMultiple: (int) $latest->silver_rounding_multiple,
            );
        } catch (Throwable) {
            return $this->fromConfig();
        }
    }

    private function fromConfig(): CommercePricingRules
    {
        $percent = max(0, (int) config('commerce.tiers.silver_markup_percent', 5));
        $rounding = max(1, (int) config('commerce.tiers.silver_rounding_multiple', 1000));

        return new CommercePricingRules(
            silverMarkupBasisPoints: $percent * 100,
            silverRoundingMultiple: $rounding,
        );
    }

    /**
     * @return array{silver_markup_basis_points: int, silver_rounding_multiple: int}
     */
    private function toCachePayload(CommercePricingRules $rules): array
    {
        return [
            'silver_markup_basis_points' => $rules->silverMarkupBasisPoints,
            'silver_rounding_multiple' => $rules->silverRoundingMultiple,
        ];
    }
}
