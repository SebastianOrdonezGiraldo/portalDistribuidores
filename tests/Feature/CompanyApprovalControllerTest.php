<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\CompanyRole;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_approvals_pages_are_not_available_for_company_panel(): void
    {
        [$order, $approver] = $this->makePendingApprovalOrder();

        $this->actingAs($approver)
            ->get('/empresa/aprobaciones')
            ->assertNotFound();

        $this->actingAs($approver)
            ->post("/empresa/aprobaciones/{$order->id}/aprobar")
            ->assertNotFound();

        $this->actingAs($approver)
            ->post("/empresa/aprobaciones/{$order->id}/rechazar", [
                'approval_note' => 'No procede',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PendingApproval->value,
        ]);
    }

    /**
     * @return array{0: Order, 1: User}
     */
    private function makePendingApprovalOrder(): array
    {
        $distributor = Distributor::factory()->create();
        $approver = User::factory()->create([
            'distributor_id' => $distributor->id,
            'company_role' => CompanyRole::AdminEmpresa,
        ]);
        $requester = User::factory()->create([
            'distributor_id' => $distributor->id,
            'company_role' => CompanyRole::UsuarioComercial,
        ]);

        $order = Order::factory()
            ->forDistributor($distributor)
            ->pendingApproval()
            ->create([
                'user_id' => $requester->id,
            ]);

        return [$order, $approver];
    }
}
