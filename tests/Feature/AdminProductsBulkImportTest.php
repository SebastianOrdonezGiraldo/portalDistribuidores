<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminProductsBulkImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_products_import_template(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get('/admin/products/import/template');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString(
            'action;sku;name;brand;description;category_id;price;stock;is_active;is_vat_excluded',
            $response->streamedContent(),
        );
    }

    public function test_admin_can_bulk_import_products_from_csv_with_upsert_logic(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria Bulk Import',
            'slug' => 'categoria-bulk-import',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Product::create([
            'name' => 'Producto Existente',
            'brand' => 'Marca Original',
            'sku' => 'SKU-UPD-001',
            'description' => 'Original',
            'category_id' => $category->id,
            'price' => 10000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $csvContent = implode("\n", [
            'action;sku;name;brand;description;category_id;price;stock;is_active',
            "upsert;SKU-UPD-001;Producto Actualizado;Marca A;Cambio por import;{$category->id};15000;22;1",
            "upsert;SKU-NEW-100;Producto Nuevo Bulk;Marca B;Alta por import;{$category->id};25000;7;0",
            "create;;Fila Invalida Sin SKU;Marca C;Error esperado;{$category->id};5000;1;1",
            "update;SKU-NO-EXISTE;Fila Invalida Update;Marca D;Error esperado;{$category->id};5000;1;1",
        ]);

        $file = UploadedFile::fake()->createWithContent('products-import.csv', $csvContent);

        $response = $this->actingAs($admin)
            ->post('/admin/products/import', [
                'default_action' => 'upsert',
                'file' => $file,
            ]);

        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('importReport', function (array $report): bool {
            return (int) ($report['total_rows'] ?? 0) === 4
                && (int) ($report['created'] ?? 0) === 1
                && (int) ($report['updated'] ?? 0) === 1
                && count($report['errors'] ?? []) === 2;
        });

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-UPD-001',
            'name' => 'Producto Actualizado',
            'brand' => 'Marca A',
            'price' => 15000.00,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-NEW-100',
            'name' => 'Producto Nuevo Bulk',
            'brand' => 'Marca B',
            'price' => 25000.00,
            'is_active' => false,
        ]);
    }

    public function test_bulk_import_preserves_price_and_stock_for_inventree_managed_products(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria InvenTree Import',
            'slug' => 'categoria-inventree-import',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Product::create([
            'name' => 'Producto InvenTree',
            'brand' => 'Marca Original',
            'sku' => 'SKU-INV-MANAGED',
            'description' => 'Original',
            'category_id' => $category->id,
            'price' => 10000,
            'stock' => 30,
            'inventree_stock' => 40,
            'reserved_stock' => 10,
            'is_active' => true,
        ]);

        $csvContent = implode("\n", [
            'action;sku;name;brand;description;category_id;price;stock;is_active',
            "upsert;SKU-INV-MANAGED;Producto InvenTree Actualizado;Marca Nueva;Catalogo actualizado;{$category->id};99999;1;1",
        ]);

        $file = UploadedFile::fake()->createWithContent('products-import-inventree.csv', $csvContent);

        $response = $this->actingAs($admin)
            ->post('/admin/products/import', [
                'default_action' => 'upsert',
                'file' => $file,
            ]);

        $response->assertRedirect('/admin/products');

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-INV-MANAGED',
            'name' => 'Producto InvenTree Actualizado',
            'brand' => 'Marca Nueva',
            'price' => 10000,
            'stock' => 30,
            'inventree_stock' => 40,
            'reserved_stock' => 10,
        ]);
    }

    public function test_admin_can_bulk_import_vat_excluded_products_from_csv(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria IVA Excluido',
            'slug' => 'categoria-iva-excluido',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $csvContent = implode("\n", [
            'action;sku;name;brand;description;category_id;price;stock;is_active;is_vat_excluded',
            "upsert;SKU-IVA-EXC-001;Producto IVA Excluido;Marca IVA;Alta con IVA excluido;{$category->id};34000;5;1;si",
        ]);

        $file = UploadedFile::fake()->createWithContent('products-import-vat.csv', $csvContent);

        $response = $this->actingAs($admin)
            ->post('/admin/products/import', [
                'default_action' => 'upsert',
                'file' => $file,
            ]);

        $response->assertRedirect('/admin/products');
        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-IVA-EXC-001',
            'price' => 34000.00,
            'is_vat_excluded' => true,
        ]);
    }

    public function test_admin_can_bulk_import_using_category_slug_or_name(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria Slug Name',
            'slug' => 'categoria-slug-name',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $csvContent = implode("\n", [
            'action;sku;name;brand;description;category_id;price;stock;is_active',
            'upsert;SKU-SLUG-001;Producto por slug;Marca Slug;Import con slug;categoria-slug-name;15000;5;1',
            'upsert;SKU-NAME-001;Producto por nombre;Marca Name;Import con nombre;   categoria slug name   ;17000;8;1',
        ]);

        $file = UploadedFile::fake()->createWithContent('products-import-category-refs.csv', $csvContent);

        $response = $this->actingAs($admin)
            ->post('/admin/products/import', [
                'default_action' => 'upsert',
                'file' => $file,
            ]);

        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('importReport', function (array $report): bool {
            return (int) ($report['created'] ?? 0) === 2
                && (int) ($report['updated'] ?? 0) === 0
                && count($report['errors'] ?? []) === 0;
        });

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-SLUG-001',
            'brand' => 'Marca Slug',
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-NAME-001',
            'brand' => 'Marca Name',
            'category_id' => $category->id,
        ]);
    }

    public function test_bulk_import_returns_clear_message_when_category_is_missing(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria Missing Validation',
            'slug' => 'categoria-missing-validation',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $csvContent = implode("\n", [
            'action;sku;name;brand;description;category_id;price;stock;is_active',
            'upsert;SKU-MISS-CAT;Producto sin categoria;Marca X;Debe fallar;;15000;5;1',
            "upsert;SKU-VALID-CAT;Producto valido;Marca Y;Debe pasar;{$category->id};17000;8;1",
        ]);

        $file = UploadedFile::fake()->createWithContent('products-import-missing-category.csv', $csvContent);

        $response = $this->actingAs($admin)
            ->post('/admin/products/import', [
                'default_action' => 'upsert',
                'file' => $file,
            ]);

        $response->assertRedirect('/admin/products');
        $response->assertSessionHas('importReport', function (array $report): bool {
            $errors = $report['errors'] ?? [];

            return count($errors) === 1
                && str_contains((string) ($errors[0]['message'] ?? ''), 'El campo category_id es obligatorio.');
        });
    }
}
