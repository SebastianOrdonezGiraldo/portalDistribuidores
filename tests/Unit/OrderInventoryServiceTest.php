<?php

namespace Tests\Unit;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryHold;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Shared\Enums\OrderStatus;
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

    public function test_hold_preserves_base_stock_and_decreases_available_stock(): void
    {
        [$order, $product] = $this->simpleOrder(stock: 50, qty: 10);

        $this->service->holdForOrder($order);

        $product->refresh();
        $this->assertSame(50.0, (float) $product->stock);
        $this->assertSame(10.0, (float) $product->reserved_stock);
        $this->assertSame(40.0, $product->available_stock);
        $this->assertDatabaseHas('inventory_holds', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 10,
            'status' => InventoryHold::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('inventory_hold_events', [
            'order_id' => $order->id,
            'action' => 'created',
            'previous_quantity' => 0,
            'new_quantity' => 10,
        ]);
    }

    public function test_two_orders_accumulate_holds_and_cannot_overreserve(): void
    {
        $product = Product::factory()->create(['stock' => 20]);
        $first = $this->orderFor($product, 4);
        $second = $this->orderFor($product, 5);

        $this->service->holdForOrder($first);
        $this->service->holdForOrder($second);

        $product->refresh();
        $this->assertSame(20.0, (float) $product->stock);
        $this->assertSame(9.0, (float) $product->reserved_stock);
        $this->assertSame(11.0, $product->available_stock);

        $tooLarge = $this->orderFor($product, 12);

        try {
            $this->service->holdForOrder($tooLarge);
            $this->fail('Expected insufficient stock exception.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Disponible: 11', $exception->getMessage());
        }

        $this->assertSame(9.0, (float) $product->fresh()->reserved_stock);
        $this->assertDatabaseMissing('inventory_holds', ['order_id' => $tooLarge->id]);
    }

    public function test_release_is_idempotent_and_never_increases_base_stock(): void
    {
        [$order, $product] = $this->simpleOrder(stock: 10, qty: 3);
        $this->service->holdForOrder($order);

        $this->service->releaseForOrder($order, reason: 'cancelled');
        $this->service->releaseForOrder($order, reason: 'cancelled_again');

        $product->refresh();
        $this->assertSame(10.0, (float) $product->stock);
        $this->assertSame(0.0, (float) $product->reserved_stock);
        $this->assertSame(10.0, $product->available_stock);
        $this->assertDatabaseHas('inventory_holds', [
            'order_id' => $order->id,
            'status' => InventoryHold::STATUS_RELEASED,
            'release_reason' => 'cancelled',
        ]);
        $this->assertSame(1, $order->inventoryHolds()->firstOrFail()->events()->where('action', 'released')->count());
    }

    public function test_variant_hold_updates_variant_and_parent_projection_without_mutating_bases(): void
    {
        $product = Product::factory()->create(['stock' => 50]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 50,
        ]);
        $order = $this->orderFor($product, 7, $variant);

        $this->service->holdForOrder($order);

        $product->refresh();
        $variant->refresh();
        $this->assertSame(50.0, (float) $product->stock);
        $this->assertSame(43.0, $product->available_stock);
        $this->assertSame(50.0, (float) $variant->stock);
        $this->assertSame(7.0, (float) $variant->reserved_stock);
        $this->assertSame(43.0, $variant->available_stock);
    }

    public function test_variant_hold_rejects_quantity_over_effective_availability(): void
    {
        $product = Product::factory()->create(['stock' => 1]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 1,
        ]);
        $order = $this->orderFor($product, 2, $variant);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $this->service->holdForOrder($order);
    }

    public function test_edit_adjusts_increase_decrease_add_and_remove_atomically(): void
    {
        $productA = Product::factory()->create(['stock' => 10]);
        $productB = Product::factory()->create(['stock' => 8]);
        $order = $this->orderFor($productA, 4);
        $this->service->holdForOrder($order);

        $item = $order->items()->firstOrFail();
        $item->update(['qty' => 6]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $productB->id,
            'product_variant_id' => null,
            'qty' => 3,
        ]);
        $this->service->adjustForEditedOrder($order);

        $this->assertSame(4.0, $productA->fresh()->available_stock);
        $this->assertSame(5.0, $productB->fresh()->available_stock);

        $item->delete();
        $order->items()->where('product_id', $productB->id)->update(['qty' => 1]);
        $this->service->adjustForEditedOrder($order);

        $this->assertSame(10.0, $productA->fresh()->available_stock);
        $this->assertSame(7.0, $productB->fresh()->available_stock);
        $this->assertDatabaseHas('inventory_holds', [
            'order_id' => $order->id,
            'product_id' => $productA->id,
            'status' => InventoryHold::STATUS_RELEASED,
        ]);
    }

    public function test_failed_edit_keeps_previous_hold_intact(): void
    {
        [$order, $product] = $this->simpleOrder(stock: 5, qty: 3);
        $this->service->holdForOrder($order);
        $order->items()->firstOrFail()->update(['qty' => 6]);

        try {
            $this->service->adjustForEditedOrder($order);
            $this->fail('Expected insufficient stock exception.');
        } catch (DomainException) {
            // Expected.
        }

        $this->assertSame(3.0, (float) $product->fresh()->reserved_stock);
        $this->assertDatabaseHas('inventory_holds', [
            'order_id' => $order->id,
            'quantity' => 3,
            'status' => InventoryHold::STATUS_ACTIVE,
        ]);
    }

    public function test_empty_order_operations_are_idempotent(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);

        $this->service->holdForOrder($order);
        $this->service->releaseForOrder($order);

        $this->assertDatabaseCount('inventory_holds', 0);
        $this->assertDatabaseCount('inventory_hold_events', 0);
    }

    /** @return array{Order, Product} */
    private function simpleOrder(float $stock, int $qty): array
    {
        $product = Product::factory()->create(['stock' => $stock]);

        return [$this->orderFor($product, $qty), $product];
    }

    private function orderFor(Product $product, int $qty, ?ProductVariant $variant = null): Order
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'qty' => $qty,
        ]);

        return $order;
    }
}
