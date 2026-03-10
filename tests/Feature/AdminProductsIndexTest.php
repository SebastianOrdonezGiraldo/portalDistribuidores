<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminProductsIndexTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_from_admin_products_index(): void
    {
        $this->get('/admin/products')
            ->assertRedirect('/login');
    }

    public function test_distributor_cannot_access_admin_products_index(): void
    {
        $distributorUser = User::factory()->create();

        $this->actingAs($distributorUser)
            ->get('/admin/products')
            ->assertForbidden();
    }

    public function test_admin_can_filter_and_sort_products(): void
    {
        $admin = User::factory()->admin()->create();

        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria Admin Products',
            'slug' => 'categoria-admin-products',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $token = 'ZZ-PROD-FILTER';

        $lowPrice = Product::create([
            'name' => 'Producto '.$token.' Bajo',
            'sku' => 'SKU-PROD-LOW',
            'description' => 'Filtro test',
            'category_id' => $category->id,
            'price' => 10000,
            'stock' => 20,
            'is_active' => true,
        ]);

        $highPrice = Product::create([
            'name' => 'Producto '.$token.' Alto',
            'sku' => 'SKU-PROD-HIGH',
            'description' => 'Filtro test',
            'category_id' => $category->id,
            'price' => 90000,
            'stock' => 0,
            'is_active' => true,
        ]);

        $inactive = Product::create([
            'name' => 'Producto sin token',
            'sku' => 'SKU-PROD-INACTIVE',
            'description' => 'No debe aparecer en filtro',
            'category_id' => $category->id,
            'price' => 50000,
            'stock' => null,
            'is_active' => false,
        ]);

        $highPrice->photos()->create([
            'path' => 'products/photos/high.png',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/products?status=active&media=with_photo&q='.$token.'&sort=price_desc&per_page=30');

        $response->assertOk();
        $response->assertSee('Producto '.$token.' Alto');
        $response->assertDontSee('Producto '.$token.' Bajo');
        $response->assertDontSee('Producto sin token');

        $response->assertViewHas('products', function ($products) use ($highPrice) {
            if ((int) $products->total() !== 1) {
                return false;
            }

            return $products->getCollection()->first()?->id === $highPrice->id;
        });

        $response->assertViewHas('metrics', fn (array $metrics) => (int) $metrics['total_products'] === 1);

        // Prevent "unused variable" static warnings for explicit fixture readability.
        $this->assertNotNull($lowPrice->id);
        $this->assertNotNull($inactive->id);
    }
}
