<?php

namespace App\Modules\Orders\Pricing;

/**
 * Raw monthly metrics for a distributor company.
 *
 * Framing/copy belongs to the tier presentation config — this DTO only carries
 * numeric facts shared by every tier.
 */
final readonly class DistributorTierMetrics
{
    public function __construct(
        public int $savingsCents,
        public int $previousSavingsCents,
        public int $ordersCount,
        public int $previousOrdersCount,
        public int $discountPercent,
        public int $benefitsCount,
    ) {}

    public function savingsDecimal(): string
    {
        return TierPrice::centsToDecimal($this->savingsCents);
    }

    public function previousSavingsDecimal(): string
    {
        return TierPrice::centsToDecimal($this->previousSavingsCents);
    }

    public function savingsTrendPercent(): ?float
    {
        return $this->trendPercent($this->savingsCents, $this->previousSavingsCents);
    }

    public function ordersTrendPercent(): ?float
    {
        return $this->trendPercent($this->ordersCount, $this->previousOrdersCount);
    }

    private function trendPercent(int|float $current, int|float $previous): ?float
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($current === 0.0 && $previous === 0.0) {
            return null;
        }

        if ($previous === 0.0) {
            return 100.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
