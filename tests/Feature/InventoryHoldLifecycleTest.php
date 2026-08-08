<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\ContaPymeInventoryMapping;
use App\Modules\Inventory\Services\ContaPymeInventoryService;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use App\Modules\Shared\ValueObjects\InventoryItemData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class InventoryHoldLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sold_reconciles_base_then_releases_only_its_hold(): void
    {
        $product = Product::factory()->create(['sku' => 'HOLD-SKU', 'stock' => 20]);
        $first = $this->createHeldOrder($product, 4);
        $second = $this->createHeldOrder($product, 5);
        $this->fakeContaPyme(['HOLD-SKU' => 16]);

        $updated = app(OrderStatusTransitionService::class)->transition(
            $first,
            OrderStatus::Sold,
            null,
            'Registrado manualmente en ContaPyme.',
        );

        $product->refresh();
        $this->assertSame(OrderStatus::Sold, $updated->status);
        $this->assertSame('synced', $updated->inventory_reconciliation_status);
        $this->assertSame(16.0, (float) $product->stock);
        $this->assertSame(5.0, (float) $product->reserved_stock);
        $this->assertSame(11.0, $product->available_stock);
        $this->assertDatabaseHas('inventory_holds', [
            'order_id' => $first->id,
            'status' => 'released',
            'release_reason' => 'sold_after_contapyme_reconciliation',
        ]);
        $this->assertDatabaseHas('inventory_holds', [
            'order_id' => $second->id,
            'status' => 'active',
            'quantity' => 5,
        ]);

        app(OrderStatusTransitionService::class)->transition($second, OrderStatus::Cancelled);
        $this->assertSame(16.0, $product->fresh()->available_stock);
    }

    public function test_reconciliation_failure_keeps_submitted_hold_and_retry_is_idempotent(): void
    {
        $product = Product::factory()->create(['sku' => 'RETRY-SKU', 'stock' => 20]);
        $order = $this->createHeldOrder($product, 4);

        $this->mock(ContaPymeInventoryService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getProductInfo')
                ->twice()
                ->with('RETRY-SKU')
                ->andReturn(null, new InventoryItemData('RETRY-SKU', stock: 16, externalId: 'RETRY-SKU'));
            $mock->shouldReceive('lastError')->once()->andReturn('Fallo temporal simulado.');
            $mock->shouldReceive('diagnosticError')->once()->andReturn('Fallo temporal simulado.');
        });

        $service = app(OrderStatusTransitionService::class);

        try {
            $service->transition($order, OrderStatus::Sold, null, 'Primer intento.');
            $this->fail('Expected reconciliation failure.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('no devolvió inventario válido', $exception->getMessage());
        }

        $order->refresh();
        $product->refresh();
        $this->assertSame(OrderStatus::Submitted, $order->status);
        $this->assertSame('failed', $order->inventory_reconciliation_status);
        $this->assertSame(4.0, (float) $product->reserved_stock);
        $this->assertSame(16.0, $product->available_stock);

        $updated = $service->transition($order, OrderStatus::Sold, null, 'Reintento exitoso.');
        $this->assertSame(OrderStatus::Sold, $updated->status);
        $this->assertSame(16.0, $product->fresh()->available_stock);
        $this->assertSame(1, $order->inventoryHolds()->firstOrFail()->events()->where('action', 'released')->count());
    }

    public function test_variant_reconciliation_requires_mapping_and_updates_variant_base(): void
    {
        $product = Product::factory()->create(['sku' => 'PARENT-SKU', 'stock' => 10]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 10,
        ]);
        $order = $this->createHeldOrder($product, 4, $variant);

        try {
            app(OrderStatusTransitionService::class)->transition($order, OrderStatus::Sold, null, 'Sin mapping.');
            $this->fail('Expected mapping failure.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('irecurso', $exception->getMessage());
        }

        $this->assertSame(4.0, (float) $variant->fresh()->reserved_stock);

        ContaPymeInventoryMapping::create([
            'product_variant_id' => $variant->id,
            'irecurso' => 'VARIANT-IR',
            'status' => 'mapped',
        ]);
        $this->fakeContaPyme(['VARIANT-IR' => 6]);

        $updated = app(OrderStatusTransitionService::class)->transition(
            $order->fresh(),
            OrderStatus::Sold,
            null,
            'Variante registrada.',
        );

        $variant->refresh();
        $product->refresh();
        $this->assertSame(OrderStatus::Sold, $updated->status);
        $this->assertSame(6.0, (float) $variant->stock);
        $this->assertSame(0.0, (float) $variant->reserved_stock);
        $this->assertSame(6.0, $variant->available_stock);
        $this->assertSame(6.0, $product->available_stock);
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

    /** @param array<string, float> $stocks */
    private function fakeContaPyme(array $stocks): void
    {
        $this->mock(ContaPymeInventoryService::class, function (MockInterface $mock) use ($stocks): void {
            $mock->shouldReceive('getProductInfo')
                ->andReturnUsing(function (string $sku) use ($stocks): ?InventoryItemData {
                    if (! array_key_exists($sku, $stocks)) {
                        return null;
                    }

                    return new InventoryItemData($sku, stock: $stocks[$sku], externalId: $sku);
                });
        });
    }
}
