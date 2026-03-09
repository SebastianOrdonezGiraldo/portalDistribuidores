<?php

namespace App\Modules\Orders\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\DTOs\CreateOrderData;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrderAction
{
    public function execute(?User $user, CreateOrderData $data): Order
    {
        $productIds = collect($data->items)->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->all();
        $products = Product::query()->whereIn('id', $productIds)->where('is_active', true)->get()->keyBy('id');

        if ($products->isEmpty()) {
            throw new DomainException('No hay productos válidos en el pedido.');
        }

        $order = DB::transaction(function () use ($user, $data, $products) {
            $order = Order::create([
                'distributor_id' => $user?->distributor_id,
                'user_id' => $user?->id,
                'oc_number' => 'CTC-TMP-'.Str::upper(Str::random(8)),
                'contact_name' => $data->contactName,
                'contact_email' => $data->contactEmail,
                'company_name' => $data->companyName,
                'company_nit' => $data->companyNit,
                'company_address' => $data->companyAddress,
                'city' => $data->city,
                'phone' => '',
                'notes' => $data->notes,
                'status' => OrderStatus::Submitted,
                'total_amount' => 0,
            ]);

            $total = 0;

            foreach ($data->items as $item) {
                $product = $products->get((int) $item['product_id']);

                if (! $product) {
                    continue;
                }

                $qty = max(1, (int) $item['qty']);
                $priceEach = (float) $product->price;
                $subtotal = $qty * $priceEach;
                $total += $subtotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'sku_snapshot' => $product->sku,
                    'qty' => $qty,
                    'unit_label' => $item['unit_label'] ?? 'unidad',
                    'price_each' => $priceEach,
                    'subtotal' => $subtotal,
                ]);
            }

            if ($total <= 0) {
                throw new DomainException('El carrito no puede generar una orden vacía.');
            }

            $order->update([
                'total_amount' => $total,
                'oc_number' => sprintf('CTC-%06d', $order->id),
            ]);

            return $order->refresh();
        });

        event(new OrderPlaced($order));

        return $order;
    }
}
