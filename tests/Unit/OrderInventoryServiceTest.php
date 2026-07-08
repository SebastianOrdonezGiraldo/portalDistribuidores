<?php

namespace Tests\Unit;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderInventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderInventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new OrderInventoryService;
    }

    public function test_decrease_for_order_deducts_stock(): void
    {
        $product = Product::factory()->create([
            'stock' => 50,
        ]);
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'qty' => 10,
        ]);

        $this->service->decreaseForOrder($order);

        $product->refresh();

        $this->assertSame(40.0, (float) $product->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'source' => 'order_deduction',
            'previous_stock' => 50,
            'new_stock' => 40,
            'delta' => -10,
        ]);
    }

    public function test_decrease_for_order_throws_when_insufficient_stock(): void
    {
        $product = Product::factory()->create([
            'stock' => 2,
        ]);
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'qty' => 5,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $this->service->decreaseForOrder($order);
    }

    public function test_increase_for_order_restores_stock(): void
    {
        $product = Product::factory()->create([
            'stock' => 40,
        ]);
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'qty' => 10,
        ]);

        $this->service->increaseForOrder($order);

        $product->refresh();

        $this->assertSame(50.0, (float) $product->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'source' => 'order_restoration',
            'previous_stock' => 40,
            'new_stock' => 50,
            'delta' => 10,
        ]);
    }

    public function test_decrease_for_order_deducts_variant_stock(): void
    {
        $attribute = ProductAttribute::factory()->create(['name' => 'Color']);
        $attributeValue = ProductAttributeValue::factory()->create([
            'product_attribute_id' => $attribute->id,
            'value' => 'Rojo',
        ]);
        $product = Product::factory()->create(['is_active' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'product_attribute_value_id' => $attributeValue->id,
            'stock' => 50,
            'is_active' => true,
        ]);
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'qty' => 7,
        ]);

        $this->service->decreaseForOrder($order);

        $variant->refresh();

        $this->assertSame(43.0, (float) $variant->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_variant_id' => $variant->id,
            'source' => 'order_deduction',
            'previous_stock' => 50,
            'new_stock' => 43,
            'delta' => -7,
        ]);
    }

    public function test_increase_for_order_restores_variant_stock(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 43,
            'is_active' => true,
        ]);
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'qty' => 7,
        ]);

        $this->service->increaseForOrder($order);

        $variant->refresh();

        $this->assertSame(50.0, (float) $variant->stock);
    }

    public function test_decrease_for_order_throws_when_variant_stock_insufficient(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 1,
            'is_active' => true,
        ]);
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'qty' => 2,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Stock insuficiente en variante');

        $this->service->decreaseForOrder($order);
    }

    public function test_decrease_for_order_is_idempotent_with_empty_items(): void
    {
        $order = Order::factory()->create();

        $this->service->decreaseForOrder($order);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_increase_for_order_is_idempotent_with_empty_items(): void
    {
        $order = Order::factory()->create();

        $this->service->increaseForOrder($order);

        $this->assertDatabaseCount('stock_movements', 0);
    }
}
