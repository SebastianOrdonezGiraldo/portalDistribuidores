<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Http\Controllers\CartController;
use App\Modules\Shared\Enums\DistributorStatus;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributor_can_login(): void
    {
        $password = Str::random(32);

        $distributor = Distributor::create([
            'name' => 'Distribuidor Test',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Distribuidor',
            'email' => 'dist@login.test',
            'password' => Hash::make($password),
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'dist@login.test',
            'password' => $password,
        ]);

        $response->assertRedirect(route('catalog.index', absolute: false));
        $this->assertAuthenticated();
    }

    public function test_distributor_cannot_login_when_company_is_pending_review(): void
    {
        $password = Str::random(32);

        $distributor = Distributor::create([
            'name' => 'Distribuidor Pendiente',
            'status' => DistributorStatus::PendingReview,
        ]);

        User::create([
            'name' => 'Distribuidor Pendiente',
            'email' => 'pending@login.test',
            'password' => Hash::make($password),
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'pending@login.test',
            'password' => $password,
        ]);

        $response->assertSessionHasErrors([
            'email' => trans('auth.company_pending_review'),
        ]);
        $this->assertGuest();
    }

    public function test_distributor_login_consumes_pending_cart_and_returns_to_product(): void
    {
        $password = Str::random(32);
        $product = Product::factory()->create([
            'price' => 10000,
            'stock' => 20,
        ]);

        $distributor = Distributor::create([
            'name' => 'Distribuidor Carrito',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Distribuidor Carrito',
            'email' => 'cart-login@test.com',
            'password' => Hash::make($password),
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $this->withSession([
            CartController::PENDING_CART_SESSION_KEY => [
                'product_id' => $product->id,
                'variant_id' => null,
                'qty' => 2,
                'unit_label' => 'unidad',
                'return_url' => route('products.show', $product, absolute: false),
            ],
        ]);

        $response = $this->post('/login', [
            'email' => 'cart-login@test.com',
            'password' => $password,
        ]);

        $response
            ->assertRedirect(route('products.show', $product, absolute: false))
            ->assertSessionHas('status', 'Producto agregado al carrito.');

        $items = $this->get(route('cart.index'))->viewData('items');

        $this->assertCount(1, $items);
        $this->assertSame($product->id, $items->first()['product']->id);
        $this->assertSame(2, $items->first()['qty']);
        $this->assertNull(session(CartController::PENDING_CART_SESSION_KEY));
    }
}
