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

    public function test_transition_rejects_same_illegal_and_missing_note_targets(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);

        foreach ([
            [OrderStatus::Submitted, null, 'ya se encuentra'],
            [OrderStatus::Delivered, null, 'No se permite'],
            [OrderStatus::Sold, '   ', 'requiere una nota'],
        ] as [$target, $note, $message]) {
            try {
                $this->service->transition($order->fresh(), $target, null, $note);
                $this->fail('Expected DomainException.');
            } catch (DomainException $exception) {
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }
    }

    public function test_pending_approval_to_submitted_creates_hold_once(): void
    {
        $order = Order::factory()->pendingApproval()->create();

        $this->inventoryService->expects($this->once())
            ->method('holdForOrder')
            ->with(
                $this->callback(fn (Order $updated): bool => $updated->is($order)),
                null,
                'status_submitted',
            );
        $this->inventoryService->expects($this->never())->method('releaseForOrder');

        $updated = $this->service->transition($order, OrderStatus::Submitted);

        $this->assertSame(OrderStatus::Submitted, $updated->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::PendingApproval->value,
            'to_status' => OrderStatus::Submitted->value,
        ]);
    }

    public function test_submitted_to_cancelled_releases_hold_once(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);

        $this->inventoryService->expects($this->once())
            ->method('releaseForOrder')
            ->with(
                $this->callback(fn (Order $updated): bool => $updated->is($order)),
                null,
                'status_cancelled',
            );

        $updated = $this->service->transition($order, OrderStatus::Cancelled);

        $this->assertSame(OrderStatus::Cancelled, $updated->status);
    }

    public function test_submitted_to_sold_releases_hold_without_external_reconciliation(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);

        $this->inventoryService->expects($this->once())
            ->method('hasActiveHold')
            ->with($this->callback(fn (Order $locked): bool => $locked->is($order)))
            ->willReturn(true);
        $this->inventoryService->expects($this->once())
            ->method('releaseForOrder')
            ->with(
                $this->callback(fn (Order $locked): bool => $locked->is($order)),
                null,
                'status_sold',
            );

        $updated = $this->service->transition($order, OrderStatus::Sold, null, 'FVE creada.');

        $this->assertSame(OrderStatus::Sold, $updated->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Submitted->value,
            'to_status' => OrderStatus::Sold->value,
            'note' => 'FVE creada.',
        ]);
    }

    public function test_non_hold_transitions_do_not_touch_inventory(): void
    {
        $this->inventoryService->expects($this->never())->method('holdForOrder');
        $this->inventoryService->expects($this->never())->method('releaseForOrder');

        $rejected = Order::factory()->pendingApproval()->create();
        $this->service->transition($rejected, OrderStatus::Rejected);

        $sold = Order::factory()->create(['status' => OrderStatus::Sold]);
        $this->service->transition($sold, OrderStatus::Dispatched, null, 'despacho');

        $dispatched = Order::factory()->create([
            'status' => OrderStatus::Dispatched,
            'tracking_number' => '957000255300',
            'shipping_carrier' => 'envia',
        ]);
        $updated = $this->service->transition($dispatched, OrderStatus::Delivered);

        $this->assertSame(OrderStatus::Delivered, $updated->status);
        $this->assertSame('957000255300', $updated->tracking_number);
    }

    public function test_dispatched_does_not_require_admin_shipping_data(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Sold]);

        $actor = User::factory()->create();
        $updated = $this->service->transition(
            $order->fresh(),
            OrderStatus::Dispatched,
            $actor,
            '  despacho parcial  ',
        );

        $this->assertSame(OrderStatus::Dispatched, $updated->status);
        $this->assertNull($updated->tracking_number);
        $this->assertNull($updated->shipping_carrier);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'changed_by_user_id' => $actor->id,
            'note' => 'despacho parcial',
        ]);
    }

    public function test_hold_failure_rolls_back_status_and_history(): void
    {
        $order = Order::factory()->pendingApproval()->create();
        $this->inventoryService->method('holdForOrder')
            ->willThrowException(new DomainException('hold fail'));

        try {
            $this->service->transition($order, OrderStatus::Submitted);
            $this->fail('Expected DomainException.');
        } catch (DomainException $exception) {
            $this->assertSame('hold fail', $exception->getMessage());
        }

        $this->assertSame(OrderStatus::PendingApproval, $order->fresh()->status);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_release_failure_rolls_back_status_and_history(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);
        $this->inventoryService->method('releaseForOrder')
            ->willThrowException(new DomainException('release fail'));

        try {
            $this->service->transition($order, OrderStatus::Cancelled);
            $this->fail('Expected DomainException.');
        } catch (DomainException $exception) {
            $this->assertSame('release fail', $exception->getMessage());
        }

        $this->assertSame(OrderStatus::Submitted, $order->fresh()->status);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_record_initial_status_is_idempotent_and_normalizes_note(): void
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

    public function test_record_initial_status_accepts_null_actor_and_empty_note(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Submitted]);

        $this->service->recordInitialStatus($order, null, '   ');

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'changed_by_user_id' => null,
            'note' => null,
        ]);
    }
}
