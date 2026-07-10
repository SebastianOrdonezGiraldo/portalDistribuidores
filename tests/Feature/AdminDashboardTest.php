<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

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
        $response->assertSee('Panel operativo');
        $response->assertSee('Pedidos Recientes');
        $response->assertSee('Pedido '.$order->oc_number);
    }

    public function test_guest_is_redirected_from_sync_endpoint(): void
    {
        $this->post('/admin/stock/sync')
            ->assertRedirect('/login');
    }

    public function test_distributor_cannot_access_sync_endpoint(): void
    {
        $distributorUser = User::factory()->create();

        $this->actingAs($distributorUser)
            ->post('/admin/stock/sync')
            ->assertForbidden();
    }

    public function test_admin_can_trigger_sync(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->post('/admin/stock/sync');

        $response->assertRedirect('/admin');
        $response->assertSessionHas('success');
    }

    public function test_sync_mutex_prevents_concurrent_runs(): void
    {
        $admin = User::factory()->admin()->create();
        Cache::set('contapyme_sync_running', true, 600);

        $response = $this->actingAs($admin)
            ->post('/admin/stock/sync');

        $response->assertRedirect('/admin');
        $response->assertSessionHas('error', fn (string $msg) => str_contains($msg, 'Ya hay una sincronización en curso'));
    }

    public function test_dashboard_shows_contapyme_stats(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        $response->assertSee('Sincronización ContaPyme');
        $response->assertSee('Estado');
        $response->assertSee('Última sincronización');
        $response->assertSee('Sincronizados');
    }
}
