<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductsIndexTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_admin_search_matches_name_fragments(): void
    {
        $admin = User::factory()->admin()->create();

        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria Fragment Search',
            'slug' => 'categoria-fragment-search',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $matchingProduct = Product::create([
            'name' => 'Guante Nitrilo Premium',
            'sku' => 'SKU-FRAGMENT-MATCH',
            'description' => 'Guante para procedimientos',
            'category_id' => $category->id,
            'price' => 35000,
            'stock' => 10,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Mascarilla Quirurgica',
            'sku' => 'SKU-FRAGMENT-OTHER',
            'description' => 'Proteccion facial',
            'category_id' => $category->id,
            'price' => 12000,
            'stock' => 25,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/products?q=nitri');

        $response->assertOk();
        $response->assertSee('Guante Nitrilo Premium');
        $response->assertDontSee('Mascarilla Quirurgica');
        $response->assertViewHas('products', function ($products) use ($matchingProduct) {
            if ((int) $products->total() !== 1) {
                return false;
            }

            return $products->getCollection()->first()?->id === $matchingProduct->id;
        });
    }

    public function test_admin_search_matches_multi_word_terms_in_any_order(): void
    {
        $admin = User::factory()->admin()->create();

        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria Multi Word Search',
            'slug' => 'categoria-multi-word-search',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $matchingProduct = Product::create([
            'name' => 'Guante Nitrilo Premium',
            'sku' => 'SKU-MULTIWORD-MATCH',
            'description' => 'Proteccion para examen clinico',
            'category_id' => $category->id,
            'price' => 18000,
            'stock' => 8,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Guante Latex Basico',
            'sku' => 'SKU-MULTIWORD-GLOVE',
            'description' => 'Guante sin nitrilo',
            'category_id' => $category->id,
            'price' => 9000,
            'stock' => 30,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Caja Premium',
            'sku' => 'SKU-MULTIWORD-PREMIUM',
            'description' => 'Caja de almacenamiento',
            'category_id' => $category->id,
            'price' => 22000,
            'stock' => 4,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/products?q=premium+nitrilo');

        $response->assertOk();
        $response->assertSee('Guante Nitrilo Premium');
        $response->assertDontSee('Guante Latex Basico');
        $response->assertDontSee('Caja Premium');
        $response->assertViewHas('products', function ($products) use ($matchingProduct) {
            if ((int) $products->total() !== 1) {
                return false;
            }

            return $products->getCollection()->first()?->id === $matchingProduct->id;
        });
    }

    public function test_admin_search_is_accent_insensitive(): void
    {
        $admin = User::factory()->admin()->create();

        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria Accent Search',
            'slug' => 'categoria-accent-search',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $matchingProduct = Product::create([
            'name' => 'Termómetro Digital',
            'sku' => 'SKU-ACCENT-MATCH',
            'description' => 'Medicion precisa de temperatura',
            'category_id' => $category->id,
            'price' => 27000,
            'stock' => 15,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Tensiometro Manual',
            'sku' => 'SKU-ACCENT-OTHER',
            'description' => 'Control de presion arterial',
            'category_id' => $category->id,
            'price' => 41000,
            'stock' => 12,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/products?q=termometro');

        $response->assertOk();
        $response->assertSee('Termómetro Digital');
        $response->assertDontSee('Tensiometro Manual');
        $response->assertViewHas('products', function ($products) use ($matchingProduct) {
            if ((int) $products->total() !== 1) {
                return false;
            }

            return $products->getCollection()->first()?->id === $matchingProduct->id;
        });
    }

    public function test_admin_products_index_renders_delete_action_for_each_product(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria Delete Action',
            'slug' => 'categoria-delete-action',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'name' => 'Producto Eliminable',
            'sku' => 'SKU-DELETE-ACTION',
            'description' => 'Producto para validar accion de eliminar',
            'category_id' => $category->id,
            'price' => 25000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/products');

        $response->assertOk();
        $response->assertSee(route('admin.products.destroy', $product), false);
        $response->assertSee('aria-label="Eliminar '.$product->name.'"', false);
        $response->assertSee('data-confirm="Eliminar '.$product->name.'? Esta accion no se puede deshacer."', false);
    }

    public function test_admin_products_index_edit_links_preserve_current_context(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria Edit Context',
            'slug' => 'categoria-edit-context',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $token = 'LINK-CONTEXT-TOKEN';

        for ($index = 1; $index <= 15; $index++) {
            Product::create([
                'name' => 'Producto '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'sku' => 'SKU-LINK-'.$index,
                'description' => $token,
                'category_id' => $category->id,
                'price' => 1000 + $index,
                'stock' => 10,
                'is_active' => true,
            ]);
        }

        $targetProduct = Product::create([
            'name' => 'Producto 16',
            'sku' => 'SKU-LINK-16',
            'description' => $token,
            'category_id' => $category->id,
            'price' => 1016,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/products?q='.$token.'&status=active&sort=name_asc&per_page=15&page=2');

        $response->assertOk();
        $response->assertSee(route('admin.products.edit', [
            'product' => $targetProduct,
            'q' => $token,
            'status' => 'active',
            'sort' => 'name_asc',
            'per_page' => 15,
            'page' => 2,
        ]));
    }
}
