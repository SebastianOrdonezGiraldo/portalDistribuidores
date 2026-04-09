<?php

namespace App\Modules\Orders\Actions;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class UpdateOrderAction
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(Order $order, array $payload, ?User $actor = null): Order
    {
        $preparedItems = $this->prepareItems($order, $payload['items'] ?? []);
        $total = (float) collect($preparedItems)->sum('subtotal');

        return DB::transaction(function () use ($order, $payload, $preparedItems, $total, $actor): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedOrder->status->canBeEditedByCompany()) {
                throw new DomainException('Solo puedes editar cotizaciones en revisión o rechazadas.');
            }

            $lockedOrder->items()->delete();

            foreach ($preparedItems as $itemData) {
                $lockedOrder->items()->create($itemData);
            }

            $lockedOrder->update([
                'contact_name' => $payload['contact_name'],
                'contact_email' => $payload['contact_email'],
                'phone' => $payload['phone'],
                'company_name' => $payload['company_name'],
                'company_nit' => $payload['company_nit'],
                'company_address' => $payload['company_address'],
                'city' => $payload['city'],
                'department' => $payload['department'],
                'notes' => $payload['notes'] ?? null,
                'total_amount' => $total,
                // Invalida el archivo actual para forzar regeneración con datos nuevos.
                'pdf_path' => null,
            ]);

            $lockedOrder->statusHistory()->create([
                'from_status' => $lockedOrder->status->value,
                'to_status' => $lockedOrder->status->value,
                'changed_by_user_id' => $actor?->id,
                'note' => 'Cotización actualizada por el cliente.',
                'metadata' => [
                    'type' => 'order_updated',
                ],
            ]);

            return $lockedOrder->refresh();
        });
    }

    /**
     * @param mixed $rawItems
     * @return array<int, array<string, mixed>>
     */
    private function prepareItems(Order $order, mixed $rawItems): array
    {
        if (! is_array($rawItems) || $rawItems === []) {
            throw new DomainException('Debes enviar los ítems de la cotización.');
        }

        /** @var \Illuminate\Support\Collection<int, OrderItem> $existingItems */
        $existingItems = $order->items()->get()->keyBy('id');
        $prepared = [];
        $seen = [];

        foreach ($rawItems as $row) {
            if (! is_array($row)) {
                continue;
            }

            $itemId = (int) ($row['id'] ?? 0);
            $qty = max(0, (int) ($row['qty'] ?? 0));
            $unitLabel = trim((string) ($row['unit_label'] ?? 'unidades'));

            if ($itemId <= 0 || isset($seen[$itemId])) {
                continue;
            }

            $seen[$itemId] = true;

            /** @var OrderItem|null $existing */
            $existing = $existingItems->get($itemId);
            if (! $existing) {
                throw new DomainException('Se enviaron ítems inválidos para esta cotización.');
            }

            if ($qty <= 0) {
                continue;
            }

            if ($unitLabel === '') {
                $unitLabel = 'unidades';
            }

            $priceEach = (float) $existing->price_each;
            $prepared[] = [
                'product_id' => $existing->product_id,
                'product_variant_id' => $existing->product_variant_id,
                'product_name_snapshot' => $existing->product_name_snapshot,
                'sku_snapshot' => $existing->sku_snapshot,
                'variant_attribute_snapshot' => $existing->variant_attribute_snapshot,
                'variant_value_snapshot' => $existing->variant_value_snapshot,
                'qty' => $qty,
                'unit_label' => $unitLabel,
                'price_each' => $priceEach,
                'subtotal' => round($qty * $priceEach, 2),
            ];
        }

        if ($prepared === []) {
            throw new DomainException('La cotización debe conservar al menos un ítem con cantidad mayor a cero.');
        }

        return $prepared;
    }
}

