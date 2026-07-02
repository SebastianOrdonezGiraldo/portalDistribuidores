<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Company\Models\CompanyBranch;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Carrito vacío
    // ──────────────────────────────────────────────────────────────────────────

    public function test_checkout_redirects_when_cart_is_empty(): void
    {
        $distributor = Distributor::factory()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);

        $this->actingAs($user)
            ->get(route('checkout.show'))
            ->assertRedirect(route('catalog.index'))
            ->assertSessionHasErrors();
    }

    public function test_checkout_redirects_when_guest_cart_is_empty(): void
    {
        $this->get(route('checkout.show'))
            ->assertRedirect(route('catalog.index'))
            ->assertSessionHasErrors();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Checkout válido – distribuidor con sucursales
    // ──────────────────────────────────────────────────────────────────────────

    public function test_checkout_renders_correctly_with_items_for_distributor(): void
    {
        $distributor = Distributor::factory()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 15000]);

        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 3]);

        $response = $this->actingAs($user)->get(route('checkout.show'));

        $response->assertOk()
            ->assertViewIs('orders.checkout')
            ->assertViewHas('items')
            ->assertViewHas('total')
            ->assertViewHas('distributor')
            ->assertViewHas('branches')
            ->assertViewHas('departments')
            ->assertSee('data-flow-steps', false)
            ->assertSee('data-current-step="checkout"', false)
            ->assertSee('Obligatorio')
            ->assertSee('Opcional');

        $this->assertEquals(45000.0, $response->viewData('total'));
    }

    public function test_checkout_passes_branches_ordered_by_default_then_name(): void
    {
        $distributor = Distributor::factory()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create();

        CompanyBranch::create([
            'distributor_id' => $distributor->id,
            'name' => 'Sucursal B',
            'address' => 'Dir B',
            'city' => 'City B',
            'is_default' => false,
        ]);
        CompanyBranch::create([
            'distributor_id' => $distributor->id,
            'name' => 'Sucursal A (Default)',
            'address' => 'Dir A',
            'city' => 'City A',
            'is_default' => true,
        ]);

        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1]);

        $branches = $this->actingAs($user)
            ->get(route('checkout.show'))
            ->viewData('branches');

        $this->assertCount(2, $branches);
        $this->assertTrue((bool) $branches->first()->is_default);
    }

    public function test_checkout_renders_correctly_for_guest_user_with_items(): void
    {
        $product = Product::factory()->create(['price' => 5000]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1]);

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertViewIs('orders.checkout')
            ->assertViewHas('items')
            ->assertViewHas('distributor', null);
    }

    public function test_checkout_distributor_can_access(): void
    {
        $distributor = Distributor::factory()->create();
        $user = User::factory()->create([
            'distributor_id' => $distributor->id,
        ]);
        $product = Product::factory()->create();

        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1]);

        $this->actingAs($user)
            ->get(route('checkout.show'))
            ->assertOk();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Checkout refleja la cantidad actualizada en el carrito
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Reproduce el bug: el usuario agrega qty=1 desde el catálogo, luego cambia
     * la cantidad a 5 en el carrito (PATCH cart.update) y va al checkout.
     * Sin la corrección, checkout mostraría qty=1. Con la corrección, muestra 5.
     */
    public function test_checkout_reflects_quantity_updated_in_cart(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 50]);

        // El catálogo siempre envía qty=1
        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1]);

        // El usuario edita la cantidad a 5 en la página del carrito
        $lineKey = $product->id.'-0';
        $this->patch(route('cart.update'), ['quantities' => [$lineKey => 5]]);

        $response = $this->get(route('checkout.show'));
        $response->assertOk();

        $items = $response->viewData('items');
        $this->assertCount(1, $items);
        $this->assertEquals(5, $items->first()['qty']);
        $this->assertEquals(50000.0, $response->viewData('total'));
    }

    public function test_checkout_reflects_quantity_when_redirected_via_redirect_checkout_flag(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 50]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1]);

        $lineKey = $product->id.'-0';

        // El botón "Continuar al checkout" envía redirect_checkout=1
        $this->patch(route('cart.update'), [
            'quantities' => [$lineKey => 7],
            'redirect_checkout' => '1',
        ])->assertRedirect(route('checkout.show'));

        $response = $this->get(route('checkout.show'));
        $response->assertOk();

        $items = $response->viewData('items');
        $this->assertEquals(7, $items->first()['qty']);
        $this->assertEquals(70000.0, $response->viewData('total'));
    }

    public function test_checkout_total_matches_updated_cart_quantities(): void
    {
        $productA = Product::factory()->create(['price' => 5000, 'stock' => 100]);
        $productB = Product::factory()->create(['price' => 8000, 'stock' => 100]);

        $this->post(route('cart.store'), ['product_id' => $productA->id, 'qty' => 1]);
        $this->post(route('cart.store'), ['product_id' => $productB->id, 'qty' => 1]);

        $lineKeyA = $productA->id.'-0';
        $lineKeyB = $productB->id.'-0';

        // Usuario actualiza ambas cantidades
        $this->patch(route('cart.update'), [
            'quantities' => [
                $lineKeyA => 3,
                $lineKeyB => 2,
            ],
        ]);

        $response = $this->get(route('checkout.show'));
        $response->assertOk();

        // 3 × 5000 + 2 × 8000 = 31000
        $this->assertEquals(31000.0, $response->viewData('total'));

        $items = collect($response->viewData('items'));
        $itemA = $items->first(fn ($item) => $item['product']->id === $productA->id);
        $itemB = $items->first(fn ($item) => $item['product']->id === $productB->id);
        $this->assertEquals(3, $itemA['qty']);
        $this->assertEquals(2, $itemB['qty']);
    }

    public function test_checkout_still_shows_original_quantity_if_cart_not_updated(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 50]);

        // Se agrega con qty=3 directamente desde ficha de producto
        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 3]);

        // Sin ningún PATCH intermedio, checkout debe mostrar qty=3
        $response = $this->get(route('checkout.show'));
        $response->assertOk();

        $items = $response->viewData('items');
        $this->assertEquals(3, $items->first()['qty']);
        $this->assertEquals(30000.0, $response->viewData('total'));
    }
}
