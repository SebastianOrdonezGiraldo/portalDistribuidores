<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;

/**
 * Immutable priced line with dual candidates and the selected effective price.
 */
final readonly class OrderPricingLine
{
    public function __construct(
        public Product $product,
        public ?ProductVariant $variant,
        public int $qty,
        public string $unitLabel,
        public int $silverUnitPriceCents,
        public int $goldUnitPriceCents,
        public int $effectiveUnitPriceCents,
        public int $silverSubtotalCents,
        public int $goldSubtotalCents,
        public int $effectiveSubtotalCents,
        public int $savingCents,
    ) {}

    public function silverUnitPriceDecimal(): string
    {
        return TierPrice::centsToDecimal($this->silverUnitPriceCents);
    }

    public function goldUnitPriceDecimal(): string
    {
        return TierPrice::centsToDecimal($this->goldUnitPriceCents);
    }

    public function effectiveUnitPriceDecimal(): string
    {
        return TierPrice::centsToDecimal($this->effectiveUnitPriceCents);
    }

    public function effectiveSubtotalDecimal(): string
    {
        return TierPrice::centsToDecimal($this->effectiveSubtotalCents);
    }

    public function unitSavingsCents(): int
    {
        return max(0, $this->silverUnitPriceCents - $this->effectiveUnitPriceCents);
    }

    public function lineSavingsCents(): int
    {
        return $this->savingCents;
    }

    public function unitSavingsDecimal(): string
    {
        return TierPrice::centsToDecimal($this->unitSavingsCents());
    }

    public function lineSavingsDecimal(): string
    {
        return TierPrice::centsToDecimal($this->lineSavingsCents());
    }
}
