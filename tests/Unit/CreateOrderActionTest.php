<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Actions\CreateOrderAction;
use App\Modules\Orders\DTOs\CreateOrderData;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_creates_submitted_order_decreases_inventory_records_initial_status_and_dispatches_event(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $product = Product::factory()->create([
            'price' => 10000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())
            ->method('recordInitialStatus')
            ->with(
                $this->callback(function (Order $order) use ($user): bool {
                    return $order->exists
                        && $order->user_id === $user->id
                        && $order->distributor_id === $user->distributor_id
                        && $order->status === OrderStatus::Submitted
                        && str_starts_with($order->oc_number, Order::OC_PREFIX);
                }),
                $user,
                'Estado inicial registrado al crear la cotización.'
            );

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())
            ->method('decreaseForOrder')
            ->with($this->callback(fn (Order $order) => $order->status === OrderStatus::Submitted));

        $action = new CreateOrderAction($statusService, $inventoryService);

        $order = $action->execute($user, $this->makeOrderData([
            ['product_id' => $product->id, 'variant_id' => null, 'qty' => 2, 'unit_label' => 'cajas'],
        ]));

        $this->assertSame(OrderStatus::Submitted, $order->status);
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame($user->distributor_id, $order->distributor_id);
        $this->assertSame('CTC-000001', $order->oc_number);
        $this->assertSame('20000.00', $order->total_amount);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'qty' => 2,
            'unit_label' => 'cajas',
            'price_each' => 10000,
            'subtotal' => 20000,
            'is_vat_excluded_snapshot' => false,
        ]);

        $this->assertSame(0.13, (float) $order->items()->firstOrFail()->vat_rate_snapshot);

        Event::assertDispatched(OrderPlaced::class, fn (OrderPlaced $event) => $event->order->is($order));
    }

    public function test_execute_persists_vat_excluded_snapshot_without_changing_final_total(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $product = Product::factory()->create([
            'price' => 34000,
            'stock' => 10,
            'is_active' => true,
            'is_vat_excluded' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())->method('recordInitialStatus');

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())->method('decreaseForOrder');

        $action = new CreateOrderAction($statusService, $inventoryService);

        $order = $action->execute($user, $this->makeOrderData([
            ['product_id' => $product->id, 'variant_id' => null, 'qty' => 2, 'unit_label' => 'unidades'],
        ]));

        $item = $order->items()->firstOrFail();

        $this->assertSame('68000.00', $order->total_amount);
        $this->assertTrue((bool) $item->is_vat_excluded_snapshot);
        $this->assertSame(0.0, (float) $item->vat_rate_snapshot);
        $this->assertSame('34000.00', $item->price_each);
        $this->assertSame('68000.00', $item->subtotal);
    }

    public function test_execute_creates_pending_approval_order_without_inventory_decrease_or_event(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $product = Product::factory()->create([
            'price' => 5000,
            'is_active' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())
            ->method('recordInitialStatus')
            ->with(
                $this->callback(fn (Order $order) => $order->status === OrderStatus::PendingApproval),
                $user,
                'Estado inicial registrado al crear la cotización.'
            );

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->never())->method('decreaseForOrder');

        $action = new CreateOrderAction($statusService, $inventoryService);

        $order = $action->execute($user, $this->makeOrderData([
            ['product_id' => $product->id, 'variant_id' => null, 'qty' => 1, 'unit_label' => 'unidades'],
        ], requiresApproval: true));

        $this->assertSame(OrderStatus::PendingApproval, $order->status);
        Event::assertNotDispatched(OrderPlaced::class);
    }

    public function test_execute_uses_variant_price_and_persists_variant_snapshots(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $product = Product::factory()->create([
            'price' => 10000,
            'is_active' => true,
        ]);
        $attribute = ProductAttribute::factory()->create(['name' => 'Talla']);
        $attributeValue = ProductAttributeValue::factory()->create([
            'product_attribute_id' => $attribute->id,
            'value' => 'M',
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'product_attribute_value_id' => $attributeValue->id,
            'price' => 15000,
            'is_active' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())->method('recordInitialStatus');

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())->method('decreaseForOrder');

        $action = new CreateOrderAction($statusService, $inventoryService);

        $order = $action->execute($user, $this->makeOrderData([
            ['product_id' => $product->id, 'variant_id' => $variant->id, 'qty' => 3, 'unit_label' => 'unidades'],
        ]));

        $this->assertSame('45000.00', $order->total_amount);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'variant_attribute_snapshot' => 'Talla',
            'variant_value_snapshot' => 'M',
            'price_each' => 15000,
            'subtotal' => 45000,
        ]);
    }

    public function test_execute_skips_invalid_items_and_normalizes_quantity_before_persisting(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $simpleProduct = Product::factory()->create([
            'price' => 8000,
            'is_active' => true,
        ]);
        $configurableProduct = Product::factory()->create([
            'price' => 12000,
            'is_active' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $configurableProduct->id,
            'is_active' => true,
        ]);
        $otherProduct = Product::factory()->create([
            'price' => 9000,
            'is_active' => true,
        ]);
        $foreignVariant = ProductVariant::factory()->create([
            'product_id' => $otherProduct->id,
            'is_active' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())->method('recordInitialStatus');

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())->method('decreaseForOrder');

        $action = new CreateOrderAction($statusService, $inventoryService);

        $order = $action->execute($user, $this->makeOrderData([
            ['product_id' => $simpleProduct->id, 'variant_id' => null, 'qty' => 0, 'unit_label' => 'packs'],
            ['product_id' => $configurableProduct->id, 'variant_id' => null, 'qty' => 2, 'unit_label' => 'unidades'],
            ['product_id' => $simpleProduct->id, 'variant_id' => $foreignVariant->id, 'qty' => 2, 'unit_label' => 'unidades'],
        ]));

        $this->assertSame('8000.00', $order->total_amount);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $simpleProduct->id,
            'qty' => 1,
            'unit_label' => 'packs',
            'subtotal' => 8000,
        ]);
    }

    public function test_execute_throws_when_cart_cannot_produce_any_valid_line(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $configurableProduct = Product::factory()->create([
            'price' => 12000,
            'is_active' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $configurableProduct->id,
            'is_active' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->never())->method('recordInitialStatus');

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->never())->method('decreaseForOrder');

        $action = new CreateOrderAction($statusService, $inventoryService);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('El carrito no puede generar una orden vacía.');

        $action->execute($user, $this->makeOrderData([
            ['product_id' => $configurableProduct->id, 'variant_id' => null, 'qty' => 2, 'unit_label' => 'unidades'],
        ]));
    }

    public function test_execute_rolls_back_order_and_skips_follow_up_side_effects_when_inventory_fails(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $product = Product::factory()->create([
            'price' => 7000,
            'is_active' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->never())->method('recordInitialStatus');

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())
            ->method('decreaseForOrder')
            ->willThrowException(new DomainException('stock error'));

        $action = new CreateOrderAction($statusService, $inventoryService);

        try {
            $action->execute($user, $this->makeOrderData([
                ['product_id' => $product->id, 'variant_id' => null, 'qty' => 1, 'unit_label' => 'unidades'],
            ]));
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $exception) {
            $this->assertSame('stock error', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        Event::assertNotDispatched(OrderPlaced::class);
    }

    private function makeDistributorUser(): User
    {
        $distributor = Distributor::factory()->create();

        return User::factory()->create([
            'distributor_id' => $distributor->id,
        ]);
    }

    /**
     * @param  array<int, array{product_id:int,variant_id:int|null,qty:int,unit_label:string}>  $items
     */
    private function makeOrderData(array $items, bool $requiresApproval = false): CreateOrderData
    {
        return new CreateOrderData(
            contactName: 'Comprador Test',
            contactEmail: 'comprador@test.com',
            phone: '3001234567',
            companyName: 'Empresa Test',
            companyNit: '9001234567',
            companyAddress: 'Calle 123',
            city: 'Bogota',
            department: 'Cundinamarca',
            notes: 'nota de prueba',
            items: $items,
            requiresApproval: $requiresApproval,
        );
    }
}
