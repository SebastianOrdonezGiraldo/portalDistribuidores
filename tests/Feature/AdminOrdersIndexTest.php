<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminOrdersIndexTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_from_admin_orders_index(): void
    {
        $this->get('/admin/orders')
            ->assertRedirect('/login');
    }

    public function test_distributor_cannot_access_admin_orders_index(): void
    {
        $distributorUser = User::factory()->create([
            'role' => UserRole::Distributor,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($distributorUser)
            ->get('/admin/orders')
            ->assertForbidden();
    }

    public function test_admin_can_filter_and_sort_orders(): void
    {
        $admin = User::factory()->admin()->create([
            'email_verified_at' => now(),
        ]);
        $distributor = Distributor::create([
            'name' => 'Distribuidor UX Test',
            'status' => 'active',
        ]);

        $token = 'ZZ-FILTER-ORDERS';

        $lowValueOrder = $this->createOrder(
            $admin,
            $distributor,
            'CTC-UX-001',
            'Cliente '.$token.' Uno',
            OrderStatus::Submitted,
            50000
        );

        $this->createOrder(
            $admin,
            $distributor,
            'CTC-UX-002',
            'Cliente Sin Filtro',
            OrderStatus::Draft,
            999999
        );

        $highValueOrder = $this->createOrder(
            $admin,
            $distributor,
            'CTC-UX-003',
            'Cliente '.$token.' Dos',
            OrderStatus::Submitted,
            120000
        );

        $response = $this->actingAs($admin)->get('/admin/orders?status=submitted&q='.$token.'&sort=amount_desc&per_page=50');

        $response->assertOk();
        $response->assertSee('CTC-UX-001');
        $response->assertSee('CTC-UX-003');
        $response->assertDontSee('CTC-UX-002');

        $response->assertViewHas('orders', function ($orders) use ($highValueOrder, $lowValueOrder) {
            if ((int) $orders->total() !== 2) {
                return false;
            }

            $ids = $orders->getCollection()->pluck('id')->values();

            return $ids->first() === $highValueOrder->id
                && $ids->contains($lowValueOrder->id);
        });

        $response->assertViewHas('metrics', fn (array $metrics) => (int) $metrics['total_orders'] === 2);
    }

    private function createOrder(
        User $admin,
        Distributor $distributor,
        string $ocNumber,
        string $companyName,
        OrderStatus $status,
        int $totalAmount
    ): Order {
        return Order::create([
            'distributor_id' => $distributor->id,
            'user_id' => $admin->id,
            'oc_number' => $ocNumber,
            'contact_name' => 'Contacto Test',
            'contact_email' => 'contacto+'.$ocNumber.'@example.com',
            'company_name' => $companyName,
            'company_nit' => '900000001',
            'company_address' => 'Calle 10 # 20-30',
            'city' => 'Bogota',
            'phone' => '3000000000',
            'notes' => 'Orden de prueba admin',
            'status' => $status,
            'total_amount' => $totalAmount,
            'pdf_path' => null,
        ]);
    }
}
