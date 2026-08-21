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
 * records audit history and applies local HOLD side effects when crossing the
 * set of statuses that consume stock. ContaPyme stock synchronization is an
 * independent process and must not block a status transition.
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

        if ($toStatus === OrderStatus::Dispatched && ! $order->canDispatchRegardingPayment()) {
            throw new DomainException(
                'No se puede despachar este pedido hasta validar el pago (o marcar la cotización sin pago). Estado de pago: '.$order->payment_status->label().'.'
            );
        }

        if ($fromStatus === OrderStatus::Submitted && $toStatus === OrderStatus::Sold) {
            return $this->transitionSubmittedToSold($order, $actor, $normalizedNote);
        }

        return DB::transaction(function () use ($order, $fromStatus, $toStatus, $actor, $normalizedNote): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== $fromStatus) {
                throw new DomainException('El pedido cambió de estado mientras se procesaba la solicitud. Intenta de nuevo.');
            }

            $updates = [
                'status' => $toStatus,
            ];

            if ($toStatus === OrderStatus::Sold) {
                // A sale only releases the local HOLD. Any stale legacy
                // reconciliation state must not continue to describe the order
                // as blocked by ContaPyme.
                $updates['inventory_reconciliation_status'] = null;
                $updates['inventory_reconciliation_attempted_at'] = null;
                $updates['inventory_reconciled_at'] = null;
                $updates['inventory_reconciliation_error'] = null;
            }

            $lockedOrder->update($updates);

            $lockedOrder->statusHistory()->create([
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'changed_by_user_id' => $actor?->id,
                'note' => $normalizedNote,
            ]);

            if (! $this->statusConsumesInventory($fromStatus) && $this->statusConsumesInventory($toStatus)) {
                $this->orderInventoryService->holdForOrder($lockedOrder, $actor, 'status_submitted');
            }

            if ($this->statusConsumesInventory($fromStatus) && ! $this->statusConsumesInventory($toStatus)) {
                $this->orderInventoryService->releaseForOrder($lockedOrder, $actor, 'status_'.$toStatus->value);
            }

            return $lockedOrder->refresh();
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
        return $status === OrderStatus::Submitted;
    }

    private function transitionSubmittedToSold(
        Order $order,
        ?User $actor,
        ?string $note,
    ): Order {
        return DB::transaction(function () use ($order, $actor, $note): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== OrderStatus::Submitted) {
                throw new DomainException('Solo un pedido Registrado puede marcarse como Vendido.');
            }

            if (! $this->orderInventoryService->hasActiveHold($locked)) {
                throw new DomainException('El pedido Registrado no tiene un HOLD activo. Corrige la reserva antes de vender.');
            }

            $this->orderInventoryService->releaseForOrder($locked, $actor, 'status_sold');

            $locked->update([
                'status' => OrderStatus::Sold,
                'inventory_reconciliation_status' => null,
                'inventory_reconciliation_attempted_at' => null,
                'inventory_reconciled_at' => null,
                'inventory_reconciliation_error' => null,
            ]);

            $locked->statusHistory()->create([
                'from_status' => OrderStatus::Submitted->value,
                'to_status' => OrderStatus::Sold->value,
                'changed_by_user_id' => $actor?->id,
                'note' => $note,
            ]);

            return $locked->refresh();
        });
    }
}
