<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Categories\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckExistence;
use RuntimeException;
use Tests\TestCase;

class MigrateProtectedMediaCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_treats_missing_r2_object_as_missing_instead_of_failing(): void
    {
        config([
            'filesystems.tech_sheets_disk' => 'private',
            'filesystems.order_pdfs_disk' => 'private',
        ]);

        $category = Category::create([
            'name' => 'Proteccion',
            'slug' => 'proteccion',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'name' => 'Producto A',
            'sku' => 'TS-001',
            'description' => 'desc',
            'category_id' => $category->id,
            'price' => 1000,
            'is_active' => true,
        ]);

        ProductDocument::create([
            'product_id' => $product->id,
            'type' => 'tech_sheet',
            'path' => 'products/documents/missing.pdf',
            'filename' => 'missing.pdf',
        ]);

        $targetDisk = new class
        {
            public function exists(string $path): bool
            {
                throw UnableToCheckExistence::forLocation(
                    $path,
                    new RuntimeException('NoSuchKey: The specified key does not exist. 404 Not Found'),
                );
            }

            public function put(string $path, string $contents): bool
            {
                throw new RuntimeException('put should not be called');
            }

            public function get(string $path): string
            {
                throw new RuntimeException('get should not be called');
            }

            public function delete(string $path): bool
            {
                throw new RuntimeException('delete should not be called');
            }
        };

        $legacyDisk = new class
        {
            public function exists(string $path): bool
            {
                return false;
            }

            public function put(string $path, string $contents): bool
            {
                throw new RuntimeException('put should not be called');
            }

            public function get(string $path): string
            {
                throw new RuntimeException('get should not be called');
            }

            public function delete(string $path): bool
            {
                throw new RuntimeException('delete should not be called');
            }
        };

        Storage::shouldReceive('disk')->with('private')->andReturn($targetDisk);
        Storage::shouldReceive('disk')->with('public')->andReturn($legacyDisk);

        $this->artisan('protected-media:migrate')
            ->expectsOutputToContain('Migracion de media protegida finalizada.')
            ->assertExitCode(0);
    }
}
