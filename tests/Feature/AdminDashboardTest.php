<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $this->get('/admin')
            ->assertRedirect('/login');
    }

    public function test_distributor_cannot_access_admin_dashboard(): void
    {
        $distributorUser = User::factory()->create();

        $this->actingAs($distributorUser)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_view_dashboard_with_recent_order_data(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::create([
            'name' => 'Distribuidor Test',
            'status' => 'active',
        ]);

        $order = Order::create([
            'distributor_id' => $distributor->id,
            'user_id' => $admin->id,
            'oc_number' => 'CTC-000999',
            'contact_name' => 'Test Contact',
            'contact_email' => 'test@example.com',
            'company_name' => 'Empresa Test',
            'company_nit' => '900123456',
            'company_address' => 'Calle 1 # 2-3',
            'city' => 'Bogota',
            'phone' => '3001234567',
            'notes' => 'Pedido de prueba',
            'status' => OrderStatus::Submitted,
            'total_amount' => 150000,
            'pdf_path' => null,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        $response->assertSee('Dashboard Operativo');
        $response->assertSee('Pedidos Recientes');
        $response->assertSee('Pedido '.$order->oc_number);
    }
}
