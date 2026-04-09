<?php

namespace App\Modules\Orders\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class UpdateOrderAction
{
    public function __construct(
        private readonly OrderInventoryService $orderInventoryService,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(
        Order $order,
        array $payload,
        ?User $actor = null,
        bool $allowSubmittedEdit = false,
    ): Order
    {
        $preparedItems = $this->prepareItems(
            $order,
            $payload['items'] ?? [],
            $payload['new_items'] ?? [],
        );
        $total = (float) collect($preparedItems)->sum('subtotal');

        return DB::transaction(function () use ($order, $payload, $preparedItems, $total, $actor, $allowSubmittedEdit): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $isEditable = $allowSubmittedEdit
                ? $lockedOrder->status->canBeEditedByAdmin()
                : $lockedOrder->status->canBeEditedByCompany();

            if (! $isEditable) {
                throw new DomainException('Esta cotización no puede editarse en su estado actual.');
            }

            $statusConsumesInventory = $this->statusConsumesInventory($lockedOrder->status);
            if ($statusConsumesInventory) {
                // Devuelve stock de la versión actual para recalcular con los nuevos ítems.
                $this->orderInventoryService->increaseForOrder($lockedOrder);
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
                'note' => $allowSubmittedEdit
                    ? 'Cotización actualizada por administrador.'
                    : 'Cotización actualizada por el cliente.',
                'metadata' => [
                    'type' => 'order_updated',
                    'scope' => $allowSubmittedEdit ? 'admin' : 'company',
                ],
            ]);

            if ($statusConsumesInventory) {
                // Aplica consumo con los ítems actualizados.
                $this->orderInventoryService->decreaseForOrder($lockedOrder);
            }

            return $lockedOrder->refresh();
        });
    }

    /**
     * @param mixed $rawItems
     * @param mixed $rawNewItems
     * @return array<int, array<string, mixed>>
     */
    private function prepareItems(Order $order, mixed $rawItems, mixed $rawNewItems = []): array
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

        $prepared = [...$prepared, ...$this->prepareNewItems($rawNewItems)];

        if ($prepared === []) {
            throw new DomainException('La cotización debe conservar al menos un ítem con cantidad mayor a cero.');
        }

        return $prepared;
    }

    /**
     * @param mixed $rawNewItems
     * @return array<int, array<string, mixed>>
     */
    private function prepareNewItems(mixed $rawNewItems): array
    {
        if (! is_array($rawNewItems) || $rawNewItems === []) {
            return [];
        }

        $rows = [];
        $productIds = [];
        $variantIds = [];

        foreach ($rawNewItems as $row) {
            if (! is_array($row)) {
                continue;
            }

            $catalogRef = trim((string) ($row['catalog_ref'] ?? ''));
            $qty = max(0, (int) ($row['qty'] ?? 0));
            $unitLabel = trim((string) ($row['unit_label'] ?? 'unidades'));

            if ($catalogRef === '' || $qty <= 0) {
                continue;
            }

            if ($unitLabel === '') {
                $unitLabel = 'unidades';
            }

            $parsedRef = $this->parseCatalogRef($catalogRef);
            if (! $parsedRef) {
                throw new DomainException('Se enviaron productos nuevos inválidos para esta cotización.');
            }

            $rows[] = [
                'type' => $parsedRef['type'],
                'id' => $parsedRef['id'],
                'qty' => $qty,
                'unit_label' => $unitLabel,
            ];

            if ($parsedRef['type'] === 'product') {
                $productIds[] = $parsedRef['id'];
            } else {
                $variantIds[] = $parsedRef['id'];
            }
        }

        if ($rows === []) {
            return [];
        }

        $products = Product::query()
            ->active()
            ->whereIn('id', array_values(array_unique($productIds)))
            ->withCount(['variants as active_variants_count' => fn ($query) => $query->active()])
            ->get()
            ->keyBy('id');

        $variants = ProductVariant::query()
            ->active()
            ->whereIn('id', array_values(array_unique($variantIds)))
            ->whereHas('product', fn ($query) => $query->active())
            ->with('product:id,name,sku,price', 'attributeValue.attribute')
            ->get()
            ->keyBy('id');

        $prepared = [];

        foreach ($rows as $row) {
            if ($row['type'] === 'product') {
                /** @var Product|null $product */
                $product = $products->get($row['id']);
                if (! $product) {
                    throw new DomainException('Uno de los productos seleccionados ya no está disponible.');
                }

                if ((int) ($product->active_variants_count ?? 0) > 0) {
                    throw new DomainException("El producto {$product->sku} requiere seleccionar una variante.");
                }

                $priceEach = (float) $product->price;

                $prepared[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                    'product_name_snapshot' => $product->name,
                    'sku_snapshot' => $product->sku,
                    'variant_attribute_snapshot' => null,
                    'variant_value_snapshot' => null,
                    'qty' => $row['qty'],
                    'unit_label' => $row['unit_label'],
                    'price_each' => $priceEach,
                    'subtotal' => round($row['qty'] * $priceEach, 2),
                ];

                continue;
            }

            /** @var ProductVariant|null $variant */
            $variant = $variants->get($row['id']);
            if (! $variant) {
                throw new DomainException('Una de las variantes seleccionadas ya no está disponible.');
            }

            /** @var Product|null $product */
            $product = $variant->product;
            if (! $product) {
                throw new DomainException('No fue posible resolver el producto de la variante seleccionada.');
            }

            $priceEach = (float) $variant->price;

            $prepared[] = [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'product_name_snapshot' => $product->name,
                'sku_snapshot' => $product->sku,
                'variant_attribute_snapshot' => $variant->attributeValue?->attribute?->name,
                'variant_value_snapshot' => $variant->attributeValue?->value,
                'qty' => $row['qty'],
                'unit_label' => $row['unit_label'],
                'price_each' => $priceEach,
                'subtotal' => round($row['qty'] * $priceEach, 2),
            ];
        }

        return $prepared;
    }

    /**
     * @return array{type: 'product'|'variant', id: int}|null
     */
    private function parseCatalogRef(string $catalogRef): ?array
    {
        if (! preg_match('/^(p|v):(\d+)$/', $catalogRef, $matches)) {
            return null;
        }

        return [
            'type' => $matches[1] === 'p' ? 'product' : 'variant',
            'id' => (int) $matches[2],
        ];
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
