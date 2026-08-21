<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryHoldLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sold_releases_only_its_local_hold_without_changing_base_stock(): void
    {
        $product = Product::factory()->create(['sku' => 'HOLD-SKU', 'stock' => 20]);
        $first = $this->createHeldOrder($product, 4);
        $second = $this->createHeldOrder($product, 5);

        $updated = app(OrderStatusTransitionService::class)->transition(
            $first,
            OrderStatus::Sold,
            null,
            'FVE creada.',
        );

        $product->refresh();
        $this->assertSame(OrderStatus::Sold, $updated->status);
        $this->assertNull($updated->inventory_reconciliation_status);
        $this->assertSame(20.0, (float) $product->stock);
        $this->assertSame(5.0, (float) $product->reserved_stock);
        $this->assertSame(15.0, $product->available_stock);
        $this->assertDatabaseHas('inventory_holds', [
            'order_id' => $first->id,
            'status' => 'released',
            'release_reason' => 'status_sold',
        ]);
        $this->assertDatabaseHas('inventory_holds', [
            'order_id' => $second->id,
            'status' => 'active',
            'quantity' => 5,
        ]);

        app(OrderStatusTransitionService::class)->transition($second, OrderStatus::Cancelled);
        $this->assertSame(20.0, (float) $product->fresh()->available_stock);
    }

    public function test_sold_clears_stale_reconciliation_failure_and_releases_hold(): void
    {
        $product = Product::factory()->create(['sku' => 'RETRY-SKU', 'stock' => 20]);
        $order = Order::factory()->create([
            'status' => OrderStatus::Submitted,
            'inventory_reconciliation_status' => 'failed',
            'inventory_reconciliation_attempted_at' => now(),
            'inventory_reconciliation_error' => 'Fallo temporal simulado.',
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 4,
        ]);
        app(OrderInventoryService::class)->holdForOrder($order);

        $updated = app(OrderStatusTransitionService::class)->transition(
            $order,
            OrderStatus::Sold,
            null,
            'FVE creada.',
        );

        $this->assertSame(OrderStatus::Sold, $updated->status);
        $this->assertNull($updated->inventory_reconciliation_status);
        $this->assertNull($updated->inventory_reconciliation_error);
        $this->assertSame(20.0, (float) $product->fresh()->stock);
        $this->assertSame(20.0, (float) $product->fresh()->available_stock);
        $this->assertSame(1, $order->inventoryHolds()->firstOrFail()->events()->where('action', 'released')->count());
    }

    public function test_sold_releases_variant_hold_without_contapyme_mapping(): void
    {
        $product = Product::factory()->create(['sku' => 'PARENT-SKU', 'stock' => 10]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 10,
        ]);
        $order = $this->createHeldOrder($product, 4, $variant);

        $updated = app(OrderStatusTransitionService::class)->transition(
            $order,
            OrderStatus::Sold,
            null,
            'FVE creada.',
        );

        $variant->refresh();
        $product->refresh();
        $this->assertSame(OrderStatus::Sold, $updated->status);
        $this->assertSame(10.0, (float) $variant->stock);
        $this->assertSame(0.0, (float) $variant->reserved_stock);
        $this->assertSame(10.0, $variant->available_stock);
        $this->assertSame(10.0, $product->available_stock);
    }

    private function createHeldOrder(Product $product, int $qty, ?ProductVariant $variant = null): Order
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'qty' => $qty,
        ]);
        app(OrderInventoryService::class)->holdForOrder($order);

        return $order;
    }
}
