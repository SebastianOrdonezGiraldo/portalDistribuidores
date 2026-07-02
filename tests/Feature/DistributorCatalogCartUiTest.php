<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributorCatalogCartUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributor_catalog_renders_cart_target_and_empty_badge(): void
    {
        $distributor = Distributor::create([
            'name' => 'Distribuidor UI',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSee('data-cart-target', false);
        $response->assertSee('data-cart-badge', false);
        $response->assertSee('>0<', false);
    }

    public function test_distributor_catalog_prioritizes_products_with_stock_and_shows_stock_status_label(): void
    {
        $distributor = Distributor::create([
            'name' => 'Distribuidor UI',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Producto Agotado',
            'stock' => 0,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Producto En Stock',
            'stock' => 15,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['Producto En Stock', 'Producto Agotado']);
        $response->assertSee('En stock');
        $response->assertSee('Agotado');
    }
}
