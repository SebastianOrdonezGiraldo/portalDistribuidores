<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
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
