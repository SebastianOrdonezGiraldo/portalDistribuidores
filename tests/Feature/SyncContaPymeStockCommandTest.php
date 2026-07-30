<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\ContaPymeInventoryService;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\ValueObjects\InventoryItemData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mockery;
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
            ->expectsOutput('DRY_OK TENS7000 contable=25 available=25')
            ->assertSuccessful();

        $this->assertSame(10.0, (float) $product->fresh()->stock);
    }

    public function test_command_writes_contapyme_accounting_stock_without_local_reservations(): void
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
            ->expectsOutput('UPDATED TENS7000 stock=25')
            ->assertSuccessful();

        $this->assertSame(25.0, (float) $product->fresh()->stock);
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
            ->expectsOutputToContain('CONTAPYME_DIAGNOSTICS: {"error_count":1,"error_groups":')
            ->assertFailed();

        $product->refresh();
        $this->assertSame(10.0, (float) $product->stock);
        $this->assertNull($product->stock_sync_status);
    }

    public function test_command_groups_repeated_failures_and_logs_each_product(): void
    {
        Product::factory()->create([
            'sku' => 'ERR-001',
            'stock' => 10,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'sku' => 'ERR-002',
            'stock' => 20,
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
                return 'Timeout de ContaPyme';
            }
        };
        $this->app->instance(ContaPymeInventoryService::class, $service);
        Log::spy();

        $this->artisan('contapyme:sync-stock --force')
            ->expectsOutputToContain('CONTAPYME_DIAGNOSTICS: {"error_count":2,"error_groups":[{"message":"Timeout de ContaPyme","count":2}],"error_details":')
            ->assertFailed();

        Log::shouldHaveReceived('error')
            ->with('contapyme.sync_failure', Mockery::type('array'))
            ->twice();
    }

    public function test_command_preserves_stock_for_a_sku_confirmed_missing_from_contapyme(): void
    {
        $product = Product::factory()->create([
            'sku' => 'MISSING-SKU',
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->app->instance(ContaPymeInventoryService::class, $this->bulkService([], [
            'MISSING-SKU' => false,
        ]));

        $this->artisan('contapyme:sync-stock --force')
            ->expectsOutput('MISSING_CONTAPYME MISSING-SKU stock=10')
            ->assertSuccessful();

        $product->refresh();
        $this->assertSame(10.0, (float) $product->stock);
        $this->assertSame('missing_contapyme', $product->stock_sync_status);
        $this->assertNull($product->stock_synced_at);
        $this->assertFalse($product->isStockManagedByContaPyme());
    }

    public function test_command_syncs_a_verified_zero_stock_product_missing_from_the_bulk_response(): void
    {
        $product = Product::factory()->create([
            'sku' => 'ZERO-SKU',
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->app->instance(ContaPymeInventoryService::class, $this->bulkService([], [
            'ZERO-SKU' => true,
        ]));

        $this->artisan('contapyme:sync-stock --force')
            ->expectsOutput('UPDATED ZERO-SKU stock=0')
            ->assertSuccessful();

        $product->refresh();
        $this->assertSame(0.0, (float) $product->stock);
        $this->assertSame('synced', $product->stock_sync_status);
        $this->assertNotNull($product->stock_synced_at);
        $this->assertTrue($product->isStockManagedByContaPyme());
    }

    public function test_dry_run_reports_a_confirmed_missing_sku_without_changing_stock(): void
    {
        $product = Product::factory()->create([
            'sku' => 'MISSING-SKU',
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->app->instance(ContaPymeInventoryService::class, $this->bulkService([], [
            'MISSING-SKU' => false,
        ]));

        $this->artisan('contapyme:sync-stock --force --dry-run')
            ->expectsOutput('DRY_MISSING MISSING-SKU local_stock=10 stock_preserved')
            ->assertSuccessful();

        $product->refresh();
        $this->assertSame(10.0, (float) $product->stock);
        $this->assertNull($product->stock_sync_status);
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
            public function productExists(string $sku): ?bool
            {
                TestCase::assertSame('TENS7000', $sku);

                return true;
            }

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
            ->expectsOutputToContain('CONTAPYME_STOCK irecurso=TENS7000 contable=25')
            ->expectsOutput('UPDATED TENS7000 stock=25')
            ->assertSuccessful();

        $this->assertSame(25.0, (float) $product->fresh()->stock);
    }

    public function test_command_queries_contapyme_even_when_sku_is_not_in_local_database(): void
    {
        $service = new class extends ContaPymeInventoryService
        {
            public function productExists(string $sku): ?bool
            {
                TestCase::assertSame('TC-23', $sku);

                return true;
            }

            public function getProductInfo(string $sku): ?InventoryItemData
            {
                TestCase::assertSame('TC-23', $sku);

                return new InventoryItemData(sku: $sku, stock: 421.0);
            }

            public function listProducts(): Collection
            {
                TestCase::fail('A point synchronization must not call the bulk endpoint.');
            }
        };
        $this->app->instance(ContaPymeInventoryService::class, $service);

        $this->artisan('contapyme:sync-stock --force --sku=TC-23 --dry-run')
            ->expectsOutput('CONTAPYME_STOCK irecurso=TC-23 contable=421')
            ->expectsOutputToContain('CONTAPYME_ONLY TC-23')
            ->assertSuccessful();
    }

    public function test_command_validates_a_sku_before_syncing_an_empty_point_balance_as_zero(): void
    {
        $product = Product::factory()->create([
            'sku' => 'ZERO-SKU',
            'stock' => 10,
            'is_active' => true,
        ]);

        $service = new class extends ContaPymeInventoryService
        {
            public function productExists(string $sku): ?bool
            {
                TestCase::assertSame('ZERO-SKU', $sku);

                return true;
            }

            public function getProductInfo(string $sku): ?InventoryItemData
            {
                TestCase::assertSame('ZERO-SKU', $sku);

                return new InventoryItemData(sku: $sku, stock: 0.0, rawData: []);
            }

            public function listProducts(): Collection
            {
                TestCase::fail('A point synchronization must not call the bulk endpoint.');
            }
        };
        $this->app->instance(ContaPymeInventoryService::class, $service);

        $this->artisan('contapyme:sync-stock --force --sku=ZERO-SKU')
            ->expectsOutputToContain('CONTAPYME_STOCK irecurso=ZERO-SKU contable=0')
            ->expectsOutput('UPDATED ZERO-SKU stock=0')
            ->assertSuccessful();

        $product->refresh();
        $this->assertSame(0.0, (float) $product->stock);
        $this->assertTrue($product->isStockManagedByContaPyme());
    }

    public function test_command_preserves_existing_state_when_sku_validation_fails(): void
    {
        $product = Product::factory()->create([
            'sku' => 'UNKNOWN-SKU',
            'stock' => 10,
            'stock_sync_status' => 'synced',
            'stock_synced_at' => now(),
            'is_active' => true,
        ]);

        $service = new class extends ContaPymeInventoryService
        {
            private bool $existenceChecked = false;

            public function listProducts(): Collection
            {
                return collect();
            }

            public function productExists(string $sku): ?bool
            {
                $this->existenceChecked = true;

                return null;
            }

            public function lastError(): ?string
            {
                return $this->existenceChecked ? 'ContaPyme unavailable' : null;
            }
        };
        $this->app->instance(ContaPymeInventoryService::class, $service);

        $this->artisan('contapyme:sync-stock --force')
            ->expectsOutput('FAILED UNKNOWN-SKU')
            ->expectsOutputToContain('CONTAPYME_DIAGNOSTICS: {"error_count":1,"error_groups":')
            ->assertFailed();

        $product->refresh();
        $this->assertSame(10.0, (float) $product->stock);
        $this->assertTrue($product->isStockManagedByContaPyme());
    }

    public function test_command_falls_back_to_default_logging_without_a_slack_webhook(): void
    {
        Log::spy();
        config(['logging.channels.slack.url' => null]);

        Product::factory()->create([
            'sku' => 'MISSING-SKU',
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->app->instance(ContaPymeInventoryService::class, $this->bulkService([], [
            'MISSING-SKU' => false,
        ]));

        $this->artisan('contapyme:sync-stock --force')
            ->expectsOutput('MISSING_CONTAPYME MISSING-SKU stock=10')
            ->assertSuccessful();

        Log::shouldHaveReceived('critical')->once();
        Log::shouldNotHaveReceived('channel');
    }

    /**
     * @param  array<string, float>  $stockBySku
     * @param  array<string, bool|null>  $existsBySku
     */
    private function bulkService(array $stockBySku, array $existsBySku = []): ContaPymeInventoryService
    {
        return new class($stockBySku, $existsBySku) extends ContaPymeInventoryService
        {
            /** @param array<string, float> $stockBySku */
            /** @param array<string, bool|null> $existsBySku */
            public function __construct(
                private readonly array $stockBySku,
                private readonly array $existsBySku,
            ) {}

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

            public function productExists(string $sku): ?bool
            {
                return $this->existsBySku[$sku] ?? false;
            }
        };
    }
}
