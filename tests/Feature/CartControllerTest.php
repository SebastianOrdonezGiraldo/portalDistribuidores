<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_anyone_can_view_empty_cart(): void
    {
        $this->get(route('cart.index'))
            ->assertOk()
            ->assertViewIs('cart.index')
            ->assertViewHas('items');
    }

    public function test_cart_shows_added_product(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 2])
            ->assertRedirect();

        $response = $this->get(route('cart.index'));
        $response->assertOk();

        $items = $response->viewData('items');
        $this->assertCount(1, $items);
        $this->assertEquals(2, $items->first()['qty']);
    }

    public function test_cart_product_thumb_uses_primary_photo_when_available(): void
    {
        $product = Product::factory()->create(['price' => 10000]);
        $primaryPhoto = $product->photos()->create([
            'path' => 'products/photos/primary-photo.jpg',
            'is_primary' => true,
            'sort_order' => 2,
        ]);
        $product->photos()->create([
            'path' => 'products/photos/secondary-photo.jpg',
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertRedirect();

        $response = $this->get(route('cart.index'));
        $response->assertOk();
        $response->assertSee($primaryPhoto->path);

        $items = $response->viewData('items');
        $cartProduct = $items->first()['product'];

        $this->assertTrue($cartProduct->relationLoaded('primaryPhoto'));
        $this->assertTrue($cartProduct->relationLoaded('photos'));
        $this->assertEquals($primaryPhoto->id, $cartProduct->primaryPhoto?->id);
    }

    public function test_cart_product_thumb_falls_back_to_first_photo_when_no_primary_exists(): void
    {
        $product = Product::factory()->create(['price' => 10000]);
        $fallbackPhoto = $product->photos()->create([
            'path' => 'products/photos/fallback-photo.jpg',
            'is_primary' => false,
            'sort_order' => 1,
        ]);
        $product->photos()->create([
            'path' => 'products/photos/later-photo.jpg',
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertRedirect();

        $response = $this->get(route('cart.index'));
        $response->assertOk();
        $response->assertSee($fallbackPhoto->path);

        $items = $response->viewData('items');
        $cartProduct = $items->first()['product'];

        $this->assertTrue($cartProduct->relationLoaded('photos'));
        $this->assertNull($cartProduct->primaryPhoto);
        $this->assertEquals($fallbackPhoto->id, $cartProduct->photos->first()?->id);
    }

    public function test_cart_product_thumb_shows_placeholder_when_product_has_no_photos(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertRedirect();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Sin foto');
    }

    public function test_adding_active_product_redirects_with_status(): void
    {
        $product = Product::factory()->create();

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertRedirect()
            ->assertSessionHas('status', 'Producto agregado al carrito.');
    }

    public function test_adding_active_product_via_json_returns_ok_response(): void
    {
        $product = Product::factory()->create();

        $this->postJson(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_adding_product_above_stock_is_rejected(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->postJson(route('cart.store'), ['product_id' => $product->id, 'qty' => 6])
            ->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'message' => 'Solo hay 5 unidades disponibles para este producto.',
            ]);
    }

    public function test_adding_same_product_multiple_times_cannot_exceed_stock(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 4])
            ->assertRedirect();

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 2])
            ->assertRedirect()
            ->assertSessionHasErrors();
    }

    public function test_adding_inactive_product_returns_error_redirect(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertRedirect()
            ->assertSessionHasErrors();
    }

    public function test_adding_inactive_product_via_json_returns_422(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->postJson(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_adding_product_with_variants_without_variant_id_returns_error_redirect(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->forProduct($product)->create(['is_active' => true]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertRedirect()
            ->assertSessionHasErrors();
    }

    public function test_adding_product_with_variants_without_variant_id_via_json_returns_422(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->forProduct($product)->create(['is_active' => true]);

        $this->postJson(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['variant_id'])
            ->assertJson([
                'message' => 'Debes seleccionar una variante para este producto.',
            ]);
    }

    public function test_adding_product_with_invalid_variant_id_returns_error(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->forProduct($product)->create(['is_active' => true]);

        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'qty' => 1,
            'variant_id' => 99999,
        ])
            ->assertRedirect()
            ->assertSessionHasErrors();
    }

    public function test_adding_product_with_valid_variant_succeeds(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create(['is_active' => true]);

        $this->postJson(route('cart.store'), [
            'product_id' => $product->id,
            'qty' => 1,
            'variant_id' => $variant->id,
        ])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_adding_product_with_variant_above_variant_stock_is_rejected(): void
    {
        $product = Product::factory()->create(['stock' => 999]);
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'is_active' => true,
            'stock' => 2,
        ]);

        $this->postJson(route('cart.store'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'qty' => 3,
        ])
            ->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'message' => 'Solo hay 2 unidades disponibles para este producto.',
            ]);
    }

    public function test_adding_inactive_variant_is_rejected(): void
    {
        $product = Product::factory()->create();
        $inactiveVariant = ProductVariant::factory()->forProduct($product)->inactive()->create();

        $this->postJson(route('cart.store'), [
            'product_id' => $product->id,
            'qty' => 1,
            'variant_id' => $inactiveVariant->id,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['variant_id']);
    }

    public function test_adding_nonexistent_product_returns_validation_error(): void
    {
        $this->post(route('cart.store'), ['product_id' => 99999, 'qty' => 1])
            ->assertRedirect()
            ->assertSessionHasErrors(['product_id']);
    }

    public function test_update_cart_quantities_redirects_with_status(): void
    {
        $product = Product::factory()->create(['price' => 5000]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1]);

        $lineKey = $product->id.'-0';

        $this->patch(route('cart.update'), ['quantities' => [$lineKey => 5]])
            ->assertRedirect()
            ->assertSessionHas('status', 'Carrito actualizado.');
    }

    public function test_update_cart_quantities_above_stock_returns_error(): void
    {
        $product = Product::factory()->create([
            'price' => 5000,
            'stock' => 3,
        ]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1]);

        $lineKey = $product->id.'-0';

        $this->patch(route('cart.update'), ['quantities' => [$lineKey => 4]])
            ->assertRedirect()
            ->assertSessionHasErrors();
    }

    public function test_update_cart_quantities_accepts_put_method(): void
    {
        $product = Product::factory()->create(['price' => 5000]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1]);

        $lineKey = $product->id.'-0';

        $this->put(route('cart.update'), ['quantities' => [$lineKey => 3]])
            ->assertRedirect()
            ->assertSessionHas('status', 'Carrito actualizado.');
    }

    public function test_update_with_zero_quantity_removes_line(): void
    {
        $product = Product::factory()->create(['price' => 5000]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1]);

        $lineKey = $product->id.'-0';
        $this->patch(route('cart.update'), ['quantities' => [$lineKey => 0]]);

        $items = $this->get(route('cart.index'))->viewData('items');
        $this->assertCount(0, $items);
    }

    public function test_destroy_removes_line_from_cart(): void
    {
        $product = Product::factory()->create(['price' => 5000]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 2]);

        $lineKey = $product->id.'-0';

        $this->delete(route('cart.destroy', $lineKey))
            ->assertRedirect()
            ->assertSessionHas('status', 'Línea eliminada del carrito.');

        $items = $this->get(route('cart.index'))->viewData('items');
        $this->assertCount(0, $items);
    }

    public function test_destroy_nonexistent_line_still_redirects_gracefully(): void
    {
        $this->delete(route('cart.destroy', 'key-inexistente'))
            ->assertRedirect();
    }
}
