<?php

namespace App\Modules\Orders\Services;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Centralizes legal order status transitions and their inventory side effects.
 *
 * Callers should not update order.status directly when a transition may change
 * stock or requires status history. This service validates the enum transition,
 * records audit history and reconciles inventory when crossing the set of
 * statuses that consume stock.
 */
class OrderStatusTransitionService
{
    public function __construct(
        private readonly OrderInventoryService $orderInventoryService,
    ) {}

    /**
     * Move an order to a new status and record the change atomically.
     *
     * @throws DomainException when the transition is illegal or misses a required note
     */
    public function transition(
        Order $order,
        OrderStatus $toStatus,
        ?User $actor = null,
        ?string $note = null,
    ): Order {
        $fromStatus = $order->status;

        if ($fromStatus === $toStatus) {
            throw new DomainException('La cotización ya se encuentra en el estado seleccionado.');
        }

        if (! $fromStatus->canTransitionTo($toStatus)) {
            throw new DomainException("No se permite cambiar de {$fromStatus->value} a {$toStatus->value}.");
        }

        $normalizedNote = $this->normalizeNote($note);

        if ($toStatus->requiresTransitionNote() && $normalizedNote === null) {
            throw new DomainException('Este cambio de estado requiere una nota de trazabilidad.');
        }

        return DB::transaction(function () use ($order, $fromStatus, $toStatus, $actor, $normalizedNote): Order {
            $order->update([
                'status' => $toStatus,
            ]);

            $order->statusHistory()->create([
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'changed_by_user_id' => $actor?->id,
                'note' => $normalizedNote,
            ]);

            if (! $this->statusConsumesInventory($fromStatus) && $this->statusConsumesInventory($toStatus)) {
                $this->orderInventoryService->decreaseForOrder($order);
            }

            if ($this->statusConsumesInventory($fromStatus) && ! $this->statusConsumesInventory($toStatus)) {
                $this->orderInventoryService->increaseForOrder($order);
            }

            return $order->refresh();
        });
    }

    /**
     * Store the initial status history row once after order creation.
     */
    public function recordInitialStatus(Order $order, ?User $actor = null, ?string $note = null): void
    {
        if ($order->statusHistory()->exists()) {
            return;
        }

        $order->statusHistory()->create([
            'from_status' => null,
            'to_status' => $order->status->value,
            'changed_by_user_id' => $actor?->id,
            'note' => $this->normalizeNote($note),
        ]);
    }

    private function normalizeNote(?string $note): ?string
    {
        $trimmed = trim((string) $note);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * Whether this status should hold stock out of available inventory.
     */
    private function statusConsumesInventory(OrderStatus $status): bool
    {
        return in_array($status, [
            OrderStatus::Submitted,
            OrderStatus::Sold,
            OrderStatus::Dispatched,
            OrderStatus::Delivered,
        ], true);
    }
}
