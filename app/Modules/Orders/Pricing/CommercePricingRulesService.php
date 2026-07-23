<?php

namespace App\Modules\Orders\Pricing;

use App\Models\User;
use App\Modules\Orders\Models\CommercePricingRule;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
    ): CommercePricingRulesPublishResult {
        if ($silverMarkupBasisPoints < 0 || $silverMarkupBasisPoints > 10_000) {
            throw new InvalidArgumentException(
                'silver_markup_basis_points debe estar entre 0 y 10000.',
            );
        }

        if ($silverRoundingMultiple <= 0) {
            throw new InvalidArgumentException(
                'silver_rounding_multiple debe ser mayor que cero.',
            );
        }

        // DTO invariants are also asserted here so invalid values never reach DB.
        new CommercePricingRules($silverMarkupBasisPoints, $silverRoundingMultiple);

        $result = DB::transaction(function () use ($silverMarkupBasisPoints, $silverRoundingMultiple, $actor): CommercePricingRulesPublishResult {
            /** @var CommercePricingRule|null $latest */
            $latest = CommercePricingRule::query()
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (
                $latest
                && (int) $latest->silver_markup_basis_points === $silverMarkupBasisPoints
                && (int) $latest->silver_rounding_multiple === $silverRoundingMultiple
            ) {
                return new CommercePricingRulesPublishResult(rule: $latest, changed: false);
            }

            $created = CommercePricingRule::query()->create([
                'silver_markup_basis_points' => $silverMarkupBasisPoints,
                'silver_rounding_multiple' => $silverRoundingMultiple,
                'created_by_id' => $actor->id,
            ]);

            return new CommercePricingRulesPublishResult(rule: $created, changed: true);
        });

        if ($result->changed) {
            $this->provider->forgetCache();
        }

        return $result;
    }
}
