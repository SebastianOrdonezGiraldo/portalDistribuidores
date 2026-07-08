<?php

namespace App\Modules\Shared\Contracts;

use App\Modules\Shared\ValueObjects\InventoryItemData;
use Illuminate\Support\Collection;

interface InventorySyncInterface
{
    public function testConnection(): bool;

    public function getProductInfo(string $sku): ?InventoryItemData;

    /** @return Collection<int, InventoryItemData> */
    public function listProducts(): Collection;

    public function syncProductStock(string $sku): bool;
}
