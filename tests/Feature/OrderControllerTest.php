<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderPdfGenerator;
use App\Modules\Shared\Enums\CompanyRole;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private const PDF_DIR = 'orders/';

    /** @var array<string, mixed> */
    private array $validPayload = [
        'contact_name' => 'Juan Comprador',
        'contact_email' => 'juan@empresa.com',
        'phone' => '3001234567',
        'company_name' => 'Empresa Demo',
        'company_nit' => '9000000011',
        'company_address' => 'Cra 10 #20-30',
        'city' => 'Medellín',
        'notes' => null,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config(['mail.order_notification_to' => 'admin@test.com']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function distributorWithUser(?CompanyRole $companyRole = null): array
    {
        $distributor = Distributor::factory()->create();
        $user = User::factory()->create([
            'distributor_id' => $distributor->id,
            'company_role' => $companyRole,
        ]);

        return [$distributor, $user];
    }

    private function addProductToCart(User $user, Product $product, int $qty = 1): void
    {
        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'qty' => $qty]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // store – bloqueos y permisos
    // ──────────────────────────────────────────────────────────────────────────

    public function test_solo_lectura_cannot_create_order(): void
    {
        [, $user] = $this->distributorWithUser(CompanyRole::SoloLectura);
        $product = Product::factory()->create(['price' => 5000]);
        $this->addProductToCart($user, $product);

        $this->actingAs($user)
            ->post(route('orders.store'), $this->validPayload)
            ->assertRedirect(route('empresa.dashboard'))
            ->assertSessionHasErrors();
    }

    public function test_store_redirects_to_cart_when_no_items(): void
    {
        [, $user] = $this->distributorWithUser();

        $this->actingAs($user)
            ->post(route('orders.store'), $this->validPayload)
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // store – creación exitosa
    // ──────────────────────────────────────────────────────────────────────────

    public function test_distributor_can_create_order_and_cart_is_cleared(): void
    {
        Mail::fake();

        [, $user] = $this->distributorWithUser();
        $product = Product::factory()->create(['price' => 10000]);
        $this->addProductToCart($user, $product, 2);

        $this->actingAs($user)
            ->post(route('orders.store'), $this->validPayload)
            ->assertRedirect();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('orders', [
            'contact_email' => 'juan@empresa.com',
            'phone' => '3001234567',
        ]);

        // El carrito debe estar vacío tras crear la orden
        $cartItems = $this->actingAs($user)
            ->get(route('cart.index'))
            ->viewData('items');

        $this->assertCount(0, $cartItems);
    }

    public function test_order_is_created_with_submitted_status_when_no_approval_required(): void
    {
        Mail::fake();

        [, $user] = $this->distributorWithUser(CompanyRole::AdminEmpresa);
        $product = Product::factory()->create(['price' => 5000]);
        $this->addProductToCart($user, $product);

        $this->actingAs($user)->post(route('orders.store'), $this->validPayload);

        $order = Order::query()->firstOrFail();
        $this->assertEquals(OrderStatus::Submitted, $order->status);
    }

    public function test_order_gets_pending_approval_status_for_usuario_comercial(): void
    {
        [, $user] = $this->distributorWithUser(CompanyRole::UsuarioComercial);
        $product = Product::factory()->create(['price' => 5000]);
        $this->addProductToCart($user, $product);

        $this->actingAs($user)->post(route('orders.store'), $this->validPayload);

        $order = Order::query()->firstOrFail();
        $this->assertEquals(OrderStatus::PendingApproval, $order->status);
    }

    public function test_usuario_comercial_redirects_to_empresa_orders_show(): void
    {
        [, $user] = $this->distributorWithUser(CompanyRole::UsuarioComercial);
        $product = Product::factory()->create(['price' => 5000]);
        $this->addProductToCart($user, $product);

        $response = $this->actingAs($user)->post(route('orders.store'), $this->validPayload);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('empresa.orders.show', $order));
    }

    public function test_distributor_without_approval_requirement_redirects_to_submitted(): void
    {
        Mail::fake();

        [, $user] = $this->distributorWithUser();
        $product = Product::factory()->create(['price' => 5000]);
        $this->addProductToCart($user, $product);

        $response = $this->actingAs($user)->post(route('orders.store'), $this->validPayload);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('orders.submitted', $order));
    }

    public function test_oc_number_has_correct_prefix(): void
    {
        Mail::fake();

        [, $user] = $this->distributorWithUser();
        $product = Product::factory()->create(['price' => 5000]);
        $this->addProductToCart($user, $product);

        $this->actingAs($user)->post(route('orders.store'), $this->validPayload);

        $order = Order::query()->firstOrFail();
        $this->assertStringStartsWith(Order::OC_PREFIX, $order->oc_number);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // store – validación de formulario
    // ──────────────────────────────────────────────────────────────────────────

    public function test_store_validates_required_fields(): void
    {
        [, $user] = $this->distributorWithUser();
        $product = Product::factory()->create(['price' => 5000]);
        $this->addProductToCart($user, $product);

        $this->actingAs($user)
            ->post(route('orders.store'), [])
            ->assertSessionHasErrors([
                'contact_name',
                'contact_email',
                'phone',
                'company_name',
                'company_nit',
                'company_address',
                'city',
            ]);
    }

    public function test_checkout_prefills_master_company_data_from_distributor(): void
    {
        $distributor = Distributor::factory()->create([
            'name' => 'Empresa Maestra',
            'nit' => '9001234567',
            'address' => 'Calle 100 #10-20',
            'city' => 'Bogotá',
            'phone' => '3005551111',
            'contact_name' => 'Laura Compras',
            'contact_email' => 'compras@empresa.test',
        ]);
        $user = User::factory()->create([
            'distributor_id' => $distributor->id,
        ]);
        $product = Product::factory()->create(['price' => 5000]);
        $this->addProductToCart($user, $product);

        $this->actingAs($user)
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('value="Empresa Maestra"', false)
            ->assertSee('value="9001234567"', false)
            ->assertSee('value="Laura Compras"', false)
            ->assertSee('value="compras@empresa.test"', false)
            ->assertSee('value="3005551111"', false)
            ->assertSee('value="Calle 100 #10-20"', false)
            ->assertSee('value="Bogotá"', false);
    }

    public function test_checkout_falls_back_to_user_contact_data_when_distributor_contact_fields_are_missing(): void
    {
        $distributor = Distributor::factory()->create([
            'contact_name' => null,
            'contact_email' => null,
        ]);
        $user = User::factory()->create([
            'name' => 'Usuario Checkout',
            'email' => 'usuario.checkout@test.com',
            'distributor_id' => $distributor->id,
        ]);
        $product = Product::factory()->create(['price' => 5000]);
        $this->addProductToCart($user, $product);

        $this->actingAs($user)
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('value="Usuario Checkout"', false)
            ->assertSee('value="usuario.checkout@test.com"', false);
    }

    public function test_store_keeps_distributor_master_data_unchanged_when_checkout_data_is_edited(): void
    {
        Mail::fake();

        $distributor = Distributor::factory()->create([
            'name' => 'Empresa Maestra',
            'nit' => '9001234567',
            'address' => 'Calle 100 #10-20',
            'city' => 'Bogotá',
            'phone' => '3005551111',
            'contact_name' => 'Laura Compras',
            'contact_email' => 'compras@empresa.test',
        ]);
        $user = User::factory()->create([
            'distributor_id' => $distributor->id,
        ]);
        $product = Product::factory()->create(['price' => 5000]);
        $this->addProductToCart($user, $product);

        $payload = [
            'contact_name' => 'Nombre Pedido',
            'contact_email' => 'pedido@empresa.test',
            'phone' => '3009990000',
            'company_name' => 'Empresa Pedido',
            'company_nit' => '8000000001',
            'company_address' => 'Cra 20 #30-40',
            'city' => 'Medellín',
            'notes' => 'Cambios solo para esta orden',
        ];

        $this->actingAs($user)
            ->post(route('orders.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'contact_name' => 'Nombre Pedido',
            'contact_email' => 'pedido@empresa.test',
            'phone' => '3009990000',
            'company_name' => 'Empresa Pedido',
            'company_nit' => '8000000001',
            'company_address' => 'Cra 20 #30-40',
            'city' => 'Medellín',
        ]);

        $this->assertDatabaseHas('distributors', [
            'id' => $distributor->id,
            'name' => 'Empresa Maestra',
            'nit' => '9001234567',
            'address' => 'Calle 100 #10-20',
            'city' => 'Bogotá',
            'phone' => '3005551111',
            'contact_name' => 'Laura Compras',
            'contact_email' => 'compras@empresa.test',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // submitted / show – acceso a orden
    // ──────────────────────────────────────────────────────────────────────────

    public function test_owner_distributor_can_access_submitted_page(): void
    {
        [$distributor, $user] = $this->distributorWithUser();
        $order = Order::factory()->forDistributor($distributor)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('orders.submitted', $order))
            ->assertOk()
            ->assertViewIs('orders.submitted');
    }

    public function test_other_distributor_cannot_access_submitted_page(): void
    {
        [$distA] = $this->distributorWithUser();
        [, $userB] = $this->distributorWithUser();

        $order = Order::factory()->forDistributor($distA)->create();

        $this->actingAs($userB)
            ->get(route('orders.submitted', $order))
            ->assertForbidden();
    }

    public function test_admin_can_access_any_orders_show(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create();

        $this->actingAs($admin)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertViewIs('orders.show');
    }

    public function test_guest_can_access_order_via_session(): void
    {
        $order = Order::factory()->create();

        // Simula que la sesión recuerda el pedido del invitado
        $this->withSession(['orders.guest_access' => [$order->id]])
            ->get(route('orders.submitted', $order))
            ->assertOk();
    }

    public function test_guest_without_session_cannot_access_order(): void
    {
        $order = Order::factory()->create();

        $this->get(route('orders.submitted', $order))
            ->assertForbidden();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // downloadPdf
    // ──────────────────────────────────────────────────────────────────────────

    public function test_download_pdf_returns_error_when_file_not_on_disk(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        [$distributor, $user] = $this->distributorWithUser();
        $order = Order::factory()->forDistributor($distributor)->create(['user_id' => $user->id]);

        // Forzamos que el generador devuelva una ruta pero no ponga el fichero en disco
        $fakePath = self::PDF_DIR.$order->oc_number.'.pdf';
        $generator = $this->createMock(OrderPdfGenerator::class);
        $generator->method('generate')->willReturn($fakePath);
        $this->app->instance(OrderPdfGenerator::class, $generator);

        $this->actingAs($user)
            ->get(route('orders.pdf', $order))
            ->assertRedirect()
            ->assertSessionHasErrors();
    }

    public function test_download_pdf_streams_file_when_exists(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        [$distributor, $user] = $this->distributorWithUser();
        $order = Order::factory()->forDistributor($distributor)->create(['user_id' => $user->id]);
        $fakePath = self::PDF_DIR.$order->oc_number.'.pdf';

        Storage::disk('private')->put($fakePath, '%PDF-1.4 fake content');

        $generator = $this->createMock(OrderPdfGenerator::class);
        $generator->method('generate')->willReturn($fakePath);
        $this->app->instance(OrderPdfGenerator::class, $generator);

        $this->actingAs($user)
            ->get(route('orders.pdf', $order))
            ->assertOk()
            ->assertDownload($order->oc_number.'.pdf');
    }

    public function test_download_pdf_updates_pdf_path_when_changed(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        [$distributor, $user] = $this->distributorWithUser();
        $order = Order::factory()->forDistributor($distributor)->create([
            'user_id' => $user->id,
            'pdf_path' => 'orders/old-path.pdf',
        ]);
        $newPath = self::PDF_DIR.$order->oc_number.'.pdf';

        Storage::disk('private')->put($newPath, '%PDF content');

        $generator = $this->createMock(OrderPdfGenerator::class);
        $generator->method('generate')->willReturn($newPath);
        $this->app->instance(OrderPdfGenerator::class, $generator);

        $this->actingAs($user)->get(route('orders.pdf', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'pdf_path' => $newPath,
        ]);
    }

    public function test_unauthorized_user_cannot_download_pdf(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        [$distA] = $this->distributorWithUser();
        [, $userB] = $this->distributorWithUser();
        $order = Order::factory()->forDistributor($distA)->create();

        $this->actingAs($userB)
            ->get(route('orders.pdf', $order))
            ->assertForbidden();
    }
}
