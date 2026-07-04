<?php

namespace Tests\Unit;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Actions\SyncStockFromInvenTreeAction;
use App\Modules\Inventory\Services\InvenTreeApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncStockFromInvenTreeActionTest extends TestCase
{
    use RefreshDatabase;

    private InvenTreeApiClient $apiClient;

    private SyncStockFromInvenTreeAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.inventree.sku_field' => 'IPN']);

        $this->apiClient = $this->createMock(InvenTreeApiClient::class);
        $this->action = new SyncStockFromInvenTreeAction($this->apiClient);
    }

    public function test_execute_updates_stock_for_matching_product(): void
    {
        Product::factory()->create([
            'sku' => 'SKU-001',
            'inventree_stock' => null,
            'reserved_stock' => 0,
            'stock' => 0,
        ]);

        $this->apiClient
            ->expects($this->once())
            ->method('getParts')
            ->with(['active' => true, 'category_detail' => true])
            ->willReturn([
                [
                    'pk' => 1,
                    'name' => 'Producto A',
                    'IPN' => 'SKU-001',
                    'total_in_stock' => 100,
                    'active' => true,
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['matched']);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame(0, $stats['unmatched']);
        $this->assertSame(0, $stats['errors']);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-001',
            'inventree_stock' => 100,
            'reserved_stock' => 0,
            'stock' => 100,
        ]);
    }

    public function test_execute_computes_stock_as_inventree_stock_minus_reserved(): void
    {
        Product::factory()->create([
            'sku' => 'SKU-002',
            'inventree_stock' => null,
            'reserved_stock' => 15,
            'stock' => 0,
        ]);

        $this->apiClient
            ->expects($this->once())
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 2,
                    'name' => 'Con reservas',
                    'IPN' => 'SKU-002',
                    'total_in_stock' => 100,
                    'active' => true,
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $this->action->execute();

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-002',
            'inventree_stock' => 100,
            'reserved_stock' => 15,
            'stock' => 85,
        ]);
    }

    public function test_execute_skips_when_inventree_stock_unchanged(): void
    {
        Product::factory()->create([
            'sku' => 'SKU-003',
            'inventree_stock' => 50,
            'reserved_stock' => 5,
            'stock' => 45,
        ]);

        $this->apiClient
            ->expects($this->once())
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 3,
                    'name' => 'Sin cambios',
                    'IPN' => 'SKU-003',
                    'total_in_stock' => 50,
                    'active' => true,
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['matched']);
        $this->assertSame(0, $stats['updated']);
        $this->assertSame(0, $stats['errors']);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-003',
            'inventree_stock' => 50,
            'reserved_stock' => 5,
            'stock' => 45,
        ]);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_execute_skips_variant_parts(): void
    {
        $this->apiClient
            ->expects($this->once())
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 4,
                    'name' => 'Variante',
                    'IPN' => 'SKU-VAR',
                    'total_in_stock' => 10,
                    'active' => true,
                    'variant_of' => 1,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['skipped_variants']);
        $this->assertSame(0, $stats['matched']);
        $this->assertSame(0, $stats['updated']);
    }

    public function test_execute_skips_products_with_configurable_variants(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SKU-004',
            'inventree_stock' => null,
            'reserved_stock' => 0,
            'stock' => 0,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'stock' => 5,
        ]);

        $this->apiClient
            ->expects($this->once())
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 5,
                    'name' => 'Configurable',
                    'IPN' => 'SKU-004',
                    'total_in_stock' => 100,
                    'active' => true,
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['skipped_variants']);
        $this->assertSame(0, $stats['unmatched']);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-004',
            'inventree_stock' => null,
            'stock' => 0,
        ]);
    }

    public function test_execute_skips_unmatched_sku(): void
    {
        $this->apiClient
            ->expects($this->once())
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 6,
                    'name' => 'No existe',
                    'IPN' => 'SKU-NONEXISTENT',
                    'total_in_stock' => 10,
                    'active' => true,
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(0, $stats['matched']);
        $this->assertSame(1, $stats['unmatched']);
        $this->assertSame(0, $stats['updated']);
    }

    public function test_execute_records_stock_movement_on_update(): void
    {
        Product::factory()->create([
            'sku' => 'SKU-005',
            'inventree_stock' => 30,
            'reserved_stock' => 5,
            'stock' => 25,
        ]);

        $this->apiClient
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 7,
                    'name' => 'Con movimiento',
                    'IPN' => 'SKU-005',
                    'total_in_stock' => 80,
                    'active' => true,
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $this->action->execute();

        $this->assertDatabaseHas('stock_movements', [
            'source' => 'inventree_sync',
            'previous_stock' => 25,
            'new_stock' => 75,
            'delta' => 50,
        ]);
    }

    public function test_execute_handles_empty_inventree_stock_as_unmatched(): void
    {
        $this->apiClient
            ->expects($this->once())
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 8,
                    'name' => 'Sin stock',
                    'IPN' => 'SKU-008',
                    'total_in_stock' => null,
                    'active' => true,
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(0, $stats['matched']);
        $this->assertSame(1, $stats['unmatched']);
    }

    public function test_execute_invokes_progress_callback(): void
    {
        Product::factory()->create(['sku' => 'SKU-010']);
        Product::factory()->create(['sku' => 'SKU-011']);

        $this->apiClient
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 10,
                    'name' => 'P1',
                    'IPN' => 'SKU-010',
                    'total_in_stock' => 5,
                    'active' => true,
                    'variant_of' => null,
                    'is_template' => false,
                ],
                [
                    'pk' => 11,
                    'name' => 'P2',
                    'IPN' => 'SKU-011',
                    'total_in_stock' => 10,
                    'active' => true,
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $calls = [];

        $this->action->execute(function (int $current, int $total, array $part) use (&$calls): void {
            $calls[] = [$current, $total, $part['pk']];
        });

        $this->assertCount(2, $calls);
        $this->assertSame([1, 2, 10], $calls[0]);
        $this->assertSame([2, 2, 11], $calls[1]);
    }
}
