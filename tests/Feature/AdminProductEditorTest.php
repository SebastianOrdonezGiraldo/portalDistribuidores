<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_check_sku_availability(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();
        $product = $this->createProduct($category, 'SKU-EDITOR-001');

        $this->actingAs($admin)
            ->get('/admin/products/check-sku?sku=SKU-EDITOR-001')
            ->assertOk()
            ->assertJson(['available' => false]);

        $this->actingAs($admin)
            ->get('/admin/products/check-sku?sku=SKU-EDITOR-NEW')
            ->assertOk()
            ->assertJson(['available' => true]);

        $this->actingAs($admin)
            ->get('/admin/products/check-sku?sku=SKU-EDITOR-001&ignore='.$product->id)
            ->assertOk()
            ->assertJson(['available' => true]);
    }

    public function test_admin_can_choose_redirect_after_updating_product(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();
        $product = $this->createProduct($category, 'SKU-REDIRECT-001');

        $basePayload = [
            'name' => 'Producto Redirect',
            'brand' => 'Marca Redirect',
            'sku' => 'SKU-REDIRECT-001',
            'description' => 'Edicion de prueba',
            'category_id' => $category->id,
            'price' => 12345,
            'stock' => 10,
            'is_active' => 1,
        ];

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put('/admin/products/'.$product->id, array_merge($basePayload, ['after_save' => 'save', '_token' => 'test-token']))
            ->assertRedirect('/admin/products/'.$product->id.'/edit');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'brand' => 'Marca Redirect',
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put('/admin/products/'.$product->id, array_merge($basePayload, ['after_save' => 'stay', '_token' => 'test-token']))
            ->assertRedirect('/admin/products/'.$product->id.'/edit');

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put('/admin/products/'.$product->id, array_merge($basePayload, ['after_save' => 'index', '_token' => 'test-token']))
            ->assertRedirect('/admin/products');
    }

    public function test_admin_can_deactivate_and_remove_product_media(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();
        $product = $this->createProduct($category, 'SKU-MEDIA-001');

        $photo = $product->photos()->create([
            'path' => 'products/photos/test-remove.png',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $document = $product->documents()->create([
            'type' => 'tech_sheet',
            'path' => 'products/documents/test-remove.pdf',
            'filename' => 'Ficha-remove.pdf',
        ]);

        $video = $product->videos()->create([
            'url' => 'https://www.youtube.com/watch?v=test-remove',
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->patch('/admin/products/'.$product->id.'/status', ['is_active' => 0, '_token' => 'test-token'])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->delete('/admin/products/'.$product->id.'/photos/'.$photo->id, ['_token' => 'test-token'])
            ->assertRedirect();

        $this->assertDatabaseMissing('product_photos', ['id' => $photo->id]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->delete('/admin/products/'.$product->id.'/documents/'.$document->id, ['_token' => 'test-token'])
            ->assertRedirect();

        $this->assertDatabaseMissing('product_documents', ['id' => $document->id]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->delete('/admin/products/'.$product->id.'/videos/'.$video->id, ['_token' => 'test-token'])
            ->assertRedirect();

        $this->assertDatabaseMissing('product_videos', ['id' => $video->id]);
    }

    private function createCategory(): Category
    {
        return Category::create([
            'parent_id' => null,
            'name' => 'Categoria Editor Product',
            'slug' => 'categoria-editor-product',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createProduct(Category $category, string $sku): Product
    {
        return Product::create([
            'name' => 'Producto Editor',
            'sku' => $sku,
            'description' => 'Producto de prueba editor',
            'category_id' => $category->id,
            'price' => 10000,
            'stock' => 15,
            'is_active' => true,
        ]);
    }
}
