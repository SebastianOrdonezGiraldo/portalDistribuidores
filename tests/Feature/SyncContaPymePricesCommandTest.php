<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\ContaPymePriceService;
use App\Modules\Inventory\ValueObjects\ContaPymePriceLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncContaPymePricesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_products_with_sku_are_selected_and_prices_are_saved(): void
    {
        $active = Product::factory()->create(['sku' => 'SKU-A', 'price' => '10.00', 'silver_price' => null]);
        Product::factory()->create(['sku' => 'SKU-INACTIVE', 'is_active' => false]);
        Product::factory()->create(['sku' => '', 'is_active' => true]);
        $this->fakePrices(['10.00', '12.00']);

        $this->artisan('contapyme:sync-prices --force')->assertSuccessful();

        $active->refresh();
        $this->assertSame('10.00', $active->price);
        $this->assertSame('12.00', $active->silver_price);
        $this->assertSame('synced', $active->price_sync_status);
    }

    public function test_unchanged_values_do_not_change_last_sync_timestamp(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SKU-A', 'price' => '10.00', 'silver_price' => '12.00',
            'price_sync_status' => 'synced', 'price_synced_at' => now()->subDay(),
        ]);
        $previous = $product->price_synced_at;
        $this->fakePrices(['10.00', '12.00']);

        $this->artisan('contapyme:sync-prices --force')->assertSuccessful();

        $this->assertSame($previous->toDateTimeString(), $product->fresh()->price_synced_at->toDateTimeString());
    }

    public function test_missing_and_anomalous_values_preserve_last_valid_prices(): void
    {
        $missing = Product::factory()->create(['sku' => 'SKU-M', 'price' => '10.00', 'silver_price' => '12.00']);
        $anomaly = Product::factory()->create(['sku' => 'SKU-X', 'price' => '10.00', 'silver_price' => '12.00']);
        $mock = Mockery::mock(ContaPymePriceService::class);
        $mock->shouldReceive('calculatedPrice')->times(4)->andReturnUsing(function (string $sku, string $method, string $list) {
            if ($sku === 'SKU-M') {
                return ContaPymePriceLookup::missing('no existe');
            }

            return $list === '1' ? ContaPymePriceLookup::ok('15.00') : ContaPymePriceLookup::ok('14.00');
        });
        $this->app->instance(ContaPymePriceService::class, $mock);

        $this->artisan('contapyme:sync-prices --force')->assertSuccessful();

        $this->assertSame('12.00', $missing->fresh()->silver_price);
        $this->assertSame('missing_contapyme', $missing->fresh()->price_sync_status);
        $this->assertSame('10.00', $anomaly->fresh()->price);
        $this->assertSame('anomalous', $anomaly->fresh()->price_sync_status);
    }

    public function test_dry_run_does_not_write_prices_or_status(): void
    {
        $product = Product::factory()->create(['sku' => 'SKU-A', 'price' => '10.00', 'silver_price' => null]);
        $this->fakePrices(['11.00', '13.00']);

        $this->artisan('contapyme:sync-prices --force --dry-run')->assertSuccessful();

        $product->refresh();
        $this->assertNull($product->silver_price);
        $this->assertSame('never_synced', $product->price_sync_status);
    }

    /** @param list<string> $prices */
    private function fakePrices(array $prices): void
    {
        $mock = Mockery::mock(ContaPymePriceService::class);
        $mock->shouldReceive('calculatedPrice')->twice()->andReturn(
            ContaPymePriceLookup::ok($prices[0]),
            ContaPymePriceLookup::ok($prices[1]),
        );
        $this->app->instance(ContaPymePriceService::class, $mock);
    }
}
