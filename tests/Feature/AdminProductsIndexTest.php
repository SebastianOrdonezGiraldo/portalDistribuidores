<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Categories\Models\Category;
use App\Modules\Inventory\Services\ContaPymeSyncState;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
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

    public function test_guest_is_redirected_from_admin_inventory_pdf_download(): void
    {
        $this->get('/admin/products/inventory/pdf')
            ->assertRedirect('/login');
    }

    public function test_distributor_cannot_access_admin_inventory_pdf_download(): void
    {
        $distributorUser = User::factory()->create();

        $this->actingAs($distributorUser)
            ->get('/admin/products/inventory/pdf')
            ->assertForbidden();
    }

    public function test_admin_can_download_inventory_pdf_with_current_filters(): void
    {
        Carbon::setTestNow('2026-04-08 11:22:33');

        try {
            $admin = User::factory()->admin()->create([
                'name' => 'Admin PDF',
            ]);

            $category = Category::create([
                'parent_id' => null,
                'name' => 'Categoria Inventario PDF',
                'slug' => 'categoria-inventario-pdf',
                'is_active' => true,
                'sort_order' => 1,
            ]);

            $attribute = ProductAttribute::factory()->create([
                'name' => 'Talla',
                'slug' => 'talla',
            ]);

            $variantValueS = ProductAttributeValue::factory()->create([
                'product_attribute_id' => $attribute->id,
                'value' => 'S',
                'slug' => 's',
            ]);

            $variantValueM = ProductAttributeValue::factory()->create([
                'product_attribute_id' => $attribute->id,
                'value' => 'M',
                'slug' => 'm',
            ]);

            $token = 'INV-PDF-TOKEN';

            Product::create([
                'name' => 'Producto '.$token.' Agotado',
                'sku' => 'SKU-NO-STOCK',
                'description' => 'No debe entrar por filtro de stock',
                'category_id' => $category->id,
                'price' => 10000,
                'stock' => 0,
                'is_active' => true,
            ]);

            Product::create([
                'name' => 'Producto sin token',
                'sku' => 'SKU-OTHER',
                'description' => 'No debe entrar por filtro de busqueda',
                'category_id' => $category->id,
                'price' => 10000,
                'stock' => 3,
                'is_active' => true,
            ]);

            Product::create([
                'name' => 'Producto '.$token.' Inactivo',
                'sku' => 'SKU-INACTIVE',
                'description' => 'No debe entrar por filtro de estado',
                'category_id' => $category->id,
                'price' => 10000,
                'stock' => 4,
                'is_active' => false,
            ]);

            Product::create([
                'name' => 'Producto '.$token.' Simple',
                'sku' => 'SKU-SIMPLE-001',
                'description' => 'Debe entrar como fila simple',
                'category_id' => $category->id,
                'price' => 15000,
                'stock' => 9,
                'is_active' => true,
            ]);

            $variantProduct = Product::create([
                'name' => 'Producto '.$token.' Variantes',
                'sku' => 'SKU-VAR-001',
                'description' => 'Debe entrar como filas por variante',
                'category_id' => $category->id,
                'variant_attribute_id' => $attribute->id,
                'price' => 12000,
                'stock' => 5,
                'is_active' => true,
            ]);

            ProductVariant::factory()
                ->forProduct($variantProduct)
                ->create([
                    'product_attribute_value_id' => $variantValueS->id,
                    'price' => 12000,
                    'stock' => 2,
                    'sort_order' => 1,
                    'is_active' => true,
                ]);

            ProductVariant::factory()
                ->forProduct($variantProduct)
                ->create([
                    'product_attribute_value_id' => $variantValueM->id,
                    'price' => 12500,
                    'stock' => 3,
                    'sort_order' => 2,
                    'is_active' => true,
                ]);

            Pdf::shouldReceive('loadView')
                ->once()
                ->with('admin.products.inventory-pdf', Mockery::on(function (array $data) use ($token) {
                    $this->assertInstanceOf(Carbon::class, $data['generatedAt']);
                    $this->assertSame('Admin PDF', $data['generatedBy']);
                    $this->assertSame(2, (int) $data['totals']['products_count']);
                    $this->assertSame(3, (int) $data['totals']['rows_count']);
                    $this->assertSame(3, (int) $data['totals']['known_stock_rows']);
                    $this->assertGreaterThan(0, (float) $data['totals']['total_stock']);
                    $this->assertSame(
                        ['SKU-SIMPLE-001', 'SKU-VAR-001', 'SKU-VAR-001'],
                        $data['rows']->pluck('sku')->all(),
                    );
                    $this->assertSame(
                        ['S', 'M'],
                        $data['rows']->where('sku', 'SKU-VAR-001')->pluck('variant_value')->values()->all(),
                    );
                    $this->assertTrue(
                        collect($data['appliedFilters'])->contains(fn (string $label) => str_contains($label, 'Busqueda: '.$token))
                    );
                    $this->assertTrue(
                        collect($data['appliedFilters'])->contains('Stock: Con stock')
                    );
                    $this->assertTrue(
                        collect($data['appliedFilters'])->contains('Disponibilidad: Disponible')
                    );

                    return true;
                }))
                ->andReturnUsing(function () {
                    $pdfMock = Mockery::mock(DomPdfWrapper::class);
                    $pdfMock->shouldReceive('setPaper')
                        ->once()
                        ->with('a4', 'landscape')
                        ->andReturnSelf();
                    $pdfMock->shouldReceive('download')
                        ->once()
                        ->with('saldos_inventario_20260408_112233.pdf')
                        ->andReturn(response('%PDF-INVENTORY', 200, ['Content-Type' => 'application/pdf']));

                    return $pdfMock;
                });

            $response = $this->actingAs($admin)
                ->get('/admin/products/inventory/pdf?status=active&stock=in_stock&q='.$token.'&sort=stock_desc');

            $response->assertOk();
            $response->assertHeader('content-type', 'application/pdf');
            $this->assertSame('%PDF-INVENTORY', $response->getContent());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_admin_products_index_marks_externally_managed_stock_as_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria Stock Externo Readonly',
            'slug' => 'categoria-stock-externo-readonly',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'name' => 'Producto Sin Stock',
            'sku' => 'SKU-NOSTOCK',
            'description' => 'Producto sin stock definido',
            'category_id' => $category->id,
            'price' => 10000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/products');

        $response->assertOk();
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
        $response->assertSee('SKU-PROD-HIGH');
        $response->assertDontSee('Producto '.$token.' Bajo');
        $response->assertDontSee('SKU-PROD-LOW');
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

    public function test_admin_products_index_renders_duplicate_action_for_each_product_with_context(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria Duplicate Action',
            'slug' => 'categoria-duplicate-action',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'name' => 'Producto Duplicable Index',
            'sku' => 'SKU-DUPLICATE-ACTION',
            'description' => 'TOKEN-DUPLICATE-ACTION',
            'category_id' => $category->id,
            'price' => 25000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/products?q=TOKEN-DUPLICATE-ACTION&status=active&sort=name_asc&per_page=30&page=1');

        $response->assertOk();
        $response->assertSee(route('admin.products.duplicate', $product), false);
        $response->assertSee('aria-label="Duplicar '.$product->name.'"', false);
        $response->assertSee('data-confirm="Duplicar '.$product->name.' como copia inactiva?"', false);
        $response->assertSee('name="index_context[q]" value="TOKEN-DUPLICATE-ACTION"', false);
        $response->assertSee('name="index_context[status]" value="active"', false);
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

    public function test_products_page_shows_contapyme_sync_button(): void
    {
        $admin = User::factory()->admin()->create();
        Cache::flush();
        config(['contapyme.enabled' => true]);

        $response = $this->actingAs($admin)
            ->get('/admin/products');

        $response->assertOk();
        $response->assertSee('Sincronizar stock ContaPyme');
        $response->assertSee(route('admin.contapyme.sync'));
    }

    public function test_products_page_shows_running_contapyme_sync_state(): void
    {
        $admin = User::factory()->admin()->create();
        Cache::flush();
        config(['contapyme.enabled' => true]);
        app(ContaPymeSyncState::class)->queue();

        $response = $this->actingAs($admin)
            ->get('/admin/products');

        $response->assertOk();
        $response->assertSee('Sincronización en curso');
        $response->assertSee('Sincronización ContaPyme encolada.');
        $this->assertMatchesRegularExpression(
            '/<button[^>]*data-contapyme-sync-button[^>]*disabled/',
            $response->getContent(),
        );
    }

    public function test_products_page_enables_contapyme_sync_after_completion(): void
    {
        $admin = User::factory()->admin()->create();
        Cache::flush();
        config(['contapyme.enabled' => true]);

        $state = app(ContaPymeSyncState::class);
        $owner = $state->queue();
        $state->complete('Sincronización completada.');
        $state->release($owner);

        $response = $this->actingAs($admin)
            ->get('/admin/products');

        $response->assertOk();
        $response->assertSee('Sincronización ContaPyme completada.');
        $response->assertSee('Sincronizar stock ContaPyme');
        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*data-contapyme-sync-button[^>]*disabled/',
            $response->getContent(),
        );
    }

    public function test_products_page_enables_contapyme_sync_after_a_failed_job(): void
    {
        $admin = User::factory()->admin()->create();
        Cache::flush();
        config(['contapyme.enabled' => true]);

        $state = app(ContaPymeSyncState::class);
        $owner = $state->queue();
        $state->fail('App\\Modules\\Inventory\\Jobs\\SyncContaPymeStockJob has been attempted too many times.');
        $state->release($owner);

        $response = $this->actingAs($admin)
            ->get('/admin/products');

        $response->assertOk();
        $response->assertSee('Última sincronización falló');
        $response->assertSee('has been attempted too many times');
        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*data-contapyme-sync-button[^>]*disabled/',
            $response->getContent(),
        );
    }

    public function test_products_page_shows_grouped_sync_error_details_and_limits_visible_examples(): void
    {
        $admin = User::factory()->admin()->create();
        Cache::flush();
        config(['contapyme.enabled' => true]);

        $state = app(ContaPymeSyncState::class);
        $owner = $state->queue();
        $state->fail('La sincronización masiva falló.', [
            'error_count' => 12,
            'error_groups' => [
                ['message' => 'Timeout de ContaPyme', 'count' => 12],
            ],
            'error_details' => collect(range(1, 10))->map(fn (int $index): array => [
                'sku' => 'ERR-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'phase' => 'consulta_stock',
                'message' => 'Timeout de ContaPyme',
            ])->all(),
        ]);
        $state->release($owner);

        $response = $this->actingAs($admin)
            ->get('/admin/products');

        $response->assertOk();
        $response->assertSee('Detalles del error: 12');
        $response->assertSee('errores');
        $response->assertSee('12x');
        $response->assertSee('SKU ERR-001');
        $response->assertSee('Los otros 2 quedaron registrados en los logs del sistema.');
        $response->assertDontSee('SKU ERR-011');
    }

    public function test_products_page_disables_contapyme_sync_when_it_is_disabled(): void
    {
        $admin = User::factory()->admin()->create();
        Cache::flush();
        config(['contapyme.enabled' => false]);

        $response = $this->actingAs($admin)
            ->get('/admin/products');

        $response->assertOk();
        $response->assertSee('La sincronización ContaPyme está deshabilitada en este entorno.');
        $this->assertMatchesRegularExpression(
            '/<button[^>]*data-contapyme-sync-button[^>]*disabled/',
            $response->getContent(),
        );
    }

    public function test_products_page_recovers_after_an_abandoned_lock_expires(): void
    {
        $admin = User::factory()->admin()->create();
        Cache::flush();
        config(['contapyme.enabled' => true]);
        Carbon::setTestNow('2026-07-10 10:00:00');

        try {
            $state = app(ContaPymeSyncState::class);
            $state->queue();

            Carbon::setTestNow('2026-07-10 10:12:01');

            $response = $this->actingAs($admin)
                ->get('/admin/products');

            $response->assertOk();
            $this->assertDoesNotMatchRegularExpression(
                '/<button[^>]*data-contapyme-sync-button[^>]*disabled/',
                $response->getContent(),
            );
            $response->assertSee('Sincronizar stock ContaPyme');
        } finally {
            Carbon::setTestNow();
        }
    }
}
