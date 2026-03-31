<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Enums\CompanyRole;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CompanyApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_approval_moves_order_to_submitted_and_decreases_stock(): void
    {
        Event::fake([OrderPlaced::class]);

        [$order, $product, $approver] = $this->makePendingApprovalOrderWithStock(5, 2);

        $this->actingAs($approver)
            ->post(route('empresa.approvals.approve', $order))
            ->assertRedirect(route('empresa.approvals.index'));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Submitted->value,
        ]);
        $this->assertEquals(3.0, (float) $product->fresh()->stock);

        Event::assertDispatched(OrderPlaced::class, fn (OrderPlaced $event) => (int) $event->order->id === (int) $order->id);
    }

    public function test_approval_keeps_order_pending_when_stock_is_insufficient(): void
    {
        Event::fake([OrderPlaced::class]);

        [$order, $product, $approver] = $this->makePendingApprovalOrderWithStock(1, 2);

        $this->from(route('empresa.approvals.index'))
            ->actingAs($approver)
            ->post(route('empresa.approvals.approve', $order))
            ->assertRedirect(route('empresa.approvals.index'))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PendingApproval->value,
        ]);
        $this->assertEquals(1.0, (float) $product->fresh()->stock);

        Event::assertNotDispatched(OrderPlaced::class);
    }

    /**
     * @return array{0: Order, 1: Product, 2: User}
     */
    private function makePendingApprovalOrderWithStock(float $stock, int $qty): array
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
        $product = Product::factory()->create([
            'price' => 25000,
            'stock' => $stock,
            'is_active' => true,
        ]);

        $order = Order::factory()
            ->forDistributor($distributor)
            ->pendingApproval()
            ->create([
                'user_id' => $requester->id,
            ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => $qty,
            'price_each' => 25000,
            'subtotal' => 25000 * $qty,
        ]);

        return [$order, $product, $approver];
    }
}
