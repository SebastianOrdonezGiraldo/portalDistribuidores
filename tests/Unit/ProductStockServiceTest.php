<?php

namespace Tests\Unit;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Services\ProductStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductStockService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProductStockService;
    }

    public function test_update_simple_product_stock_returns_false_when_stock_is_externally_managed(): void
    {
        $product = Product::factory()->create([
            'external_stock' => 100,
            'reserved_stock' => 5,
            'stock' => 95,
        ]);

        $result = $this->service->updateSimpleProductStock($product, 200);

        $this->assertFalse($result);

        $product->refresh();

        $this->assertSame(100.0, (float) $product->external_stock);
        $this->assertSame(5.0, (float) $product->reserved_stock);
        $this->assertSame(95.0, (float) $product->stock);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_update_simple_product_stock_succeeds_for_legacy_product(): void
    {
        $product = Product::factory()->create([
            'external_stock' => null,
            'reserved_stock' => 0,
            'stock' => 50,
        ]);

        $result = $this->service->updateSimpleProductStock($product, 120);

        $this->assertTrue($result);

        $product->refresh();

        $this->assertNull($product->external_stock);
        $this->assertSame(0.0, (float) $product->reserved_stock);
        $this->assertSame(120.0, (float) $product->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'source' => 'admin_manual',
            'previous_stock' => 50,
            'new_stock' => 120,
            'delta' => 70,
        ]);
    }

    public function test_update_simple_product_stock_returns_false_when_product_has_active_variants(): void
    {
        $product = Product::factory()->create([
            'external_stock' => null,
            'stock' => 50,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'stock' => 10,
        ]);

        $result = $this->service->updateSimpleProductStock($product, 200);

        $this->assertFalse($result);

        $product->refresh();

        $this->assertSame(50.0, (float) $product->stock);
    }

    public function test_update_simple_product_stock_normalizes_to_zero_when_negative(): void
    {
        $product = Product::factory()->create([
            'external_stock' => null,
            'stock' => 30,
        ]);

        $result = $this->service->updateSimpleProductStock($product, -10);

        $this->assertTrue($result);

        $product->refresh();

        $this->assertSame(0.0, (float) $product->stock);
    }

    public function test_update_simple_product_stock_accepts_null_to_set_stock_to_null(): void
    {
        $product = Product::factory()->create([
            'external_stock' => null,
            'stock' => 30,
        ]);

        $result = $this->service->updateSimpleProductStock($product, null);

        $this->assertTrue($result);

        $product->refresh();

        $this->assertNull($product->stock);
    }

    public function test_update_variant_stocks_updates_variant_and_recomputes_product_stock(): void
    {
        $product = Product::factory()->create([
            'external_stock' => null,
            'stock' => 50,
        ]);
        $variant1 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'stock' => 10,
        ]);
        $variant2 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'stock' => 20,
        ]);

        $result = $this->service->updateVariantStocks($product, [
            ['id' => $variant1->id, 'stock' => 15],
            ['id' => $variant2->id, 'stock' => 25],
        ]);

        $this->assertTrue($result);

        $variant1->refresh();
        $variant2->refresh();

        $this->assertSame(15.0, (float) $variant1->stock);
        $this->assertSame(25.0, (float) $variant2->stock);

        $product->refresh();

        $this->assertSame(40.0, (float) $product->stock);
    }

    public function test_update_variant_stocks_returns_false_when_no_active_variants(): void
    {
        $product = Product::factory()->create([
            'external_stock' => null,
            'stock' => 50,
        ]);

        $result = $this->service->updateVariantStocks($product, []);

        $this->assertFalse($result);
    }
}
