<?php

namespace Tests\Unit;

use App\Modules\Inventory\Actions\SyncProductsFromInvenTreeAction;
use App\Modules\Inventory\Actions\SyncStockFromInvenTreeAction;
use App\Modules\Inventory\Services\InvenTreeApiClient;
use App\Modules\Inventory\Services\InvenTreeSyncService;
use Tests\TestCase;

class InvenTreeSyncServiceTest extends TestCase
{
    public function test_sync_all_products_runs_only_products_phase(): void
    {
        $apiClient = $this->createMock(InvenTreeApiClient::class);
        $syncProducts = $this->createMock(SyncProductsFromInvenTreeAction::class);
        $syncStock = $this->createMock(SyncStockFromInvenTreeAction::class);

        $syncProducts->expects($this->once())
            ->method('execute')
            ->willReturn(['total' => 1]);

        $syncStock->expects($this->never())
            ->method('execute');

        $service = new InvenTreeSyncService($apiClient, $syncProducts, $syncStock);

        $results = $service->syncAll('products');

        $this->assertSame(1, $results['products']['total']);
        $this->assertSame(0, $results['stock']['total']);
    }

    public function test_sync_all_stock_runs_only_stock_phase(): void
    {
        $apiClient = $this->createMock(InvenTreeApiClient::class);
        $syncProducts = $this->createMock(SyncProductsFromInvenTreeAction::class);
        $syncStock = $this->createMock(SyncStockFromInvenTreeAction::class);

        $syncProducts->expects($this->never())
            ->method('execute');

        $syncStock->expects($this->once())
            ->method('execute')
            ->willReturn(['total' => 1]);

        $service = new InvenTreeSyncService($apiClient, $syncProducts, $syncStock);

        $results = $service->syncAll('stock');

        $this->assertSame(0, $results['products']['total']);
        $this->assertSame(1, $results['stock']['total']);
    }
}
