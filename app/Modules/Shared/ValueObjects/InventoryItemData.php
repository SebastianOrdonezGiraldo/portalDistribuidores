<?php

namespace App\Modules\Shared\ValueObjects;

class InventoryItemData
{
    public function __construct(
        public readonly string $sku,
        public readonly ?string $name = null,
        public readonly ?float $stock = null,
        public readonly ?float $price = null,
        public readonly ?string $unit = null,
        public readonly ?bool $isActive = null,
        public readonly ?string $externalId = null,
        public readonly ?array $rawData = null,
    ) {}
}
