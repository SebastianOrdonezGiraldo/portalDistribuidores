<?php

namespace App\Modules\Orders\Pricing;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;

/**
 * A validated and priced order line.
 *
 * This is the single source of truth used by CreateOrderAction both to compute
 * the order total and to persist the order items, so the total and the stored
 * lines can never diverge.
 */
final readonly class PricedOrderLine
{
    public function __construct(
        public Product $product,
        public ?ProductVariant $variant,
        public int $qty,
        public string $unitLabel,
        public TierPrice $price,
    ) {}

    public function subtotalCents(): int
    {
        return $this->price->effectivePriceCents * $this->qty;
    }

    public function lineSavingsCents(): int
    {
        return $this->price->unitSavingsCents * $this->qty;
    }

    public function subtotalDecimal(): string
    {
        return TierPrice::centsToDecimal($this->subtotalCents());
    }

    public function lineSavingsDecimal(): string
    {
        return TierPrice::centsToDecimal($this->lineSavingsCents());
    }
}
