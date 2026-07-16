<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Categories\Models\Category;
use App\Modules\Orders\Mail\OrderCreatedCustomerQuotationMail;
use App\Modules\Orders\Mail\OrderCreatedNotificationMail;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProductVariantsFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_global_attribute_and_variants(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria Variantes',
            'slug' => 'categoria-variantes',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Balon Profesional',
            'brand' => 'Marca Test',
            'sku' => 'VAR-001',
            'description' => 'Producto con variantes',
            'category_id' => $category->id,
            'has_variants' => 1,
            'new_variant_attribute_name' => 'Color',
            'variants' => [
                ['value' => 'Rojo', 'price' => 18000, 'stock' => 5],
                ['value' => 'Azul', 'price' => 21000, 'stock' => 8],
            ],
            'is_active' => 1,
        ]);

        $product = Product::query()->where('sku', 'VAR-001')->firstOrFail();
        $attribute = ProductAttribute::query()->where('slug', 'color')->firstOrFail();

        $response->assertRedirect('/admin/products/'.$product->id.'/edit');
        $this->assertSame($attribute->id, (int) $product->variant_attribute_id);
        $this->assertEquals(18000.0, (float) $product->price);

        $this->assertDatabaseHas('product_attribute_values', [
            'product_attribute_id' => $attribute->id,
            'slug' => 'rojo',
        ]);

        $this->assertDatabaseHas('product_attribute_values', [
            'product_attribute_id' => $attribute->id,
            'slug' => 'azul',
        ]);

        $this->assertDatabaseCount('product_variants', 2);
    }

    public function test_cart_requires_variant_selection_and_order_uses_variant_price(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Mail::fake();
        config(['mail.order_notification_to' => 'asesora@test.com']);

        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria Compra Variantes',
            'slug' => 'categoria-compra-variantes',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $attribute = ProductAttribute::create([
            'name' => 'Color',
            'slug' => 'color',
        ]);

        $value = ProductAttributeValue::create([
            'product_attribute_id' => $attribute->id,
            'value' => 'Rojo',
            'slug' => 'rojo',
        ]);

        $product = Product::create([
            'name' => 'Balon con color',
            'sku' => 'VAR-ORDER-001',
            'description' => 'Producto con variante obligatoria',
            'category_id' => $category->id,
            'variant_attribute_id' => $attribute->id,
            'price' => 15000,
            'is_vat_excluded' => true,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'product_attribute_value_id' => $value->id,
            'price' => 15000,
            'stock' => 3,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->postJson(route('cart.store'), [
            'product_id' => $product->id,
            'qty' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('variant_id');

        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'qty' => 2,
        ])->assertRedirect();

        $response = $this->post(route('orders.store'), [
            'contact_name' => 'Cliente Test',
            'contact_email' => 'cliente@test.com',
            'phone' => '3008889999',
            'company_name' => 'Empresa Variant',
            'company_nit' => '900999111',
            'company_address' => 'Calle 10 #20-30',
            'city' => 'Bogota',
            'department' => 'Cundinamarca',
            'notes' => 'Pedido con variante',
        ]);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('orders.submitted', ['order' => $order]));

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'variant_attribute_snapshot' => 'Color',
            'variant_value_snapshot' => 'Rojo',
            'price_each' => 15000.00,
            'qty' => 2.00,
            'subtotal' => 30000.00,
            'is_vat_excluded_snapshot' => true,
        ]);

        $this->assertSame(0.0, (float) $order->items()->firstOrFail()->vat_rate_snapshot);
        $this->assertEquals(1.0, (float) $variant->fresh()->stock);
        Mail::assertSent(OrderCreatedNotificationMail::class);
        Mail::assertSent(OrderCreatedCustomerQuotationMail::class);
    }
}
