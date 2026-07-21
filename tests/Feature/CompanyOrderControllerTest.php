<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Jobs\GenerateOrderPdfJob;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Services\OrderPdfGenerator;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function makeDistributorWithUser(): array
    {
        $distributor = Distributor::factory()->create();
        $user = User::factory()->create([
            'distributor_id' => $distributor->id,
        ]);

        return [$distributor, $user];
    }

    private function makeOrderWithItem(Distributor $distributor, array $orderOverrides = []): Order
    {
        $order = Order::factory()->forDistributor($distributor)->create($orderOverrides);
        $product = Product::factory()->create(['price' => 5000]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 2,
            'unit_label' => 'unidades',
            'price_each' => 5000,
            'subtotal' => 10000,
        ]);

        return $order;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // index – listado de pedidos
    // ──────────────────────────────────────────────────────────────────────────

    public function test_index_shows_only_own_distributor_orders(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        [$distB] = $this->makeDistributorWithUser();

        Order::factory()->forDistributor($distA)->count(3)->create();
        Order::factory()->forDistributor($distB)->count(2)->create();

        $response = $this->actingAs($userA)->get(route('empresa.orders.index'));

        $response->assertOk()->assertViewIs('empresa.orders.index');

        $orders = $response->viewData('orders');
        $this->assertCount(3, $orders);
    }

    public function test_index_filters_by_q_matching_oc_number(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();

        Order::factory()->forDistributor($distA)->create(['oc_number' => 'CTC-000001']);
        Order::factory()->forDistributor($distA)->create(['oc_number' => 'CTC-000002']);

        $response = $this->actingAs($userA)
            ->get(route('empresa.orders.index', ['q' => 'CTC-000001']));

        $orders = $response->viewData('orders');
        $this->assertCount(1, $orders);
        $this->assertEquals('CTC-000001', $orders->first()->oc_number);
    }

    public function test_index_filters_by_status(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();

        Order::factory()->forDistributor($distA)->create(['status' => OrderStatus::Submitted]);
        Order::factory()->forDistributor($distA)->create(['status' => OrderStatus::PendingApproval]);
        Order::factory()->forDistributor($distA)->create(['status' => OrderStatus::Sent]);

        $response = $this->actingAs($userA)
            ->get(route('empresa.orders.index', ['status' => 'submitted']));

        $orders = $response->viewData('orders');
        $this->assertCount(1, $orders);
    }

    public function test_index_filters_by_visual_status_group(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();

        Order::factory()->forDistributor($distA)->create(['status' => OrderStatus::Submitted]);
        Order::factory()->forDistributor($distA)->create(['status' => OrderStatus::Dispatched]);
        Order::factory()->forDistributor($distA)->create(['status' => OrderStatus::Delivered]);
        Order::factory()->forDistributor($distA)->create(['status' => OrderStatus::Rejected]);

        $response = $this->actingAs($userA)
            ->get(route('empresa.orders.index', ['group' => 'active']));

        $orders = $response->viewData('orders');
        $this->assertCount(2, $orders);
        $this->assertSame(2, $response->viewData('groupSummary')['active']);
    }

    public function test_index_filters_by_creation_date_range(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();

        Order::factory()->forDistributor($distA)->create(['created_at' => '2026-07-05 10:00:00']);
        Order::factory()->forDistributor($distA)->create(['created_at' => '2026-07-18 10:00:00']);

        $response = $this->actingAs($userA)->get(route('empresa.orders.index', [
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-10',
        ]));

        $this->assertCount(1, $response->viewData('orders'));
    }

    public function test_index_eager_loads_status_history_for_expandable_timeline(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        $order = Order::factory()->forDistributor($distA)->create(['status' => OrderStatus::Sold]);
        $order->statusHistory()->create([
            'from_status' => OrderStatus::Submitted->value,
            'to_status' => OrderStatus::Sold->value,
            'changed_by_user_id' => $userA->id,
            'note' => 'Venta confirmada.',
        ]);

        $response = $this->actingAs($userA)->get(route('empresa.orders.index'));
        $listedOrder = $response->viewData('orders')->first();

        $this->assertTrue($listedOrder->relationLoaded('statusHistory'));
        $this->assertTrue($listedOrder->statusHistory->first()->relationLoaded('actor'));
        $response->assertSee('Venta confirmada.');
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('empresa.orders.index'))
            ->assertRedirect(route('login'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // show – detalle de pedido
    // ──────────────────────────────────────────────────────────────────────────

    public function test_show_renders_own_order(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        $order = $this->makeOrderWithItem($distA);

        $this->actingAs($userA)
            ->get(route('empresa.orders.show', $order))
            ->assertOk()
            ->assertViewIs('empresa.orders.show')
            ->assertViewHas('order');
    }

    public function test_show_forbids_other_distributors_order(): void
    {
        [$distA] = $this->makeDistributorWithUser();
        [, $userB] = $this->makeDistributorWithUser();

        $order = Order::factory()->forDistributor($distA)->create();

        $this->actingAs($userB)
            ->get(route('empresa.orders.show', $order))
            ->assertForbidden();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // edit / update
    // ──────────────────────────────────────────────────────────────────────────

    public function test_edit_renders_for_pending_approval_order(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        $order = $this->makeOrderWithItem($distA, [
            'status' => OrderStatus::PendingApproval,
        ]);

        $this->actingAs($userA)
            ->get(route('empresa.orders.edit', $order))
            ->assertOk()
            ->assertViewIs('empresa.orders.edit');
    }

    public function test_edit_redirects_when_order_status_is_not_editable(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        $order = $this->makeOrderWithItem($distA, [
            'status' => OrderStatus::Submitted,
        ]);

        $this->actingAs($userA)
            ->get(route('empresa.orders.edit', $order))
            ->assertRedirect(route('empresa.orders.show', $order))
            ->assertSessionHasErrors();
    }

    public function test_update_pending_approval_order_updates_fields_items_and_invalidates_pdf(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        $order = $this->makeOrderWithItem($distA, [
            'status' => OrderStatus::PendingApproval,
            'pdf_path' => 'orders/old-cotizacion.pdf',
        ]);

        $item = $order->items()->firstOrFail();

        $response = $this->actingAs($userA)
            ->from(route('empresa.orders.edit', $order))
            ->put(route('empresa.orders.update', $order), [
                'contact_name' => 'Contacto Actualizado',
                'contact_email' => 'actualizado@empresa.test',
                'phone' => '3009990000',
                'company_name' => 'Empresa Ajustada',
                'company_nit' => '9001234567',
                'company_address' => 'Calle 11 #22-33',
                'city' => 'Medellín',
                'department' => 'Antioquia',
                'notes' => 'nota nueva',
                'items' => [
                    [
                        'id' => $item->id,
                        'qty' => 3,
                        'unit_label' => 'cajas',
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('empresa.orders.show', $order))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'contact_name' => 'Contacto Actualizado',
            'contact_email' => 'actualizado@empresa.test',
            'phone' => '3009990000',
            'company_name' => 'Empresa Ajustada',
            'company_nit' => '9001234567',
            'company_address' => 'Calle 11 #22-33',
            'city' => 'Medellín',
            'department' => 'Antioquia',
            'notes' => 'nota nueva',
            'status' => OrderStatus::PendingApproval->value,
            'total_amount' => 15000,
            'pdf_path' => null,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'qty' => 3,
            'unit_label' => 'cajas',
            'price_each' => 5000,
            'subtotal' => 15000,
        ]);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::PendingApproval->value,
            'to_status' => OrderStatus::PendingApproval->value,
            'changed_by_user_id' => $userA->id,
            'note' => 'Cotización actualizada por el cliente.',
        ]);
    }

    public function test_update_pending_approval_order_can_remove_existing_item_and_add_new_product(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        $order = $this->makeOrderWithItem($distA, [
            'status' => OrderStatus::PendingApproval,
        ]);

        $item = $order->items()->firstOrFail();
        $newProduct = Product::factory()->create([
            'price' => 7000,
            'is_active' => true,
        ]);

        $this->actingAs($userA)
            ->put(route('empresa.orders.update', $order), [
                'contact_name' => $order->contact_name,
                'contact_email' => $order->contact_email,
                'phone' => '3002223333',
                'company_name' => $order->company_name,
                'company_nit' => '9001234567',
                'company_address' => $order->company_address,
                'city' => $order->city,
                'department' => 'Antioquia',
                'notes' => 'Cambio de producto',
                'items' => [
                    [
                        'id' => $item->id,
                        'qty' => 0,
                        'unit_label' => 'unidades',
                    ],
                ],
                'new_items' => [
                    [
                        'catalog_ref' => 'p:'.$newProduct->id,
                        'qty' => 4,
                        'unit_label' => 'cajas',
                    ],
                ],
            ])
            ->assertRedirect(route('empresa.orders.show', $order))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PendingApproval->value,
            'total_amount' => 28000,
        ]);

        $this->assertDatabaseMissing('order_items', [
            'order_id' => $order->id,
            'product_id' => $item->product_id,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $newProduct->id,
            'qty' => 4,
            'unit_label' => 'cajas',
            'price_each' => 7000,
            'subtotal' => 28000,
        ]);
    }

    public function test_update_rejected_order_resubmits_for_approval_and_clears_reject_note(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        $order = $this->makeOrderWithItem($distA, [
            'status' => OrderStatus::Rejected,
            'approval_note' => 'Falta presupuesto',
        ]);

        $item = $order->items()->firstOrFail();

        $response = $this->actingAs($userA)
            ->put(route('empresa.orders.update', $order), [
                'contact_name' => $order->contact_name,
                'contact_email' => $order->contact_email,
                'phone' => $order->phone ?? '3001112222',
                'company_name' => $order->company_name,
                'company_nit' => '9001234567',
                'company_address' => $order->company_address ?? 'Calle 1',
                'city' => $order->city ?? 'Bogotá',
                'department' => 'Antioquia',
                'notes' => $order->notes,
                'items' => [
                    [
                        'id' => $item->id,
                        'qty' => 2,
                        'unit_label' => $item->unit_label,
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('empresa.orders.show', $order))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PendingApproval->value,
            'approval_note' => null,
        ]);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Rejected->value,
            'to_status' => OrderStatus::PendingApproval->value,
            'changed_by_user_id' => $userA->id,
            'note' => 'Cotización ajustada y reenviada para aprobación interna.',
        ]);
    }

    public function test_update_rejects_when_all_item_quantities_are_zero(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        $order = $this->makeOrderWithItem($distA, [
            'status' => OrderStatus::PendingApproval,
        ]);
        $item = $order->items()->firstOrFail();

        $response = $this->actingAs($userA)
            ->from(route('empresa.orders.edit', $order))
            ->put(route('empresa.orders.update', $order), [
                'contact_name' => $order->contact_name,
                'contact_email' => $order->contact_email,
                'phone' => $order->phone ?? '3001112222',
                'company_name' => $order->company_name,
                'company_nit' => '9001234567',
                'company_address' => $order->company_address ?? 'Calle 1',
                'city' => $order->city ?? 'Bogotá',
                'department' => 'Antioquia',
                'notes' => $order->notes,
                'items' => [
                    [
                        'id' => $item->id,
                        'qty' => 0,
                        'unit_label' => $item->unit_label,
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('empresa.orders.edit', $order))
            ->assertSessionHasErrors();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // downloadPdf
    // ──────────────────────────────────────────────────────────────────────────

    public function test_download_pdf_streams_file_when_exists(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        Queue::fake();

        [$distA, $userA] = $this->makeDistributorWithUser();
        $order = Order::factory()->forDistributor($distA)->create();
        $fakePath = 'orders/'.$order->oc_number.'.pdf';

        Storage::disk('private')->put($fakePath, '%PDF-1.4 fake');

        $generator = $this->createMock(OrderPdfGenerator::class);
        $generator->method('generate')->willReturn($fakePath);
        $this->app->instance(OrderPdfGenerator::class, $generator);

        $this->actingAs($userA)
            ->get(route('empresa.orders.pdf', $order))
            ->assertOk()
            ->assertDownload($order->oc_number.'.pdf');

        Queue::assertNothingPushed();
    }

    public function test_download_pdf_dispatches_job_and_returns_error_message_when_not_on_disk(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        Queue::fake();

        [$distA, $userA] = $this->makeDistributorWithUser();
        $order = Order::factory()->forDistributor($distA)->create();
        $fakePath = 'orders/'.$order->oc_number.'.pdf';

        // El generador devuelve ruta pero no existe en disco
        $generator = $this->createMock(OrderPdfGenerator::class);
        $generator->method('generate')->willReturn($fakePath);
        $this->app->instance(OrderPdfGenerator::class, $generator);

        $this->actingAs($userA)
            ->get(route('empresa.orders.pdf', $order))
            ->assertRedirect()
            ->assertSessionHasErrors();

        Queue::assertPushed(GenerateOrderPdfJob::class, function ($job) use ($order) {
            return $job->orderId === $order->id;
        });
    }

    public function test_download_pdf_forbidden_for_other_distributor(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        [$distA] = $this->makeDistributorWithUser();
        [, $userB] = $this->makeDistributorWithUser();
        $order = Order::factory()->forDistributor($distA)->create();

        $this->actingAs($userB)
            ->get(route('empresa.orders.pdf', $order))
            ->assertForbidden();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // reorder
    // ──────────────────────────────────────────────────────────────────────────

    public function test_reorder_warns_when_order_has_no_items(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        // Orden sin ítems
        $order = Order::factory()->forDistributor($distA)->create();

        $this->actingAs($userA)
            ->post(route('empresa.orders.reorder', $order))
            ->assertRedirect()
            ->assertSessionHas('warning');
    }

    public function test_reorder_adds_active_products_to_cart(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        $product = Product::factory()->create([
            'price' => 8000,
            'stock' => 10,
            'is_active' => true,
        ]);
        $order = Order::factory()->forDistributor($distA)->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 3,
            'unit_label' => 'unidades',
            'price_each' => 8000,
            'subtotal' => 24000,
        ]);

        $this->actingAs($userA)
            ->post(route('empresa.orders.reorder', $order))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('status');

        $cartItems = $this->actingAs($userA)->get(route('cart.index'))->viewData('items');
        $this->assertCount(1, $cartItems);
        $this->assertEquals($product->id, $cartItems->first()['product']->id);
    }

    public function test_reorder_skips_inactive_products_and_reports_them(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        $activeProduct = Product::factory()->create(['price' => 5000, 'is_active' => true]);
        $inactiveProduct = Product::factory()->inactive()->create();
        $order = Order::factory()->forDistributor($distA)->create();

        foreach ([$activeProduct, $inactiveProduct] as $p) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $p->id,
                'product_name_snapshot' => $p->name,
                'sku_snapshot' => $p->sku,
                'qty' => 1,
                'unit_label' => 'unidades',
                'price_each' => $p->price,
                'subtotal' => $p->price,
            ]);
        }

        $response = $this->actingAs($userA)
            ->post(route('empresa.orders.reorder', $order));

        $response->assertRedirect(route('cart.index'))
            ->assertSessionHas('status')
            ->assertSessionHas('warning');

        $cartItems = $this->actingAs($userA)->get(route('cart.index'))->viewData('items');
        $this->assertCount(1, $cartItems);
        $this->assertEquals($activeProduct->id, $cartItems->first()['product']->id);
    }

    public function test_reorder_redirects_to_catalog_when_all_products_inactive(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        $inactiveProduct = Product::factory()->inactive()->create();
        $order = Order::factory()->forDistributor($distA)->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $inactiveProduct->id,
            'product_name_snapshot' => $inactiveProduct->name,
            'sku_snapshot' => $inactiveProduct->sku,
            'qty' => 2,
            'unit_label' => 'unidades',
            'price_each' => $inactiveProduct->price,
            'subtotal' => $inactiveProduct->price * 2,
        ]);

        $this->actingAs($userA)
            ->post(route('empresa.orders.reorder', $order))
            ->assertRedirect(route('catalog.index'))
            ->assertSessionHas('warning');
    }

    public function test_reorder_skips_inactive_variant_and_reports_it(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        $product = Product::factory()->create(['price' => 6000, 'is_active' => true]);
        $inactiveVariant = ProductVariant::factory()->forProduct($product)->inactive()->create();
        $order = Order::factory()->forDistributor($distA)->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $inactiveVariant->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 1,
            'unit_label' => 'unidades',
            'price_each' => $inactiveVariant->price,
            'subtotal' => $inactiveVariant->price,
        ]);

        $response = $this->actingAs($userA)
            ->post(route('empresa.orders.reorder', $order));

        $response->assertRedirect(route('catalog.index'))
            ->assertSessionHas('warning');
    }

    public function test_reorder_skips_products_that_require_variant_selection(): void
    {
        // El producto tiene variante activa pero el item original no tenía variant_id.
        // El reorder detecta hasConfigurableVariants() y lo omite.
        [$distA, $userA] = $this->makeDistributorWithUser();
        $product = Product::factory()->create(['price' => 4000, 'is_active' => true]);
        ProductVariant::factory()->forProduct($product)->create(['is_active' => true]);

        $order = Order::factory()->forDistributor($distA)->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 1,
            'unit_label' => 'unidades',
            'price_each' => 4000,
            'subtotal' => 4000,
        ]);

        $this->actingAs($userA)
            ->post(route('empresa.orders.reorder', $order))
            ->assertRedirect(route('catalog.index'))
            ->assertSessionHas('warning');
    }

    public function test_reorder_with_all_added_redirects_to_cart_without_warning(): void
    {
        [$distA, $userA] = $this->makeDistributorWithUser();
        $product = Product::factory()->create(['price' => 3000, 'is_active' => true]);
        $order = Order::factory()->forDistributor($distA)->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 1,
            'unit_label' => 'unidades',
            'price_each' => 3000,
            'subtotal' => 3000,
        ]);

        $this->actingAs($userA)
            ->post(route('empresa.orders.reorder', $order))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('status')
            ->assertSessionMissing('warning');
    }
}
