<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Mail\PaymentReceiptAdminMail;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\PaymentUploadToken;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Orders\Services\Payment\OrderPaymentService;
use App\Modules\Orders\Services\Payment\PaymentReceiptUploadService;
use App\Modules\Orders\Services\Payment\PaymentUploadTokenService;
use App\Modules\Orders\Support\PaymentReceiptUploadLimits;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Enums\PaymentMethod;
use App\Modules\Shared\Enums\PaymentStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $validPayload = [
        'contact_name' => 'Juan Comprador',
        'contact_email' => 'juan@empresa.com',
        'phone' => '3001234567',
        'company_name' => 'Empresa Demo',
        'company_nit' => '9000000011',
        'company_address' => 'Cra 10 #20-30',
        'city' => 'Medellín',
        'department' => 'Antioquia',
        'notes' => null,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Storage::fake('private');
        config([
            'filesystems.order_pdfs_disk' => 'private',
            'mail.order_notification_to' => 'admin@test.com',
            'commerce.payment.manual_reservation_ttl_minutes' => 45,
            'commerce.payment.upload_token_ttl_minutes' => 20,
        ]);
    }

    private function distributorWithUser(): array
    {
        $distributor = Distributor::factory()->create();
        $user = User::factory()->create([
            'distributor_id' => $distributor->id,
        ]);

        return [$distributor, $user];
    }

    private function addProductToCart(User $user, Product $product, int $qty = 1): void
    {
        $product->forceFill([
            'stock' => max($qty, 5),
            'is_active' => true,
        ])->save();

        $this->actingAs($user)->post(route('cart.store'), [
            'product_id' => $product->id,
            'qty' => $qty,
        ]);
    }

    public function test_quote_intent_keeps_payment_not_applicable(): void
    {
        Mail::fake();
        [, $user] = $this->distributorWithUser();
        $product = Product::factory()->create(['price' => 10000, 'stock' => 10]);
        $this->addProductToCart($user, $product);

        $this->actingAs($user)
            ->post(route('orders.store'), array_merge($this->validPayload, [
                'checkout_intent' => 'quote',
            ]))
            ->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame(PaymentStatus::NotApplicable, $order->payment_status);
        $this->assertNull($order->payment_method);
        $this->assertNull($order->payment_reservation_expires_at);
    }

    public function test_pay_intent_sets_pending_upload_and_method(): void
    {
        Mail::fake();
        [, $user] = $this->distributorWithUser();
        $product = Product::factory()->create(['price' => 10000, 'stock' => 10]);
        $this->addProductToCart($user, $product);

        $this->actingAs($user)
            ->post(route('orders.store'), array_merge($this->validPayload, [
                'checkout_intent' => 'pay',
                'payment_method' => PaymentMethod::Bancolombia->value,
            ]))
            ->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame(PaymentStatus::PendingUpload, $order->payment_status);
        $this->assertSame(PaymentMethod::Bancolombia, $order->payment_method);
        $this->assertNotNull($order->payment_reservation_expires_at);
        $this->assertTrue(PaymentUploadToken::query()->where('order_id', $order->id)->exists());
    }

    public function test_pay_intent_requires_payment_method(): void
    {
        [, $user] = $this->distributorWithUser();
        $product = Product::factory()->create(['price' => 10000, 'stock' => 10]);
        $this->addProductToCart($user, $product);

        $this->actingAs($user)
            ->post(route('orders.store'), array_merge($this->validPayload, [
                'checkout_intent' => 'pay',
            ]))
            ->assertSessionHasErrors('payment_method');
    }

    public function test_magic_link_upload_moves_to_confirming(): void
    {
        Mail::fake();
        $order = Order::factory()->create([
            'status' => OrderStatus::Submitted,
            'payment_status' => PaymentStatus::PendingUpload,
            'payment_method' => PaymentMethod::Nequi,
            'payment_reservation_expires_at' => now()->addMinutes(30),
        ]);

        $tokenService = app(PaymentUploadTokenService::class);
        $plain = $tokenService->issue($order);

        $file = UploadedFile::fake()->image('comprobante.jpg', 400, 400);

        $this->post(route('orders.payment-receipt.store', $order), [
            'token' => $plain,
            'receipt' => $file,
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame(PaymentStatus::Confirming, $order->payment_status);
        $this->assertNotNull($order->payment_receipt_path);
        $this->assertNotNull($order->payment_receipt_uploaded_at);
        $this->assertTrue(
            PaymentUploadToken::query()->where('order_id', $order->id)->whereNotNull('consumed_at')->exists()
        );
    }

    public function test_magic_link_accepts_receipt_at_the_10mb_limit(): void
    {
        Mail::fake();
        $order = Order::factory()->create([
            'status' => OrderStatus::Submitted,
            'payment_status' => PaymentStatus::PendingUpload,
            'payment_method' => PaymentMethod::Nequi,
            'payment_reservation_expires_at' => now()->addMinutes(30),
        ]);

        $plain = app(PaymentUploadTokenService::class)->issue($order);
        $pdfSize = PaymentReceiptUploadLimits::maxSizeKb() * 1024;
        $file = UploadedFile::fake()->createWithContent(
            'comprobante-10mb.pdf',
            '%PDF-'.str_repeat('0', $pdfSize - 5),
        );

        $this->post(route('orders.payment-receipt.store', $order), [
            'token' => $plain,
            'receipt' => $file,
        ])->assertRedirect();

        $this->assertSame(PaymentStatus::Confirming, $order->fresh()->payment_status);
    }

    public function test_magic_link_rejects_receipt_above_the_configured_limit(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Submitted,
            'payment_status' => PaymentStatus::PendingUpload,
            'payment_method' => PaymentMethod::Nequi,
            'payment_reservation_expires_at' => now()->addMinutes(30),
        ]);

        $plain = app(PaymentUploadTokenService::class)->issue($order);
        $file = UploadedFile::fake()->create(
            'comprobante-grande.pdf',
            PaymentReceiptUploadLimits::maxSizeKb() + 1,
            'application/pdf',
        );

        $this->post(route('orders.payment-receipt.store', $order), [
            'token' => $plain,
            'receipt' => $file,
        ])->assertSessionHasErrors([
            'receipt' => 'El comprobante no puede superar '.PaymentReceiptUploadLimits::maxSizeLabel().'.',
        ]);

        $this->assertSame(PaymentStatus::PendingUpload, $order->fresh()->payment_status);
        $this->assertDatabaseHas('payment_upload_tokens', [
            'order_id' => $order->id,
            'consumed_at' => null,
        ]);
    }

    public function test_magic_link_displays_the_payment_receipt_limit(): void
    {
        $order = Order::factory()->create([
            'payment_status' => PaymentStatus::PendingUpload,
            'payment_method' => PaymentMethod::Llave,
        ]);

        $plain = app(PaymentUploadTokenService::class)->issue($order);

        $this->get(route('orders.payment-receipt.show', [
            'order' => $order,
            'token' => $plain,
        ]))
            ->assertOk()
            ->assertSee('máximo '.PaymentReceiptUploadLimits::maxSizeLabel());
    }

    public function test_magic_link_unknown_token_returns_404(): void
    {
        $order = Order::factory()->create([
            'payment_status' => PaymentStatus::PendingUpload,
            'payment_method' => PaymentMethod::Llave,
        ]);

        $this->get(route('orders.payment-receipt.show', [
            'order' => $order,
            'token' => str_repeat('a', 64),
        ]))->assertNotFound();
    }

    public function test_admin_validate_payment_keeps_submitted_order_and_active_hold(): void
    {
        Queue::fake();
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'status' => OrderStatus::Submitted,
            'payment_status' => PaymentStatus::Confirming,
            'payment_method' => PaymentMethod::Qr,
            'payment_receipt_path' => 'orders/payment-receipts/1/file.jpg',
            'payment_receipt_uploaded_at' => now(),
        ]);
        $product = Product::factory()->create(['stock' => 10]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 2,
            'unit_label' => 'unidades',
            'price_each' => 5000,
            'subtotal' => 10000,
        ]);
        app(OrderInventoryService::class)->holdForOrder($order);

        $this->actingAs($admin)
            ->post(route('admin.orders.payment.validate', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(PaymentStatus::Validated, $order->payment_status);
        $this->assertSame(OrderStatus::Submitted, $order->status);
        $this->assertSame(2.0, (float) $product->fresh()->reserved_stock);
        $this->assertTrue($order->activeInventoryHolds()->exists());
    }

    public function test_cannot_dispatch_while_payment_pending(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Sold,
            'payment_status' => PaymentStatus::PendingUpload,
            'payment_method' => PaymentMethod::Bancolombia,
        ]);

        $this->expectException(DomainException::class);

        app(OrderStatusTransitionService::class)->transition(
            $order,
            OrderStatus::Dispatched,
            null,
            'Despacho',
            '2258298191',
        );
    }

    public function test_expire_pending_upload_cancels_and_is_idempotent(): void
    {
        Queue::fake();
        $product = Product::factory()->create(['stock' => 10, 'is_active' => true, 'price' => 5000]);
        $order = Order::factory()->create([
            'status' => OrderStatus::Submitted,
            'payment_status' => PaymentStatus::PendingUpload,
            'payment_method' => PaymentMethod::Bancolombia,
            'payment_reservation_expires_at' => now()->subMinute(),
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 2,
            'unit_label' => 'unidades',
            'price_each' => 5000,
            'base_unit_price' => 5000,
            'silver_unit_price' => 5250,
            'unit_savings' => 0,
            'subtotal' => 10000,
            'line_savings' => 0,
            'is_vat_excluded_snapshot' => false,
        ]);

        app(OrderInventoryService::class)->holdForOrder($order);

        $service = app(OrderPaymentService::class);
        $this->assertTrue($service->expirePendingUpload($order));

        $order->refresh();
        $product->refresh();
        $this->assertSame(PaymentStatus::Expired, $order->payment_status);
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame(10.0, (float) $product->stock);
        $this->assertSame(0.0, (float) $product->reserved_stock);

        $this->assertFalse($service->expirePendingUpload($order->fresh()));
    }

    public function test_second_upload_while_confirming_is_rejected(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Submitted,
            'payment_status' => PaymentStatus::Confirming,
            'payment_method' => PaymentMethod::Nequi,
            'payment_receipt_path' => 'orders/payment-receipts/1/existing.jpg',
            'payment_receipt_uploaded_at' => now(),
        ]);

        $this->expectException(DomainException::class);
        app(PaymentReceiptUploadService::class)->upload(
            $order,
            UploadedFile::fake()->image('otro.jpg'),
        );
    }

    public function test_gold_receipt_upload_notifies_admin_with_attachment(): void
    {
        Mail::fake();

        config(['mail.gold_payment_receipt_notification_to' => 'administrador@icmtherapy.com']);

        $distributor = Distributor::factory()->gold()->create();
        $order = Order::factory()->create([
            'distributor_id' => $distributor->id,
            'status' => OrderStatus::Submitted,
            'payment_status' => PaymentStatus::PendingUpload,
            'payment_method' => PaymentMethod::Bancolombia,
            'distributor_tier_snapshot' => DistributorTier::Gold,
            'payment_reservation_expires_at' => now()->addMinutes(30),
        ]);

        $tokenService = app(PaymentUploadTokenService::class);
        $plain = $tokenService->issue($order);

        $this->post(route('orders.payment-receipt.store', $order), [
            'token' => $plain,
            'receipt' => UploadedFile::fake()->image('comprobante-oro.jpg', 400, 400),
        ])->assertRedirect();

        Mail::assertSent(PaymentReceiptAdminMail::class, function ($mail) {
            return $mail->hasTo('administrador@icmtherapy.com')
                && $mail->tier === DistributorTier::Gold
                && count($mail->attachments()) === 1;
        });
    }

    public function test_silver_receipt_upload_notifies_comercial_with_attachment(): void
    {
        Mail::fake();

        config(['mail.silver_payment_receipt_notification_to' => 'COMERCIAL@IMPORTCORPORALMEDICAL.COM']);

        $distributor = Distributor::factory()->silver()->create();
        $order = Order::factory()->create([
            'distributor_id' => $distributor->id,
            'status' => OrderStatus::Submitted,
            'payment_status' => PaymentStatus::PendingUpload,
            'payment_method' => PaymentMethod::Nequi,
            'distributor_tier_snapshot' => DistributorTier::Silver,
            'payment_reservation_expires_at' => now()->addMinutes(30),
        ]);

        $tokenService = app(PaymentUploadTokenService::class);
        $plain = $tokenService->issue($order);

        $this->post(route('orders.payment-receipt.store', $order), [
            'token' => $plain,
            'receipt' => UploadedFile::fake()->image('comprobante-plata.jpg', 400, 400),
        ])->assertRedirect();

        Mail::assertSent(PaymentReceiptAdminMail::class, function ($mail) {
            return $mail->hasTo('COMERCIAL@IMPORTCORPORALMEDICAL.COM')
                && $mail->tier === DistributorTier::Silver
                && count($mail->attachments()) === 1;
        });
    }

    public function test_checkout_shows_quote_and_pay_buttons(): void
    {
        [, $user] = $this->distributorWithUser();
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);
        $this->addProductToCart($user, $product);

        $this->actingAs($user)
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Solo cotizar')
            ->assertSee('Pagar ahora')
            ->assertSee('Bancolombia')
            ->assertSee('Nequi')
            ->assertSee('Llave')
            ->assertSee('75690965348', false)
            ->assertSee('0092741326', false);
    }
}
