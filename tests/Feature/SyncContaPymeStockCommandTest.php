<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Inventory\Services\ContaPymeInventoryService;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\ValueObjects\InventoryItemData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
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

        $this->app->instance(ContaPymeInventoryService::class, $this->bulkService([
            'TENS7000' => 25.0,
        ]));

        $this->artisan('contapyme:sync-stock --force')
            ->expectsOutput('UPDATED TENS7000 stock=25')
            ->assertSuccessful();

        $product->refresh();
        $this->assertSame(25.0, (float) $product->stock);
        $this->assertSame('synced', $product->stock_sync_status);
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
            public function listProducts(): Collection
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

        $this->app->instance(ContaPymeInventoryService::class, $this->bulkService([
            'TENS7000' => 25.0,
        ]));

        $this->artisan('contapyme:sync-stock --force --dry-run')
            ->expectsOutput('DRY_OK TENS7000 physical=25 reserved=0 available=25')
            ->assertSuccessful();

        $this->assertSame(10.0, (float) $product->fresh()->stock);
    }

    public function test_command_reduces_contapyme_physical_stock_by_local_order_reservations(): void
    {
        $product = Product::factory()->create([
            'sku' => 'TENS7000',
            'stock' => 30,
            'is_active' => true,
        ]);
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'qty' => 4,
        ]);

        $this->app->instance(ContaPymeInventoryService::class, $this->bulkService([
            'TENS7000' => 25.0,
        ]));

        $this->artisan('contapyme:sync-stock --force')
            ->expectsOutput('UPDATED TENS7000 stock=21')
            ->assertSuccessful();

        $this->assertSame(21.0, (float) $product->fresh()->stock);
    }

    public function test_command_keeps_existing_stock_when_the_bulk_api_fails(): void
    {
        $product = Product::factory()->create([
            'sku' => 'TENS7000',
            'stock' => 10,
            'is_active' => true,
        ]);

        $service = new class extends ContaPymeInventoryService
        {
            public function listProducts(): Collection
            {
                return collect();
            }

            public function lastError(): ?string
            {
                return 'ContaPyme unavailable';
            }
        };
        $this->app->instance(ContaPymeInventoryService::class, $service);

        $this->artisan('contapyme:sync-stock --force')
            ->expectsOutput('CONTAPYME_ERROR: la sincronizacion masiva fallo; no se modifico stock local.')
            ->assertFailed();

        $product->refresh();
        $this->assertSame(10.0, (float) $product->stock);
        $this->assertNull($product->stock_sync_status);
    }

    public function test_command_marks_missing_contapyme_skus_with_zero_stock(): void
    {
        $product = Product::factory()->create([
            'sku' => 'MISSING-SKU',
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->app->instance(ContaPymeInventoryService::class, $this->bulkService([]));

        $this->artisan('contapyme:sync-stock --force')
            ->expectsOutput('UPDATED MISSING-SKU stock=0')
            ->assertSuccessful();

        $product->refresh();
        $this->assertSame(0.0, (float) $product->stock);
        $this->assertSame('missing_contapyme', $product->stock_sync_status);
    }

    public function test_command_uses_the_point_endpoint_when_a_sku_is_requested(): void
    {
        $product = Product::factory()->create([
            'sku' => 'TENS7000',
            'stock' => 10,
            'is_active' => true,
        ]);

        $service = new class extends ContaPymeInventoryService
        {
            public function getProductInfo(string $sku): ?InventoryItemData
            {
                TestCase::assertSame('TENS7000', $sku);

                return new InventoryItemData(sku: $sku, stock: 25.0);
            }

            public function listProducts(): Collection
            {
                TestCase::fail('A point synchronization must not call the bulk endpoint.');
            }
        };
        $this->app->instance(ContaPymeInventoryService::class, $service);

        $this->artisan('contapyme:sync-stock --force --sku=TENS7000')
            ->expectsOutput('UPDATED TENS7000 stock=25')
            ->assertSuccessful();

        $this->assertSame(25.0, (float) $product->fresh()->stock);
    }

    /**
     * @param  array<string, float>  $stockBySku
     */
    private function bulkService(array $stockBySku): ContaPymeInventoryService
    {
        return new class($stockBySku) extends ContaPymeInventoryService
        {
            /** @param array<string, float> $stockBySku */
            public function __construct(private readonly array $stockBySku) {}

            public function listProducts(): Collection
            {
                return collect($this->stockBySku)
                    ->map(fn (float $stock, string $sku): InventoryItemData => new InventoryItemData(
                        sku: $sku,
                        stock: $stock,
                        externalId: $sku,
                    ))
                    ->values();
            }
        };
    }
}
