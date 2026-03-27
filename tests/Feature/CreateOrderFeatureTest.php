<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use App\Modules\Orders\Mail\OrderCreatedCustomerQuotationMail;
use App\Modules\Orders\Mail\OrderCreatedNotificationMail;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CreateOrderFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributor_can_create_order_from_cart(): void
    {
        Mail::fake();
        config(['mail.order_notification_to' => 'asesora@test.com']);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $distributor = Distributor::create(['name' => 'Distribuidor A', 'status' => 'active']);
        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'name' => 'Insumos',
            'slug' => 'insumos',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'name' => 'Guante Test',
            'sku' => 'TEST-001',
            'description' => 'Producto test',
            'category_id' => $category->id,
            'price' => 10000,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 2])
            ->assertRedirect();

        $response = $this->actingAs($user)->post(route('orders.store'), [
            'contact_name' => 'Comprador Test',
            'contact_email' => 'comprador@test.com',
            'company_name' => 'Empresa Test',
            'company_nit' => '9001234567',
            'company_address' => 'Calle 123 #45-67',
            'city' => 'Bogotá',
            'notes' => 'nota',
        ]);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('orders.submitted', ['order' => $order]));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('orders', [
            'contact_name' => 'Comprador Test',
            'company_name' => 'Empresa Test',
            'company_nit' => '9001234567',
            'company_address' => 'Calle 123 #45-67',
            'contact_email' => 'comprador@test.com',
            'city' => 'Bogotá',
        ]);
        Mail::assertSent(OrderCreatedNotificationMail::class);
        Mail::assertSent(OrderCreatedCustomerQuotationMail::class);
    }

    public function test_checkout_rejects_company_nit_with_non_numeric_characters(): void
    {
        Mail::fake();
        config(['mail.order_notification_to' => 'asesora@test.com']);
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $distributor = Distributor::create(['name' => 'Distribuidor A', 'status' => 'active']);
        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'name' => 'Insumos',
            'slug' => 'insumos',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'name' => 'Guante Test',
            'sku' => 'TEST-001',
            'description' => 'Producto test',
            'category_id' => $category->id,
            'price' => 10000,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 2])
            ->assertRedirect();

        $response = $this->from(route('checkout.show'))
            ->actingAs($user)
            ->post(route('orders.store'), [
                'contact_name' => 'Comprador Test',
                'contact_email' => 'comprador@test.com',
                'company_name' => 'Empresa Test',
                'company_nit' => '900123456-7',
                'company_address' => 'Calle 123 #45-67',
                'city' => 'Bogotá',
                'notes' => 'nota',
            ]);

        $response->assertRedirect(route('checkout.show'));
        $response->assertSessionHasErrors('company_nit');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        Mail::assertNothingSent();
    }
}
