<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_categories(): void
    {
        $this->get('/admin/categories')
            ->assertRedirect('/login');
    }

    public function test_distributor_cannot_access_admin_categories(): void
    {
        $distributorUser = User::factory()->create();

        $this->actingAs($distributorUser)
            ->get('/admin/categories')
            ->assertForbidden();
    }

    public function test_admin_can_filter_categories_list(): void
    {
        $admin = User::factory()->admin()->create();

        $active = Category::create([
            'name' => 'Categoria Activa Filter',
            'slug' => 'categoria-activa-filter',
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $inactive = Category::create([
            'name' => 'Categoria Inactiva Filter',
            'slug' => 'categoria-inactiva-filter',
            'parent_id' => null,
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $active->synonyms()->create(['term' => 'term-busqueda-categoria']);

        Product::create([
            'name' => 'Producto en categoria activa',
            'sku' => 'CAT-FLT-001',
            'description' => 'Producto para filtro de categoria',
            'category_id' => $active->id,
            'price' => 1000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/categories?status=active&with_products=yes&q=term-busqueda-categoria');

        $response->assertOk();
        $response->assertSee('Categoria Activa Filter');
        $response->assertDontSee('Categoria Inactiva Filter');
    }

    public function test_admin_cannot_delete_category_with_products_or_children(): void
    {
        $admin = User::factory()->admin()->create();

        $withProducts = Category::create([
            'name' => 'Categoria con Productos',
            'slug' => 'categoria-con-productos',
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Product::create([
            'name' => 'Producto en categoria bloqueada',
            'sku' => 'CAT-DEL-001',
            'description' => 'Producto que bloquea borrado',
            'category_id' => $withProducts->id,
            'price' => 1000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $parent = Category::create([
            'name' => 'Categoria Padre',
            'slug' => 'categoria-padre',
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Category::create([
            'name' => 'Categoria Hija',
            'slug' => 'categoria-hija',
            'parent_id' => $parent->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->delete('/admin/categories/'.$withProducts->id, ['_token' => 'test-token'])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['id' => $withProducts->id]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->delete('/admin/categories/'.$parent->id, ['_token' => 'test-token'])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    }

    public function test_admin_can_toggle_status_and_cannot_create_cycle(): void
    {
        $admin = User::factory()->admin()->create();

        $parent = Category::create([
            'name' => 'Categoria Parent Toggle',
            'slug' => 'categoria-parent-toggle',
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $child = Category::create([
            'name' => 'Categoria Child Toggle',
            'slug' => 'categoria-child-toggle',
            'parent_id' => $parent->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->patch('/admin/categories/'.$parent->id.'/status', ['is_active' => 0, '_token' => 'test-token'])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'id' => $parent->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put('/admin/categories/'.$parent->id, [
                '_token' => 'test-token',
                'name' => $parent->name,
                'slug' => $parent->slug,
                'parent_id' => $child->id,
                'is_active' => 1,
                'sort_order' => 1,
                'synonyms' => '',
            ]);

        $response->assertSessionHasErrors('parent_id');

        $this->assertDatabaseHas('categories', [
            'id' => $parent->id,
            'parent_id' => null,
        ]);
    }
}
