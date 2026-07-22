<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_order_show(): void
    {
        $order = $this->createOrderWithItem();

        $this->get('/admin/orders/'.$order->id)
            ->assertRedirect('/login');
    }

    public function test_distributor_cannot_access_admin_order_show(): void
    {
        $order = $this->createOrderWithItem();
        $distributorUser = User::factory()->create();

        $this->actingAs($distributorUser)
            ->get('/admin/orders/'.$order->id)
            ->assertForbidden();
    }

    public function test_admin_can_view_admin_order_detail_page(): void
    {
        $order = $this->createOrderWithItem();
        $admin = User::query()->findOrFail($order->user_id);

        $response = $this->actingAs($admin)
            ->get('/admin/orders/'.$order->id);

        $response->assertOk();
        $response->assertSee('Pedido '.$order->oc_number);
        $response->assertSee('Resumen Comercial');
        $response->assertSee('Ítems del Pedido');
        $response->assertSee('KIT-TEST-001');
        $response->assertSee('Checklist Operativo');
    }

    public function test_admin_can_open_edit_form_for_editable_status(): void
    {
        $order = $this->createOrderWithItem();
        $admin = User::query()->findOrFail($order->user_id);

        $this->actingAs($admin)
            ->get(route('admin.orders.edit', $order))
            ->assertOk()
            ->assertViewIs('admin.orders.edit')
            ->assertSee('Editar Pedido '.$order->oc_number);
    }

    public function test_admin_edit_price_hints_respect_order_tier(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['price' => 100000, 'is_active' => true]);

        $goldDistributor = Distributor::factory()->gold()->create();
        $goldOrder = Order::factory()->forDistributor($goldDistributor)->create([
            'status' => OrderStatus::Submitted,
            'user_id' => $admin->id,
            'distributor_tier_snapshot' => DistributorTier::Gold,
        ]);

        $goldOptions = $this->actingAs($admin)
            ->get(route('admin.orders.edit', $goldOrder))
            ->viewData('catalogOptions');

        $this->assertSame(100000.0, $this->hintPriceFor($goldOptions, 'p:'.$product->id));

        $silverDistributor = Distributor::factory()->silver()->create();
        $silverOrder = Order::factory()->forDistributor($silverDistributor)->create([
            'status' => OrderStatus::Submitted,
            'user_id' => $admin->id,
            'distributor_tier_snapshot' => DistributorTier::Silver,
        ]);

        $silverOptions = $this->actingAs($admin)
            ->get(route('admin.orders.edit', $silverOrder))
            ->viewData('catalogOptions');

        $this->assertSame(105000.0, $this->hintPriceFor($silverOptions, 'p:'.$product->id));
    }

    /**
     * @param  iterable<int, array{ref:string,label:string,price:float}>  $options
     */
    private function hintPriceFor(iterable $options, string $ref): float
    {
        foreach ($options as $option) {
            if ($option['ref'] === $ref) {
                return (float) $option['price'];
            }
        }

        $this->fail("No se encontró la opción de catálogo {$ref}.");
    }

    public function test_admin_cannot_open_edit_form_for_non_editable_status(): void
    {
        $order = $this->createOrderWithItem();
        $order->update(['status' => OrderStatus::Sold]);
        $admin = User::query()->findOrFail($order->user_id);

        $this->actingAs($admin)
            ->get(route('admin.orders.edit', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors();
    }

    public function test_admin_can_update_submitted_order_and_rebalance_inventory(): void
    {
        [$order, $product, $admin] = $this->createSubmittedOrderWithProductStock(3, 2);
        $order->update(['pdf_path' => 'orders/old-admin.pdf']);
        $item = $order->items()->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), [
                'contact_name' => 'Contacto Admin',
                'contact_email' => 'admin-edita@test.com',
                'phone' => '3004445555',
                'company_name' => 'Empresa Admin',
                'company_nit' => '9001234567',
                'company_address' => 'Calle 200 #30-40',
                'city' => 'Medellín',
                'department' => 'Antioquia',
                'notes' => 'Ajuste administrativo',
                'items' => [
                    [
                        'id' => $item->id,
                        'qty' => 4,
                        'unit_label' => 'caja',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'contact_name' => 'Contacto Admin',
            'contact_email' => 'admin-edita@test.com',
            'phone' => '3004445555',
            'company_name' => 'Empresa Admin',
            'company_nit' => '9001234567',
            'company_address' => 'Calle 200 #30-40',
            'city' => 'Medellín',
            'department' => 'Antioquia',
            'notes' => 'Ajuste administrativo',
            'status' => OrderStatus::Submitted->value,
            'total_amount' => 240000,
            'pdf_path' => null,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'qty' => 4,
            'unit_label' => 'caja',
            'subtotal' => 240000,
        ]);

        $this->assertEquals(1.0, (float) $product->fresh()->stock);
    }

    public function test_admin_can_remove_existing_item_and_add_new_product_on_submitted_order(): void
    {
        [$order, $productA, $admin] = $this->createSubmittedOrderWithProductStock(3, 2);
        $productB = Product::factory()->create([
            'price' => 45000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $item = $order->items()->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), [
                'contact_name' => 'Contacto Admin',
                'contact_email' => 'admin-edita@test.com',
                'phone' => '3004445555',
                'company_name' => 'Empresa Admin',
                'company_nit' => '9001234567',
                'company_address' => 'Calle 200 #30-40',
                'city' => 'Medellín',
                'department' => 'Antioquia',
                'notes' => 'Cambio de referencia',
                'items' => [
                    [
                        'id' => $item->id,
                        'qty' => 0,
                        'unit_label' => 'caja',
                    ],
                ],
                'new_items' => [
                    [
                        'catalog_ref' => 'p:'.$productB->id,
                        'qty' => 3,
                        'unit_label' => 'paquete',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        // El distribuidor es Plata por defecto: el ítem nuevo se valoriza al
        // precio Plata (45000 -> 48000) mediante el motor central.
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Submitted->value,
            'total_amount' => 144000,
        ]);

        $this->assertDatabaseMissing('order_items', [
            'order_id' => $order->id,
            'product_id' => $productA->id,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $productB->id,
            'qty' => 3,
            'unit_label' => 'paquete',
            'price_each' => 48000,
            'base_unit_price' => 45000,
            'silver_unit_price' => 48000,
            'subtotal' => 144000,
        ]);

        // El ítem anterior se devuelve a stock y el nuevo se descuenta.
        $this->assertEquals(5.0, (float) $productA->fresh()->stock);
        $this->assertEquals(2.0, (float) $productB->fresh()->stock);
    }

    public function test_admin_update_submitted_order_rolls_back_when_stock_is_insufficient(): void
    {
        [$order, $product, $admin] = $this->createSubmittedOrderWithProductStock(0, 2);
        $item = $order->items()->firstOrFail();
        $originalTotal = (float) $order->total_amount;
        $originalQty = (int) $item->qty;
        $originalSubtotal = (float) $item->subtotal;

        $this->from(route('admin.orders.edit', $order))
            ->actingAs($admin)
            ->put(route('admin.orders.update', $order), [
                'contact_name' => $order->contact_name,
                'contact_email' => $order->contact_email,
                'phone' => '3004445555',
                'company_name' => $order->company_name,
                'company_nit' => '9001234567',
                'company_address' => $order->company_address,
                'city' => $order->city,
                'department' => 'Antioquia',
                'notes' => $order->notes,
                'items' => [
                    [
                        'id' => $item->id,
                        'qty' => 5,
                        'unit_label' => 'caja',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.orders.edit', $order))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'total_amount' => $originalTotal,
            'status' => OrderStatus::Submitted->value,
        ]);

        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'order_id' => $order->id,
            'qty' => $originalQty,
            'subtotal' => $originalSubtotal,
        ]);

        $this->assertEquals(0.0, (float) $product->fresh()->stock);
    }

    public function test_admin_can_transition_status_and_history_is_recorded(): void
    {
        $order = $this->createOrderWithItem();
        $admin = User::query()->findOrFail($order->user_id);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Sold->value,
                'note' => 'Venta confirmada por telefono.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Sold->value,
        ]);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Submitted->value,
            'to_status' => OrderStatus::Sold->value,
            'changed_by_user_id' => $admin->id,
            'note' => 'Venta confirmada por telefono.',
        ]);
    }

    public function test_admin_transition_to_sold_requires_note(): void
    {
        $order = $this->createOrderWithItem();
        $admin = User::query()->findOrFail($order->user_id);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Sold->value,
                'note' => '',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Submitted->value,
        ]);
    }

    public function test_admin_transition_from_pending_approval_to_submitted_decreases_stock(): void
    {
        [$order, $product, $admin] = $this->createPendingApprovalOrderWithProductStock(5, 2);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Submitted->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Submitted->value,
        ]);
        $this->assertEquals(3.0, (float) $product->fresh()->stock);
    }

    public function test_admin_transition_from_pending_approval_to_submitted_fails_when_stock_is_insufficient(): void
    {
        [$order, $product, $admin] = $this->createPendingApprovalOrderWithProductStock(1, 2);

        $this->from('/admin/orders/'.$order->id)
            ->actingAs($admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Submitted->value,
            ])
            ->assertRedirect('/admin/orders/'.$order->id)
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PendingApproval->value,
        ]);
        $this->assertEquals(1.0, (float) $product->fresh()->stock);
    }

    public function test_admin_transition_from_submitted_to_cancelled_restores_stock(): void
    {
        [$order, $product, $admin] = $this->createSubmittedOrderWithProductStock(3, 2);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Cancelled->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
        ]);
        $this->assertEquals(5.0, (float) $product->fresh()->stock);
    }

    public function test_admin_transition_from_pending_approval_to_cancelled_does_not_restore_stock(): void
    {
        [$order, $product, $admin] = $this->createPendingApprovalOrderWithProductStock(5, 2);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Cancelled->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
        ]);
        $this->assertEquals(5.0, (float) $product->fresh()->stock);
    }

    /**
     * @return array{0: Order, 1: Product, 2: User}
     */
    private function createPendingApprovalOrderWithProductStock(float $stock, int $qty): array
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::create([
            'name' => 'Distribuidor Pending Test',
            'status' => 'active',
        ]);
        $product = Product::factory()->create([
            'price' => 60000,
            'stock' => $stock,
            'is_active' => true,
        ]);

        $order = Order::factory()
            ->forDistributor($distributor)
            ->pendingApproval()
            ->create([
                'user_id' => $admin->id,
            ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => $qty,
            'unit_label' => 'caja',
            'price_each' => 60000,
            'subtotal' => 60000 * $qty,
        ]);

        return [$order, $product, $admin];
    }

    /**
     * @return array{0: Order, 1: Product, 2: User}
     */
    private function createSubmittedOrderWithProductStock(float $stock, int $qty): array
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::create([
            'name' => 'Distribuidor Submitted Test',
            'status' => 'active',
        ]);
        $product = Product::factory()->create([
            'price' => 60000,
            'stock' => $stock,
            'is_active' => true,
        ]);

        $order = Order::factory()
            ->forDistributor($distributor)
            ->create([
                'status' => OrderStatus::Submitted,
                'user_id' => $admin->id,
            ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => $qty,
            'unit_label' => 'caja',
            'price_each' => 60000,
            'subtotal' => 60000 * $qty,
        ]);

        return [$order, $product, $admin];
    }

    private function createOrderWithItem(): Order
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::create([
            'name' => 'Distribuidor Show Test',
            'status' => 'active',
        ]);

        $order = Order::create([
            'distributor_id' => $distributor->id,
            'user_id' => $admin->id,
            'oc_number' => 'CTC-SHOW-001',
            'contact_name' => 'Contacto Show',
            'contact_email' => 'show@example.com',
            'company_name' => 'Empresa Show',
            'company_nit' => '900777000',
            'company_address' => 'Carrera 50 # 10-20',
            'city' => 'Bogota',
            'phone' => '3007770000',
            'notes' => 'Nota de prueba',
            'status' => OrderStatus::Submitted,
            'total_amount' => 120000,
            'pdf_path' => null,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot' => 'Producto Show',
            'sku_snapshot' => 'KIT-TEST-001',
            'qty' => 2,
            'unit_label' => 'caja',
            'price_each' => 60000,
            'subtotal' => 120000,
        ]);

        return $order;
    }
}
