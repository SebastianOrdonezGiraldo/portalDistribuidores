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
    // Acceso sin permisos
    // ──────────────────────────────────────────────────────────────────────────

    public function test_checkout_redirects_when_user_is_inactive(): void
    {
        $distributor = Distributor::factory()->create();
        $user = User::factory()->inactive()->create([
            'distributor_id' => $distributor->id,
        ]);

        $product = Product::factory()->create();
        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1]);

        $this->actingAs($user)
            ->get(route('checkout.show'))
            ->assertRedirect(route('empresa.dashboard'))
            ->assertSessionHasErrors();
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
            ->assertViewHas('departments');

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
}
