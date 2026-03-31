<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Support\ProductUploadLimits;
use App\Modules\Categories\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
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

    public function test_admin_update_flow_preserves_listing_context_when_present(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();
        $product = $this->createProduct($category, 'SKU-REDIRECT-CONTEXT-001');

        $basePayload = [
            'name' => 'Producto Redirect Context',
            'brand' => 'Marca Redirect Context',
            'sku' => 'SKU-REDIRECT-CONTEXT-001',
            'description' => 'Edicion con contexto',
            'category_id' => $category->id,
            'price' => 22345,
            'stock' => 11,
            'is_active' => 1,
        ];

        $indexContext = [
            'q' => 'guante',
            'status' => 'inactive',
            'media' => 'without_photo',
            'sort' => 'name_desc',
            'per_page' => 30,
            'page' => 2,
        ];

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put('/admin/products/'.$product->id, array_merge($basePayload, [
                'after_save' => 'save',
                'index_context' => $indexContext,
                '_token' => 'test-token',
            ]))
            ->assertRedirect(route('admin.products.edit', array_merge(['product' => $product], $indexContext)));

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put('/admin/products/'.$product->id, array_merge($basePayload, [
                'after_save' => 'stay',
                'index_context' => $indexContext,
                '_token' => 'test-token',
            ]))
            ->assertRedirect(route('admin.products.edit', array_merge(['product' => $product], $indexContext)));

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put('/admin/products/'.$product->id, array_merge($basePayload, [
                'after_save' => 'index',
                'index_context' => $indexContext,
                '_token' => 'test-token',
            ]))
            ->assertRedirect(route('admin.products.index', $indexContext));
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

    public function test_admin_can_delete_product_from_admin_route(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();
        $product = $this->createProduct($category, 'SKU-DELETE-001');

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->delete('/admin/products/'.$product->id, ['_token' => 'test-token'])
            ->assertRedirect('/admin/products')
            ->assertSessionHas('status', 'Producto eliminado.');

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_admin_can_delete_product_and_preserve_listing_context(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();
        $product = $this->createProduct($category, 'SKU-DELETE-CONTEXT-001');

        $product->update(['is_active' => false]);
        $product->photos()->create([
            'path' => 'products/photos/delete-context.png',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $indexContext = [
            'q' => 'SKU-DELETE-CONTEXT-001',
            'status' => 'inactive',
            'media' => 'with_photo',
            'sort' => 'name_desc',
            'per_page' => 30,
        ];

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->delete('/admin/products/'.$product->id, [
                'index_context' => $indexContext,
                '_token' => 'test-token',
            ])
            ->assertRedirect(route('admin.products.index', $indexContext))
            ->assertSessionHas('status', 'Producto eliminado.');

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_admin_delete_reduces_to_previous_page_when_filtered_page_becomes_invalid(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();
        $token = 'PAGE-ADJUST-CONTEXT';

        for ($index = 1; $index <= 30; $index++) {
            Product::create([
                'name' => 'Producto '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'sku' => 'SKU-PAGE-ADJUST-'.$index,
                'description' => $token,
                'category_id' => $category->id,
                'price' => 1000 + $index,
                'stock' => 10,
                'is_active' => true,
            ]);
        }

        $product = Product::create([
            'name' => 'Producto 31',
            'sku' => 'SKU-PAGE-ADJUST-31',
            'description' => $token,
            'category_id' => $category->id,
            'price' => 1031,
            'stock' => 10,
            'is_active' => true,
        ]);

        $indexContext = [
            'q' => $token,
            'status' => 'active',
            'sort' => 'name_asc',
            'per_page' => 30,
            'page' => 2,
        ];

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->delete('/admin/products/'.$product->id, [
                'index_context' => $indexContext,
                '_token' => 'test-token',
            ])
            ->assertRedirect(route('admin.products.index', [
                'q' => $token,
                'status' => 'active',
                'sort' => 'name_asc',
                'per_page' => 30,
            ]))
            ->assertSessionHas('status', 'Producto eliminado.');

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_guest_cannot_delete_product_from_admin_route(): void
    {
        $category = $this->createCategory();
        $product = $this->createProduct($category, 'SKU-DELETE-GUEST-001');

        $this->delete('/admin/products/'.$product->id)
            ->assertRedirect('/login');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    public function test_distributor_cannot_delete_product_from_admin_route(): void
    {
        $distributorUser = User::factory()->create();
        $category = $this->createCategory();
        $product = $this->createProduct($category, 'SKU-DELETE-DIST-001');

        $this->actingAs($distributorUser)
            ->delete('/admin/products/'.$product->id)
            ->assertForbidden();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    public function test_admin_can_store_product_with_manual_document(): void
    {
        Storage::fake('private');

        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();

        $response = $this->actingAs($admin)
            ->post('/admin/products', array_merge($this->validProductPayload($category), [
                'manual' => $this->fakePdfUpload('manual-usuario.pdf'),
            ]));

        $product = Product::query()->where('sku', 'SKU-NEW-001')->firstOrFail();
        $document = $product->documents()->where('type', 'manual')->first();

        $response->assertRedirect('/admin/products/'.$product->id.'/edit');
        $this->assertNotNull($document);
        Storage::disk('private')->assertExists($document->path);
    }

    public function test_admin_update_replaces_existing_manual_document(): void
    {
        Storage::fake('private');

        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();
        $product = $this->createProduct($category, 'SKU-MANUAL-UPDATE-001');

        Storage::disk('private')->put('products/documents/manual-anterior.pdf', '%PDF-1.4 old manual');
        $existingDocument = $product->documents()->create([
            'type' => 'manual',
            'path' => 'products/documents/manual-anterior.pdf',
            'filename' => 'manual-anterior.pdf',
        ]);

        $payload = [
            'name' => 'Producto Editor',
            'brand' => 'Marca Manual',
            'sku' => 'SKU-MANUAL-UPDATE-001',
            'description' => 'Producto con manual actualizado',
            'category_id' => $category->id,
            'price' => 10000,
            'stock' => 15,
            'is_active' => 1,
            'manual' => $this->fakePdfUpload('manual-nuevo.pdf'),
        ];

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put('/admin/products/'.$product->id, array_merge($payload, ['_token' => 'test-token']));

        $updatedDocument = $product->fresh()->documents()->where('type', 'manual')->first();

        $response->assertRedirect('/admin/products/'.$product->id.'/edit');
        $this->assertNotNull($updatedDocument);
        $this->assertSame($existingDocument->id, $updatedDocument->id);
        $this->assertNotSame($existingDocument->path, $updatedDocument->path);
        Storage::disk('private')->assertMissing($existingDocument->path);
        Storage::disk('private')->assertExists($updatedDocument->path);
        $this->assertSame(1, $product->fresh()->documents()->where('type', 'manual')->count());
    }

    public function test_store_product_shows_clear_error_when_tech_sheet_exceeds_individual_limit(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();

        $response = $this->actingAs($admin)
            ->from('/admin/products/create')
            ->post('/admin/products', array_merge($this->validProductPayload($category), [
                'tech_sheet' => UploadedFile::fake()->create('ficha-tecnica.pdf', ProductUploadLimits::techSheetMaxSizeKb() + 1, 'application/pdf'),
            ]));

        $response
            ->assertRedirect('/admin/products/create')
            ->assertSessionHasErrors([
                'tech_sheet' => 'La ficha tecnica debe pesar como maximo '.ProductUploadLimits::techSheetMaxSizeLabel().'.',
            ]);
    }

    public function test_store_product_shows_clear_error_when_manual_exceeds_individual_limit(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();

        $response = $this->actingAs($admin)
            ->from('/admin/products/create')
            ->post('/admin/products', array_merge($this->validProductPayload($category), [
                'manual' => UploadedFile::fake()->create('manual-usuario.pdf', ProductUploadLimits::manualMaxSizeKb() + 1, 'application/pdf'),
            ]));

        $response
            ->assertRedirect('/admin/products/create')
            ->assertSessionHasErrors([
                'manual' => 'El manual de usuario debe pesar como maximo '.ProductUploadLimits::manualMaxSizeLabel().'.',
            ]);
    }

    public function test_store_product_shows_clear_error_when_photo_exceeds_individual_limit(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();

        $response = $this->actingAs($admin)
            ->from('/admin/products/create')
            ->post('/admin/products', array_merge($this->validProductPayload($category), [
                'photos' => [
                    UploadedFile::fake()->create('producto-grande.jpg', ProductUploadLimits::photoMaxSizeKb() + 1, 'image/jpeg'),
                ],
            ]));

        $response
            ->assertRedirect('/admin/products/create')
            ->assertSessionHasErrors([
                'photos.0' => 'Cada foto debe pesar como maximo '.ProductUploadLimits::photoMaxSizeLabel().'.',
            ]);
    }

    public function test_post_too_large_redirects_back_with_media_upload_error(): void
    {
        $admin = User::factory()->admin()->create();

        Route::middleware('web')->post('/test-post-too-large', function () {
            throw new PostTooLargeException();
        });

        $response = $this->actingAs($admin)
            ->from('/admin/products/create')
            ->post('/test-post-too-large');

        $response
            ->assertRedirect('/admin/products/create')
            ->assertSessionHasErrors([
                'media_upload' => ProductUploadLimits::totalSizeExceededMessage(),
            ]);
    }

    public function test_store_product_shows_clear_error_when_photo_upload_is_rejected_by_php_limit(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();

        $response = $this->actingAs($admin)
            ->from('/admin/products/create')
            ->post('/admin/products', array_merge($this->validProductPayload($category), [
                'photos' => [
                    $this->invalidUploadedFile('producto-servidor.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE),
                ],
            ]));

        $response
            ->assertRedirect('/admin/products/create')
            ->assertSessionHasErrors([
                'photos.0' => ProductUploadLimits::photoUploadFailedMessage(),
            ]);
    }

    public function test_store_product_requires_stock_when_product_is_published(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();

        $payload = $this->validProductPayload($category);
        unset($payload['stock']);
        $payload['sku'] = 'SKU-NEW-NO-STOCK-001';
        $payload['is_active'] = 1;

        $response = $this->actingAs($admin)
            ->from('/admin/products/create')
            ->post('/admin/products', $payload);

        $response
            ->assertRedirect('/admin/products/create')
            ->assertSessionHasErrors([
                'stock' => 'El stock es obligatorio para publicar el producto.',
            ]);
    }

    public function test_store_product_requires_variant_stock_when_product_with_variants_is_published(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();

        $response = $this->actingAs($admin)
            ->from('/admin/products/create')
            ->post('/admin/products', [
                'name' => 'Producto Variante sin Stock',
                'brand' => 'Marca Variante',
                'sku' => 'SKU-VAR-NO-STOCK-001',
                'description' => 'Producto con variante sin stock',
                'category_id' => $category->id,
                'has_variants' => 1,
                'new_variant_attribute_name' => 'Color',
                'variants' => [
                    ['value' => 'Rojo', 'price' => 18000, 'stock' => 5],
                    ['value' => 'Azul', 'price' => 21000, 'stock' => ''],
                ],
                'is_active' => 1,
            ]);

        $response
            ->assertRedirect('/admin/products/create')
            ->assertSessionHasErrors([
                'variants.1.stock' => 'El stock de cada variante es obligatorio para publicar el producto.',
            ]);
    }

    public function test_post_too_large_without_referer_uses_safe_fallback_and_preserves_old_input(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->createCategory();

        Route::middleware('web')->post('/test-post-too-large-fallback', function () {
            throw new PostTooLargeException();
        });

        $payload = array_merge($this->validProductPayload($category), [
            'description' => 'Producto que debe volver al formulario con datos previos.',
        ]);

        $response = $this->actingAs($admin)
            ->post('/test-post-too-large-fallback', $payload);

        $response
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors([
                'media_upload' => ProductUploadLimits::totalSizeExceededMessage(),
            ])
            ->assertSessionHasInput([
                'name' => $payload['name'],
                'brand' => $payload['brand'],
                'sku' => $payload['sku'],
            ]);
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

    private function validProductPayload(Category $category): array
    {
        return [
            'name' => 'Producto Nuevo',
            'brand' => 'Marca Nueva',
            'sku' => 'SKU-NEW-001',
            'description' => 'Producto con media',
            'category_id' => $category->id,
            'price' => 15000,
            'stock' => 12,
            'is_active' => 1,
        ];
    }

    private function invalidUploadedFile(string $filename, string $mimeType, int $error): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'product-upload-');

        if ($tempPath === false) {
            throw new \RuntimeException('No fue posible crear un archivo temporal para la prueba.');
        }

        file_put_contents($tempPath, 'temporary-upload');

        return new UploadedFile($tempPath, $filename, $mimeType, $error, true);
    }

    private function fakePdfUpload(string $filename): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'product-manual-upload-');

        if ($tempPath === false) {
            throw new \RuntimeException('No fue posible crear un PDF temporal para la prueba.');
        }

        file_put_contents($tempPath, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n");

        return new UploadedFile($tempPath, $filename, 'application/pdf', null, true);
    }
}
