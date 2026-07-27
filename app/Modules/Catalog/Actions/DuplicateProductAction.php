<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Catalog\Models\ProductPhoto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DuplicateProductAction
{
    /**
     * @var list<array{disk:string,path:string}>
     */
    private array $copiedFiles = [];

    public function execute(Product $product): Product
    {
        $this->copiedFiles = [];
        $product->loadMissing([
            'photos',
            'documents',
            'videos',
            'variants.attributeValue',
        ]);

        try {
            /** @var Product $duplicate */
            $duplicate = DB::transaction(function () use ($product): Product {
                $duplicate = Product::query()->create([
                    'name' => $this->duplicateName((string) $product->name),
                    'brand' => $product->brand,
                    'sku' => $this->duplicateSku((string) $product->sku),
                    'description' => $product->description,
                    'category_id' => $product->category_id,
                    'variant_attribute_id' => $product->variant_attribute_id,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'is_active' => false,
                    'is_new' => $product->is_new,
                    'new_until' => $product->new_until,
                    'is_vat_excluded' => $product->is_vat_excluded,
                ]);

                foreach ($product->variants as $variant) {
                    $duplicate->variants()->create([
                        'product_attribute_value_id' => $variant->product_attribute_value_id,
                        'price' => $variant->price,
                        'stock' => $variant->stock,
                        'is_active' => $variant->is_active,
                        'sort_order' => $variant->sort_order,
                    ]);
                }

                foreach ($product->photos as $photo) {
                    $this->duplicatePhoto($duplicate, $photo);
                }

                foreach ($product->documents as $document) {
                    $this->duplicateDocument($duplicate, $document);
                }

                foreach ($product->videos as $video) {
                    $duplicate->videos()->create([
                        'url' => $video->url,
                        'sort_order' => $video->sort_order,
                    ]);
                }

                return $duplicate->refresh();
            });
        } catch (Throwable $exception) {
            $this->deleteCopiedFiles();

            throw $exception;
        }

        $this->copiedFiles = [];

        return $duplicate;
    }

    private function duplicateName(string $name): string
    {
        $suffix = ' (Copia)';
        $base = trim($name) !== '' ? trim($name) : 'Producto';
        $base = mb_substr($base, 0, max(1, 160 - mb_strlen($suffix)));

        return $base.$suffix;
    }

    private function duplicateSku(string $sku): string
    {
        $base = trim($sku) !== '' ? trim($sku) : 'PRODUCTO';

        for ($sequence = 1; $sequence <= 9999; $sequence++) {
            $suffix = $sequence === 1 ? '-COPIA' : '-COPIA-'.$sequence;
            $candidate = mb_substr($base, 0, max(1, 80 - mb_strlen($suffix))).$suffix;

            if (! Product::query()->where('sku', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException('No fue posible generar un SKU unico para la copia.');
    }

    private function duplicatePhoto(Product $duplicate, ProductPhoto $photo): void
    {
        if (! Storage::disk('public')->exists((string) $photo->path)) {
            return;
        }

        $newPath = $this->copyFile('public', 'public', (string) $photo->path, 'products/photos', 'jpg');

        $duplicate->photos()->create([
            'path' => $newPath,
            'photo_width' => $photo->photo_width,
            'photo_height' => $photo->photo_height,
            'is_primary' => $photo->is_primary,
            'sort_order' => $photo->sort_order,
        ]);
    }

    private function duplicateDocument(Product $duplicate, ProductDocument $document): void
    {
        $targetDisk = $document->storageDisk();
        $sourceDisk = $this->resolveExistingDocumentDisk($document);

        if ($sourceDisk === null) {
            return;
        }

        $newPath = $this->copyFile($sourceDisk, $targetDisk, (string) $document->path, 'products/documents', 'pdf');

        $duplicate->documents()->create([
            'type' => $document->type,
            'path' => $newPath,
            'filename' => $document->filename,
            'sort_order' => $document->sort_order,
        ]);
    }

    private function resolveExistingDocumentDisk(ProductDocument $document): ?string
    {
        $diskName = $document->storageDisk();
        $path = (string) $document->path;

        if (Storage::disk($diskName)->exists($path)) {
            return $diskName;
        }

        if ($diskName !== 'public' && Storage::disk('public')->exists($path)) {
            return 'public';
        }

        return null;
    }

    private function copyFile(
        string $sourceDiskName,
        string $targetDiskName,
        string $sourcePath,
        string $targetDirectory,
        string $fallbackExtension,
    ): string {
        $sourceDisk = Storage::disk($sourceDiskName);

        if (! $sourceDisk->exists($sourcePath)) {
            throw new RuntimeException('No fue posible encontrar el archivo para duplicarlo.');
        }

        $targetPath = $this->newMediaPath($sourcePath, $targetDirectory, $fallbackExtension);
        $written = Storage::disk($targetDiskName)->put($targetPath, $sourceDisk->get($sourcePath));

        if (! $written) {
            throw new RuntimeException('No fue posible copiar el archivo del producto.');
        }

        $this->copiedFiles[] = [
            'disk' => $targetDiskName,
            'path' => $targetPath,
        ];

        return $targetPath;
    }

    private function newMediaPath(string $sourcePath, string $directory, string $fallbackExtension): string
    {
        $extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
        $extension = $extension !== '' ? $extension : $fallbackExtension;

        return trim($directory, '/').'/'.(string) Str::uuid().'.'.$extension;
    }

    private function deleteCopiedFiles(): void
    {
        foreach ($this->copiedFiles as $file) {
            $disk = Storage::disk($file['disk']);

            if ($disk->exists($file['path'])) {
                $disk->delete($file['path']);
            }
        }

        $this->copiedFiles = [];
    }
}
