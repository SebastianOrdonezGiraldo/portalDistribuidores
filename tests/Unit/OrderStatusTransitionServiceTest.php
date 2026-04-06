<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTransitionServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderInventoryService $inventoryService;

    private OrderStatusTransitionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryService = $this->createMock(OrderInventoryService::class);
        $this->service = new OrderStatusTransitionService($this->inventoryService);
    }

    public function test_transition_rejects_when_order_is_already_in_target_status(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);

        $this->inventoryService
            ->expects($this->never())
            ->method('decreaseForOrder');
        $this->inventoryService
            ->expects($this->never())
            ->method('increaseForOrder');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('La cotización ya se encuentra en el estado seleccionado.');

        $this->service->transition($order, OrderStatus::Submitted);
    }

    public function test_transition_rejects_when_status_change_is_not_allowed(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);

        $this->inventoryService
            ->expects($this->never())
            ->method('decreaseForOrder');
        $this->inventoryService
            ->expects($this->never())
            ->method('increaseForOrder');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('No se permite cambiar de submitted a delivered.');

        $this->service->transition($order, OrderStatus::Delivered);
    }

    public function test_transition_rejects_when_required_note_is_missing(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);

        $this->inventoryService
            ->expects($this->never())
            ->method('decreaseForOrder');
        $this->inventoryService
            ->expects($this->never())
            ->method('increaseForOrder');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Este cambio de estado requiere una nota de trazabilidad.');

        $this->service->transition($order, OrderStatus::Sold, null, '   ');
    }

    public function test_transition_persists_trimmed_note_status_change_and_history(): void
    {
        $actor = User::factory()->create();
        $order = Order::factory()->create(['status' => OrderStatus::Sold]);

        $this->inventoryService
            ->expects($this->never())
            ->method('decreaseForOrder');
        $this->inventoryService
            ->expects($this->never())
            ->method('increaseForOrder');

        $updatedOrder = $this->service->transition($order, OrderStatus::Dispatched, $actor, '  despacho parcial  ');

        $this->assertTrue($updatedOrder->status === OrderStatus::Dispatched);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Dispatched->value,
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Sold->value,
            'to_status' => OrderStatus::Dispatched->value,
            'changed_by_user_id' => $actor->id,
            'note' => 'despacho parcial',
        ]);
    }

    public function test_transition_decreases_inventory_only_when_entering_consuming_status_for_first_time(): void
    {
        $order = Order::factory()->pendingApproval()->create();

        $this->inventoryService
            ->expects($this->once())
            ->method('decreaseForOrder')
            ->with($this->callback(fn (Order $updatedOrder) => $updatedOrder->is($order)));
        $this->inventoryService
            ->expects($this->never())
            ->method('increaseForOrder');

        $this->service->transition($order, OrderStatus::Submitted);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Submitted->value,
        ]);
    }

    public function test_transition_increases_inventory_when_leaving_consuming_status_to_non_consuming_status(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);

        $this->inventoryService
            ->expects($this->never())
            ->method('decreaseForOrder');
        $this->inventoryService
            ->expects($this->once())
            ->method('increaseForOrder')
            ->with($this->callback(fn (Order $updatedOrder) => $updatedOrder->is($order)));

        $this->service->transition($order, OrderStatus::Cancelled);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Submitted->value,
            'to_status' => OrderStatus::Cancelled->value,
        ]);
    }

    public function test_transition_does_not_adjust_inventory_when_both_statuses_consume_inventory(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Sold]);

        $this->inventoryService
            ->expects($this->never())
            ->method('decreaseForOrder');
        $this->inventoryService
            ->expects($this->never())
            ->method('increaseForOrder');

        $this->service->transition($order, OrderStatus::Dispatched, null, 'guia 123');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Dispatched->value,
        ]);
    }

    public function test_transition_does_not_adjust_inventory_when_neither_status_consumes_inventory(): void
    {
        $order = Order::factory()->pendingApproval()->create();

        $this->inventoryService
            ->expects($this->never())
            ->method('decreaseForOrder');
        $this->inventoryService
            ->expects($this->never())
            ->method('increaseForOrder');

        $this->service->transition($order, OrderStatus::Rejected);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Rejected->value,
        ]);
    }

    public function test_transition_persists_null_actor_and_null_note_when_note_is_optional(): void
    {
        $order = Order::factory()->pendingApproval()->create();

        $this->inventoryService
            ->expects($this->once())
            ->method('decreaseForOrder')
            ->with($this->callback(fn (Order $updatedOrder) => $updatedOrder->is($order)));
        $this->inventoryService
            ->expects($this->never())
            ->method('increaseForOrder');

        $this->service->transition($order, OrderStatus::Submitted, null, '   ');

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::PendingApproval->value,
            'to_status' => OrderStatus::Submitted->value,
            'changed_by_user_id' => null,
            'note' => null,
        ]);
    }

    public function test_transition_rolls_back_order_and_history_when_decreasing_inventory_fails(): void
    {
        $order = Order::factory()->pendingApproval()->create();

        $this->inventoryService
            ->expects($this->once())
            ->method('decreaseForOrder')
            ->willThrowException(new DomainException('stock fail'));
        $this->inventoryService
            ->expects($this->never())
            ->method('increaseForOrder');

        try {
            $this->service->transition($order, OrderStatus::Submitted);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $exception) {
            $this->assertSame('stock fail', $exception->getMessage());
        }

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PendingApproval->value,
        ]);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_transition_rolls_back_order_and_history_when_increasing_inventory_fails(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);

        $this->inventoryService
            ->expects($this->never())
            ->method('decreaseForOrder');
        $this->inventoryService
            ->expects($this->once())
            ->method('increaseForOrder')
            ->willThrowException(new DomainException('restore fail'));

        try {
            $this->service->transition($order, OrderStatus::Cancelled);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $exception) {
            $this->assertSame('restore fail', $exception->getMessage());
        }

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Submitted->value,
        ]);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_record_initial_status_creates_first_history_entry_once(): void
    {
        $actor = User::factory()->create();
        $order = Order::factory()->pendingApproval()->create();

        $this->service->recordInitialStatus($order, $actor, '  creada desde checkout  ');
        $this->service->recordInitialStatus($order, $actor, 'otro intento');

        $this->assertDatabaseCount('order_status_histories', 1);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => OrderStatus::PendingApproval->value,
            'changed_by_user_id' => $actor->id,
            'note' => 'creada desde checkout',
        ]);
    }

    public function test_record_initial_status_persists_null_actor_and_normalized_empty_note_as_null(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);

        $this->service->recordInitialStatus($order, null, '   ');

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => OrderStatus::Submitted->value,
            'changed_by_user_id' => null,
            'note' => null,
        ]);
    }
}
