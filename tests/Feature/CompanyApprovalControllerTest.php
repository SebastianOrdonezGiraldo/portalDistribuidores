<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
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
        [$order, $user] = $this->makePendingApprovalOrder();

        $this->actingAs($user)
            ->get('/empresa/aprobaciones')
            ->assertNotFound();

        $this->actingAs($user)
            ->post("/empresa/aprobaciones/{$order->id}/aprobar")
            ->assertNotFound();

        $this->actingAs($user)
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
        $user = User::factory()->create([
            'distributor_id' => $distributor->id,
        ]);

        $order = Order::factory()
            ->forDistributor($distributor)
            ->pendingApproval()
            ->create([
                'user_id' => $user->id,
            ]);

        return [$order, $user];
    }
}
