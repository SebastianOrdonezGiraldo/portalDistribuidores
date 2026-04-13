<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Policies\OrderPolicy;
use App\Modules\Shared\Enums\CompanyRole;
use App\Modules\Shared\Enums\DistributorStatus;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitarios para OrderPolicy.
 *
 * Instanciamos la policy directamente (sin middleware de gates)
 * para aislar la lógica pura de autorización.
 */
class OrderPolicyTest extends TestCase
{
    use RefreshDatabase;

    private OrderPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new OrderPolicy;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function makeDistributor(): Distributor
    {
        return Distributor::create([
            'name' => 'Distribuidora Test',
            'status' => DistributorStatus::Active,
        ]);
    }

    private function makeOrder(int $distributorId): Order
    {
        return Order::create([
            'distributor_id' => $distributorId,
            'oc_number' => 'CTC-TEST-01',
            'contact_name' => 'Test',
            'contact_email' => 'test@test.com',
            'company_name' => 'Empresa Test',
            'company_nit' => '123456',
            'company_address' => 'Calle 1',
            'city' => 'Bogotá',
            'status' => OrderStatus::Submitted,
            'total_amount' => 1000,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // viewAny
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_can_view_any_order(): void
    {
        $admin = User::factory()->admin()->make();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    public function test_distributor_can_view_any_order(): void
    {
        $distributor = User::factory()->make();

        $this->assertTrue($this->policy->viewAny($distributor));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // view
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_can_view_any_specific_order(): void
    {
        $distA = $this->makeDistributor();
        $order = $this->makeOrder($distA->id);
        $admin = User::factory()->admin()->make();

        $this->assertTrue($this->policy->view($admin, $order));
    }

    public function test_distributor_can_view_own_order(): void
    {
        $distA = $this->makeDistributor();
        $order = $this->makeOrder($distA->id);
        $userA = User::factory()->make(['distributor_id' => $distA->id]);

        $this->assertTrue($this->policy->view($userA, $order));
    }

    public function test_distributor_cannot_view_other_distributors_order(): void
    {
        $distA = $this->makeDistributor();
        $distB = $this->makeDistributor();
        $order = $this->makeOrder($distA->id);
        $userB = User::factory()->make(['distributor_id' => $distB->id]);

        $this->assertFalse($this->policy->view($userB, $order));
    }

    public function test_distributor_without_distributor_id_cannot_view_order(): void
    {
        $distA = $this->makeDistributor();
        $order = $this->makeOrder($distA->id);
        $user = User::factory()->make(['distributor_id' => null]);

        $this->assertFalse($this->policy->view($user, $order));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // create
    // ──────────────────────────────────────────────────────────────────────────

    public function test_distributor_can_create_order(): void
    {
        $user = User::factory()->make();

        $this->assertTrue($this->policy->create($user));
    }

    public function test_admin_cannot_create_order_via_policy(): void
    {
        // La policy reserva `create` solo a distribuidores.
        // Los admins crean pedidos por otro flujo si es necesario.
        $admin = User::factory()->admin()->make();

        $this->assertFalse($this->policy->create($admin));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // update
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_can_update_any_order(): void
    {
        $distA = $this->makeDistributor();
        $order = $this->makeOrder($distA->id);
        $admin = User::factory()->admin()->make();

        $this->assertTrue($this->policy->update($admin, $order));
    }

    public function test_distributor_can_update_own_order_when_role_allows_creating_orders(): void
    {
        $distA = $this->makeDistributor();
        $order = $this->makeOrder($distA->id);
        $userA = User::factory()->make(['distributor_id' => $distA->id]);

        $this->assertTrue($this->policy->update($userA, $order));
    }

    public function test_distributor_solo_lectura_cannot_update_order(): void
    {
        $distA = $this->makeDistributor();
        $order = $this->makeOrder($distA->id);
        $userA = User::factory()->make([
            'distributor_id' => $distA->id,
            'company_role' => CompanyRole::SoloLectura,
        ]);

        $this->assertFalse($this->policy->update($userA, $order));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // delete
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_can_delete_any_order(): void
    {
        $distA = $this->makeDistributor();
        $order = $this->makeOrder($distA->id);
        $admin = User::factory()->admin()->make();

        $this->assertTrue($this->policy->delete($admin, $order));
    }

    public function test_distributor_cannot_delete_order(): void
    {
        $distA = $this->makeDistributor();
        $order = $this->makeOrder($distA->id);
        $userA = User::factory()->make(['distributor_id' => $distA->id]);

        $this->assertFalse($this->policy->delete($userA, $order));
    }
}
