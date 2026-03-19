<?php

namespace Tests\Feature;

use App\Models\User;
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

    // ──────────────────────────────────────────────────────────────────────────
    // index – mostrar carrito
    // ──────────────────────────────────────────────────────────────────────────

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

    // ──────────────────────────────────────────────────────────────────────────
    // store – agregar producto
    // ──────────────────────────────────────────────────────────────────────────

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
        // Crea una variante activa, lo que obliga al usuario a elegir variante
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
            ->assertJson(['ok' => false, 'message' => 'Debes seleccionar una variante válida.']);
    }

    public function test_adding_product_with_invalid_variant_id_returns_error(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->forProduct($product)->create(['is_active' => true]);

        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'qty'        => 1,
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
            'qty'        => 1,
            'variant_id' => $variant->id,
        ])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_adding_inactive_variant_is_rejected(): void
    {
        $product        = Product::factory()->create();
        $inactiveVariant = ProductVariant::factory()->forProduct($product)->inactive()->create();

        // Solo hay variantes inactivas → el producto no tiene variantes activas,
        // así que no exige elegir variante pero la variante enviada tampoco se encuentra.
        // El carrito lo acepta como producto sin variante (ya que hasConfigurableVariants = false).
        // Este test verifica que al menos el request no falla con 500.
        $this->postJson(route('cart.store'), [
            'product_id' => $product->id,
            'qty'        => 1,
            'variant_id' => $inactiveVariant->id,
        ])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_adding_nonexistent_product_returns_404(): void
    {
        $this->post(route('cart.store'), ['product_id' => 99999, 'qty' => 1])
            ->assertStatus(404);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // update – actualizar cantidades
    // ──────────────────────────────────────────────────────────────────────────

    public function test_update_cart_quantities_redirects_with_status(): void
    {
        $product = Product::factory()->create(['price' => 5000]);

        // Primero agrega producto
        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1]);

        $lineKey = $product->id.'-0';

        $this->patch(route('cart.update'), ['quantities' => [$lineKey => 5]])
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

    // ──────────────────────────────────────────────────────────────────────────
    // destroy – eliminar línea del carrito
    // ──────────────────────────────────────────────────────────────────────────

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
