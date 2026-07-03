<?php

namespace Tests\Unit;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Actions\SyncProductsFromInvenTreeAction;
use App\Modules\Inventory\Services\InvenTreeApiClient;
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
        );
    }

    public function test_execute_updates_price_and_stock_on_existing_product(): void
    {
        Product::factory()->create([
            'sku' => 'SKU-001',
            'price' => 10000,
            'stock' => 10,
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
                    'pricing_min' => '50000',
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(0, $stats['updated_price']);
        $this->assertSame(0, $stats['updated_stock']);
        $this->assertSame(1, $stats['updated_both']);
        $this->assertSame(0, $stats['skipped']);
        $this->assertSame(0, $stats['not_found']);
        $this->assertSame(0, $stats['errors']);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-001',
            'price' => 50000,
            'stock' => 100,
        ]);
    }

    public function test_execute_updates_only_price_when_stock_unchanged(): void
    {
        Product::factory()->create([
            'sku' => 'SKU-001',
            'price' => 10000,
            'stock' => 50,
        ]);

        $this->apiClient
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 1,
                    'IPN' => 'SKU-001',
                    'total_in_stock' => 50,
                    'pricing_min' => '75000',
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(0, $stats['updated_stock']);
        $this->assertSame(1, $stats['updated_price']);
        $this->assertSame(0, $stats['updated_both']);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-001',
            'price' => 75000,
            'stock' => 50,
        ]);
    }

    public function test_execute_updates_only_stock_when_price_unchanged(): void
    {
        Product::factory()->create([
            'sku' => 'SKU-001',
            'price' => 30000,
            'stock' => 10,
        ]);

        $this->apiClient
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 1,
                    'IPN' => 'SKU-001',
                    'total_in_stock' => 80,
                    'pricing_min' => '30000',
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(0, $stats['updated_price']);
        $this->assertSame(1, $stats['updated_stock']);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-001',
            'price' => 30000,
            'stock' => 80,
        ]);
    }

    public function test_execute_skips_when_nothing_changed(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SKU-001',
            'price' => 30000,
            'stock' => 50,
        ]);

        $this->apiClient
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 1,
                    'IPN' => 'SKU-001',
                    'total_in_stock' => $product->stock,
                    'pricing_min' => '30000',
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(0, $stats['updated_price']);
        $this->assertSame(0, $stats['updated_stock']);
        $this->assertSame(0, $stats['updated_both']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, $stats['not_found']);
    }

    public function test_execute_reports_not_found_when_product_does_not_exist(): void
    {
        $this->apiClient
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 1,
                    'IPN' => 'SKU-NOEXISTE',
                    'total_in_stock' => 10,
                    'pricing_min' => '10000',
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['not_found']);
        $this->assertSame(0, $stats['updated_price']);
        $this->assertSame(0, $stats['updated_stock']);
    }

    public function test_execute_skips_variant_parts(): void
    {
        $this->apiClient
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 2,
                    'IPN' => 'SKU-VAR',
                    'total_in_stock' => 10,
                    'pricing_min' => null,
                    'variant_of' => 1,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, $stats['updated_price']);
        $this->assertSame(0, $stats['updated_stock']);
    }

    public function test_execute_skips_template_parts(): void
    {
        $this->apiClient
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 3,
                    'IPN' => 'SKU-TPL',
                    'total_in_stock' => 0,
                    'pricing_min' => null,
                    'variant_of' => null,
                    'is_template' => true,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['skipped']);
    }

    public function test_execute_skips_part_with_empty_sku(): void
    {
        $this->apiClient
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 4,
                    'IPN' => '',
                    'total_in_stock' => 5,
                    'pricing_min' => null,
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $stats = $this->action->execute();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['skipped']);
    }

    public function test_execute_invokes_progress_callback(): void
    {
        Product::factory()->create(['sku' => 'SKU-005', 'price' => 100, 'stock' => 5]);
        Product::factory()->create(['sku' => 'SKU-006', 'price' => 200, 'stock' => 10]);

        $this->apiClient
            ->method('getParts')
            ->willReturn([
                [
                    'pk' => 5,
                    'IPN' => 'SKU-005',
                    'total_in_stock' => 15,
                    'pricing_min' => '150',
                    'variant_of' => null,
                    'is_template' => false,
                ],
                [
                    'pk' => 6,
                    'IPN' => 'SKU-006',
                    'total_in_stock' => 25,
                    'pricing_min' => '250',
                    'variant_of' => null,
                    'is_template' => false,
                ],
            ]);

        $calls = [];

        $this->action->execute(function (int $current, int $total, array $part) use (&$calls): void {
            $calls[] = [$current, $total, $part['pk']];
        });

        $this->assertCount(2, $calls);
        $this->assertSame([1, 2, 5], $calls[0]);
        $this->assertSame([2, 2, 6], $calls[1]);
    }
}
