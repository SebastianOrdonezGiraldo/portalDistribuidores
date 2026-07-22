<?php

namespace Tests\Unit;

use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Actions\UpdateOrderAction;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateOrderActionTest extends TestCase
{
    use RefreshDatabase;

    private function action(): UpdateOrderAction
    {
        return app(UpdateOrderAction::class);
    }

    /**
     * @param  array<int, array{id:int,qty:int,unit_label?:string}>  $items
     * @param  array<int, array{catalog_ref:string,qty:int,unit_label?:string}>  $newItems
     * @return array<string, mixed>
     */
    private function payload(array $items, array $newItems = []): array
    {
        return [
            'contact_name' => 'Contacto Editado',
            'contact_email' => 'editado@test.com',
            'phone' => '3001234567',
            'company_name' => 'Empresa Editada',
            'company_nit' => '9001234567',
            'company_address' => 'Calle 123',
            'city' => 'Bogota',
            'department' => 'Cundinamarca',
            'notes' => 'nota editada',
            'items' => $items,
            'new_items' => $newItems,
        ];
    }

    private function makeOrder(Distributor $distributor, ?DistributorTier $snapshot): Order
    {
        return Order::factory()
            ->forDistributor($distributor)
            ->pendingApproval()
            ->create([
                'distributor_tier_snapshot' => $snapshot,
                'total_amount' => 0,
            ]);
    }

    public function test_editing_silver_order_keeps_silver_prices(): void
    {
        $distributor = Distributor::factory()->silver()->create();
        $order = $this->makeOrder($distributor, DistributorTier::Silver);
        $product = Product::factory()->create(['price' => 100000, 'is_active' => true]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 1,
            'unit_label' => 'unidades',
            'price_each' => 105000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'unit_savings' => 0,
            'subtotal' => 105000,
            'line_savings' => 0,
        ]);

        $updated = $this->action()->execute($order, $this->payload([
            ['id' => $item->id, 'qty' => 2, 'unit_label' => 'unidades'],
        ]));

        $this->assertSame(DistributorTier::Silver, $updated->distributor_tier_snapshot);
        $this->assertSame('210000.00', $updated->total_amount);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'price_each' => 105000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'unit_savings' => 0,
            'subtotal' => 210000,
            'line_savings' => 0,
        ]);
    }

    public function test_editing_gold_order_keeps_gold_prices(): void
    {
        $distributor = Distributor::factory()->gold()->create();
        $order = $this->makeOrder($distributor, DistributorTier::Gold);
        $product = Product::factory()->create(['price' => 100000, 'is_active' => true]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 1,
            'unit_label' => 'unidades',
            'price_each' => 100000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'unit_savings' => 5000,
            'subtotal' => 100000,
            'line_savings' => 5000,
        ]);

        $updated = $this->action()->execute($order, $this->payload([
            ['id' => $item->id, 'qty' => 3, 'unit_label' => 'unidades'],
        ]));

        $this->assertSame(DistributorTier::Gold, $updated->distributor_tier_snapshot);
        $this->assertSame('300000.00', $updated->total_amount);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'price_each' => 100000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'unit_savings' => 5000,
            'subtotal' => 300000,
            'line_savings' => 15000,
        ]);
    }

    public function test_gold_order_keeps_gold_snapshot_after_company_downgraded_to_silver(): void
    {
        $distributor = Distributor::factory()->gold()->create();
        $order = $this->makeOrder($distributor, DistributorTier::Gold);
        $product = Product::factory()->create(['price' => 100000, 'is_active' => true]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 1,
            'unit_label' => 'unidades',
            'price_each' => 100000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'unit_savings' => 5000,
            'subtotal' => 100000,
            'line_savings' => 5000,
        ]);

        // The company is later downgraded; the order snapshot must win.
        $distributor->update(['tier' => DistributorTier::Silver]);

        $updated = $this->action()->execute($order->fresh(), $this->payload([
            ['id' => $item->id, 'qty' => 2, 'unit_label' => 'unidades'],
        ]));

        $this->assertSame(DistributorTier::Gold, $updated->distributor_tier_snapshot);
        $this->assertSame('200000.00', $updated->total_amount);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'price_each' => 100000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'subtotal' => 200000,
        ]);
    }

    public function test_silver_order_keeps_silver_snapshot_after_company_upgraded_to_gold(): void
    {
        $distributor = Distributor::factory()->silver()->create();
        $order = $this->makeOrder($distributor, DistributorTier::Silver);
        $product = Product::factory()->create(['price' => 100000, 'is_active' => true]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 1,
            'unit_label' => 'unidades',
            'price_each' => 105000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'unit_savings' => 0,
            'subtotal' => 105000,
            'line_savings' => 0,
        ]);

        $distributor->update(['tier' => DistributorTier::Gold]);

        $updated = $this->action()->execute($order->fresh(), $this->payload([
            ['id' => $item->id, 'qty' => 2, 'unit_label' => 'unidades'],
        ]));

        $this->assertSame(DistributorTier::Silver, $updated->distributor_tier_snapshot);
        $this->assertSame('210000.00', $updated->total_amount);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'price_each' => 105000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'subtotal' => 210000,
        ]);
    }

    public function test_historical_order_without_snapshot_resolves_tier_once_and_persists_it(): void
    {
        $distributor = Distributor::factory()->gold()->create();
        $order = $this->makeOrder($distributor, null);
        $product = Product::factory()->create(['price' => 100000, 'is_active' => true]);

        // Legacy line: no tier snapshot columns yet.
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 1,
            'unit_label' => 'unidades',
            'price_each' => 100000,
            'base_unit_price' => null,
            'silver_unit_price' => null,
            'unit_savings' => null,
            'subtotal' => 100000,
            'line_savings' => null,
        ]);

        $updated = $this->action()->execute($order, $this->payload([
            ['id' => $item->id, 'qty' => 2, 'unit_label' => 'unidades'],
        ]));

        // Tier resolved from the distributor (Gold) and persisted on the order.
        $this->assertSame(DistributorTier::Gold, $updated->distributor_tier_snapshot);
        $this->assertSame('200000.00', $updated->total_amount);

        // Line snapshots are completed while preserving the effective price.
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'price_each' => 100000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'unit_savings' => 5000,
            'subtotal' => 200000,
            'line_savings' => 10000,
        ]);
    }

    public function test_new_variant_line_uses_its_own_base_price_at_order_tier(): void
    {
        $distributor = Distributor::factory()->silver()->create();
        $order = $this->makeOrder($distributor, DistributorTier::Silver);

        $product = Product::factory()->create(['price' => 100000, 'is_active' => true]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 1,
            'unit_label' => 'unidades',
            'price_each' => 105000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'unit_savings' => 0,
            'subtotal' => 105000,
            'line_savings' => 0,
        ]);

        $variantProduct = Product::factory()->create(['price' => 1, 'is_active' => true]);
        $attribute = ProductAttribute::factory()->create(['name' => 'Talla']);
        $attributeValue = ProductAttributeValue::factory()->create([
            'product_attribute_id' => $attribute->id,
            'value' => 'M',
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $variantProduct->id,
            'product_attribute_value_id' => $attributeValue->id,
            'price' => 15000,
            'is_active' => true,
        ]);

        $updated = $this->action()->execute($order, $this->payload(
            [['id' => $item->id, 'qty' => 1, 'unit_label' => 'unidades']],
            [['catalog_ref' => 'v:'.$variant->id, 'qty' => 2, 'unit_label' => 'unidades']],
        ));

        // 15000 -> Plata 16000 using the variant's own base price.
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'price_each' => 16000,
            'base_unit_price' => 15000,
            'silver_unit_price' => 16000,
            'subtotal' => 32000,
        ]);

        // Existing line preserved + new line = consistent total.
        $this->assertSame('137000.00', $updated->total_amount);
    }
}
