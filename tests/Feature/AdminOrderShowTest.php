<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminOrderShowTest extends TestCase
{
    use DatabaseTransactions;

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
