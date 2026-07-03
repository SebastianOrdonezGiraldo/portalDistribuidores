<?php

namespace Tests\Unit;

use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use App\Modules\Inventory\Actions\SyncProductsFromInvenTreeAction;
use App\Modules\Inventory\Services\InvenTreeApiClient;
use App\Modules\Inventory\Services\InvenTreeMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncProductsFromInvenTreeActionTest extends TestCase
{
    use RefreshDatabase;

    private InvenTreeApiClient $apiClient;

    private SyncProductsFromInvenTreeAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClient = $this->createMock(InvenTreeApiClient::class);
        $this->action = new SyncProductsFromInvenTreeAction(
            $this->apiClient,
            new InvenTreeMapper,
        );
    }

    public function test_execute_creates_new_products_from_parts(): void
    {
        $this->apiClient
            ->expects($this->once())
            ->method('getParts')
            ->with(['active' => true, 'category_detail' => true])
            ->willReturn([
                [
                    'pk' => 1,
                    'name' => 'Producto A',
                    'IPN' => 'SKU-001',
                    'description' => 'Descripción A',
                    'total_in_stock' => 50,
                    'active' => true,
                    'pricing_min' => '25000',
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['created']);
        $this->assertSame(0, $stats['updated']);
        $this->assertSame(0, $stats['skipped']);
        $this->assertSame(0, $stats['errors']);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-001',
            'name' => 'Producto A',
            'description' => 'Descripción A',
            'price' => 25000,
            'stock' => 50,
            'is_active' => true,
            'inventree_stock' => null,
            'reserved_stock' => 0,
        ]);
    }

    public function test_execute_updates_existing_product_when_data_changes(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SKU-001',
            'name' => 'Nombre Antiguo',
            'price' => 10000,
            'stock' => 10,
        ]);

        $this->apiClient
            ->expects($this->once())
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 1,
                    'name' => 'Nombre Nuevo',
                    'IPN' => 'SKU-001',
                    'description' => 'Descripción actualizada',
                    'total_in_stock' => 100,
                    'active' => true,
                    'pricing_min' => '50000',
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(0, $stats['created']);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame(0, $stats['skipped']);
        $this->assertSame(0, $stats['errors']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Nombre Nuevo',
            'description' => 'Descripción actualizada',
            'price' => 50000,
            'stock' => 100,
        ]);
    }

    public function test_execute_skips_existing_product_when_no_changes(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SKU-001',
            'name' => 'Producto Sin Cambios',
            'description' => 'Descripción',
            'price' => 30000,
            'is_active' => true,
        ]);

        $this->apiClient
            ->expects($this->once())
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 1,
                    'name' => 'Producto Sin Cambios',
                    'IPN' => 'SKU-001',
                    'description' => 'Descripción',
                    'total_in_stock' => $product->stock,
                    'active' => true,
                    'pricing_min' => '30000',
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(0, $stats['created']);
        $this->assertSame(0, $stats['updated']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, $stats['errors']);
    }

    public function test_execute_skips_variant_parts(): void
    {
        $this->apiClient
            ->expects($this->once())
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 2,
                    'name' => 'Variante',
                    'IPN' => 'SKU-VAR',
                    'description' => 'Es una variante',
                    'total_in_stock' => 10,
                    'active' => true,
                    'pricing_min' => null,
                    'variant_of' => 1,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, $stats['created']);
        $this->assertSame(0, $stats['updated']);
        $this->assertSame(0, $stats['errors']);

        $this->assertDatabaseMissing('products', ['sku' => 'SKU-VAR']);
    }

    public function test_execute_skips_template_parts(): void
    {
        $this->apiClient
            ->expects($this->once())
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 3,
                    'name' => 'Template Part',
                    'IPN' => 'SKU-TPL',
                    'description' => 'Es un template',
                    'total_in_stock' => 0,
                    'active' => true,
                    'pricing_min' => null,
                    'variant_of' => null,
                    'is_template' => true,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, $stats['created']);
        $this->assertSame(0, $stats['updated']);
        $this->assertSame(0, $stats['errors']);

        $this->assertDatabaseMissing('products', ['sku' => 'SKU-TPL']);
    }

    public function test_execute_skips_part_with_empty_sku(): void
    {
        $this->apiClient
            ->expects($this->once())
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 4,
                    'name' => 'Sin SKU',
                    'IPN' => '',
                    'description' => 'No tiene IPN',
                    'total_in_stock' => 5,
                    'active' => true,
                    'pricing_min' => null,
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, $stats['created']);
        $this->assertSame(0, $stats['updated']);
        $this->assertSame(0, $stats['errors']);
    }

    public function test_execute_invokes_progress_callback(): void
    {
        $this->apiClient
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 5,
                    'name' => 'Prod 1',
                    'IPN' => 'SKU-005',
                    'description' => 'Primero',
                    'total_in_stock' => 10,
                    'active' => true,
                    'pricing_min' => null,
                    'variant_of' => null,
                    'is_template' => false,
                ],
                [
                    'pk' => 6,
                    'name' => 'Prod 2',
                    'IPN' => 'SKU-006',
                    'description' => 'Segundo',
                    'total_in_stock' => 20,
                    'active' => true,
                    'pricing_min' => null,
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $calls = [];

        $stats = $this->action->execute(function (int $current, int $total, array $part) use (&$calls): void {
            $calls[] = [$current, $total, $part['pk']];
        });

        $this->assertCount(2, $calls);
        $this->assertSame([1, 2, 5], $calls[0]);
        $this->assertSame([2, 2, 6], $calls[1]);

        $this->assertSame(2, $stats['total']);
        $this->assertSame(2, $stats['created']);
    }

    public function test_execute_correctly_counts_errors_when_mapper_fails(): void
    {
        $mapper = $this->createMock(InvenTreeMapper::class);
        $mapper->expects($this->exactly(2))
            ->method('partToProductFillable')
            ->willReturnOnConsecutiveCalls(
                ['name' => 'Bueno', 'sku' => 'SKU-007', 'description' => '', 'stock' => 0, 'is_active' => true, 'price' => 0, 'category_id' => 1],
                $this->throwException(new \RuntimeException('Mapping error')),
            );

        Category::factory()->create(['id' => 1]);

        $this->apiClient
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 7,
                    'name' => 'Bueno',
                    'IPN' => 'SKU-007',
                    'description' => 'OK',
                    'total_in_stock' => 10,
                    'active' => true,
                    'pricing_min' => null,
                    'variant_of' => null,
                    'is_template' => false,
                ],
                [
                    'pk' => 8,
                    'name' => 'Malo',
                    'IPN' => 'SKU-008',
                    'description' => 'Falla',
                    'total_in_stock' => 0,
                    'active' => true,
                    'pricing_min' => null,
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $action = new SyncProductsFromInvenTreeAction($this->apiClient, $mapper);

        $stats = $action->execute();

        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['created']);
        $this->assertSame(0, $stats['updated']);
        $this->assertSame(0, $stats['skipped']);
        $this->assertSame(1, $stats['errors']);
    }
}
