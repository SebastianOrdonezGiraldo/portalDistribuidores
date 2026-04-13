<?php

namespace App\Modules\Orders\Services;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class OrderStatusTransitionService
{
    public function __construct(
        private readonly OrderInventoryService $orderInventoryService,
    ) {}

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
