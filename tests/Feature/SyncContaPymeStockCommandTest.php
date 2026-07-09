<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Inventory\Services\ContaPymeInventoryService;
use App\Modules\Shared\ValueObjects\InventoryItemData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncContaPymeStockCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_syncs_simple_active_products_when_forced(): void
    {
        $product = Product::factory()->create([
            'sku' => 'TENS7000',
            'stock' => 10,
            'is_active' => true,
        ]);

        $service = new class($product->id) extends ContaPymeInventoryService
        {
            public function __construct(private readonly int $expectedProductId) {}

            public function syncProduct(Product $product): array
            {
                TestCase::assertSame($this->expectedProductId, $product->id);

                return [
                    'status' => 'updated',
                    'changed' => true,
                    'stock' => 25.0,
                ];
            }
        };

        $this->app->instance(ContaPymeInventoryService::class, $service);

        $this->artisan('contapyme:sync-stock --force')
            ->expectsOutput('UPDATED TENS7000 stock=25')
            ->assertSuccessful();
    }

    public function test_command_skips_products_with_active_variants(): void
    {
        $attribute = ProductAttribute::factory()->create();
        $value = ProductAttributeValue::factory()->create([
            'product_attribute_id' => $attribute->id,
        ]);
        $product = Product::factory()->create([
            'sku' => 'VARIANT-SKU',
            'variant_attribute_id' => $attribute->id,
            'stock' => 10,
            'is_active' => true,
        ]);
        $product->variants()->create([
            'product_attribute_value_id' => $value->id,
            'price' => 10000,
            'stock' => 10,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $service = new class extends ContaPymeInventoryService
        {
            public function syncProduct(Product $product): array
            {
                TestCase::fail('Products with variants should not be synchronized.');
            }
        };
        $this->app->instance(ContaPymeInventoryService::class, $service);

        $this->artisan('contapyme:sync-stock --force')
            ->expectsOutput('SKIP_VARIANTS VARIANT-SKU')
            ->assertSuccessful();

        $this->assertSame('skipped_variants', $product->fresh()->stock_sync_status);
    }

    public function test_dry_run_queries_contapyme_without_writing_stock(): void
    {
        $product = Product::factory()->create([
            'sku' => 'TENS7000',
            'stock' => 10,
            'is_active' => true,
        ]);

        $service = new class extends ContaPymeInventoryService
        {
            public function getProductInfo(string $sku): InventoryItemData
            {
                TestCase::assertSame('TENS7000', $sku);

                return new InventoryItemData(sku: 'TENS7000', stock: 25.0);
            }

            public function syncProduct(Product $product): array
            {
                TestCase::fail('Dry run should not persist stock.');
            }
        };

        $this->app->instance(ContaPymeInventoryService::class, $service);

        $this->artisan('contapyme:sync-stock --force --dry-run')
            ->expectsOutput('DRY_OK TENS7000 stock=25')
            ->assertSuccessful();

        $this->assertSame(10.0, (float) $product->fresh()->stock);
    }
}
