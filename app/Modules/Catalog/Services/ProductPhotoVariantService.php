<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\ProductPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class ProductPhotoVariantService
{
    /**
     * @return array{width: int|null, height: int|null}
     */
    public function extractDimensions(string $localPath): array
    {
        if ($localPath === '' || ! is_file($localPath)) {
            return ['width' => null, 'height' => null];
        }

        $dimensions = @getimagesize($localPath);

        if (! is_array($dimensions)) {
            return ['width' => null, 'height' => null];
        }

        return [
            'width' => isset($dimensions[0]) ? (int) $dimensions[0] : null,
            'height' => isset($dimensions[1]) ? (int) $dimensions[1] : null,
        ];
    }

    /**
     * @param  array<string, UploadedFile>  $generatedFiles
     * @param  array<string, mixed>  $manifestEntry
     * @return array<string, array<int, array{path: string, width: int, height: int}>>
     */
    public function persistClientGeneratedVariants(string $originalPath, array $manifestEntry, array $generatedFiles): array
    {
        $variants = collect(data_get($manifestEntry, 'variants', []))
            ->filter(fn ($variant) => is_array($variant) && filled($variant['file'] ?? null))
            ->values();

        if ($variants->isEmpty()) {
            return [];
        }

        $disk = Storage::disk('public');
        $stored = [];

        foreach ($variants as $variant) {
            $fileName = (string) $variant['file'];
            $uploadedFile = $generatedFiles[$fileName] ?? null;

            if (! $uploadedFile instanceof UploadedFile) {
                continue;
            }

            $width = max(1, (int) ($variant['width'] ?? 0));
            $height = max(1, (int) ($variant['height'] ?? 0));
            $variantPath = $this->variantStoragePath($originalPath, $width);

            $stream = fopen($uploadedFile->getRealPath(), 'r');
            if ($stream === false) {
                continue;
            }

            $disk->put($variantPath, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            $stored[] = [
                'path' => $variantPath,
                'width' => $width,
                'height' => $height,
            ];
        }

        return $stored === [] ? [] : ['webp' => $stored];
    }

    /**
     * Best-effort server-side fallback for environments where Node + sharp are available.
     *
     * @return array<string, array<int, array{path: string, width: int, height: int}>>
     */
    public function maybeGenerateFromUpload(UploadedFile $file, string $originalPath): array
    {
        $realPath = $file->getRealPath();

        if (! is_string($realPath) || $realPath === '' || ! $this->nodeGeneratorAvailable()) {
            return [];
        }

        return $this->generateAndPersistFromLocalPath($realPath, $originalPath);
    }

    public function backfillPhoto(ProductPhoto $photo, bool $force = false): bool
    {
        if (! $force && $photo->width && $photo->height && $photo->webpVariants()->isNotEmpty()) {
            return false;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($photo->path)) {
            return false;
        }

        $tempSource = tempnam(sys_get_temp_dir(), 'photo-src-');
        if ($tempSource === false) {
            return false;
        }

        try {
            file_put_contents($tempSource, (string) $disk->get($photo->path));

            $dimensions = $this->extractDimensions($tempSource);
            $variants = $this->generateAndPersistFromLocalPath($tempSource, $photo->path);

            $photo->update([
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'variants' => $variants !== [] ? $variants : $photo->variants,
            ]);

            return true;
        } finally {
            @unlink($tempSource);
        }
    }

    public function deleteVariants(ProductPhoto $photo): void
    {
        $disk = Storage::disk('public');

        foreach ($photo->webpVariants() as $variant) {
            if ($disk->exists($variant['path'])) {
                $disk->delete($variant['path']);
            }
        }
    }

    public function nodeGeneratorAvailable(): bool
    {
        return is_file(base_path('scripts/build-product-photo-variants.mjs'))
            && is_file(base_path('node_modules/sharp/package.json'));
    }

    /**
     * @return array<string, array<int, array{path: string, width: int, height: int}>>
     */
    private function generateAndPersistFromLocalPath(string $localPath, string $originalPath): array
    {
        if (! $this->nodeGeneratorAvailable()) {
            return [];
        }

        $outputDirectory = $this->makeTempDirectory();
        if ($outputDirectory === null) {
            return [];
        }

        try {
            $process = new Process([
                'node',
                base_path('scripts/build-product-photo-variants.mjs'),
                '--input',
                $localPath,
                '--output-dir',
                $outputDirectory,
                '--basename',
                pathinfo($originalPath, PATHINFO_FILENAME),
            ], base_path());

            $process->mustRun();

            $payload = json_decode($process->getOutput(), true);
            if (! is_array($payload)) {
                return [];
            }

            $generatedVariants = collect(data_get($payload, 'variants', []))
                ->filter(fn ($variant) => is_array($variant) && filled($variant['file'] ?? null))
                ->values();

            if ($generatedVariants->isEmpty()) {
                return [];
            }

            $disk = Storage::disk('public');
            $storedVariants = [];

            foreach ($generatedVariants as $variant) {
                $localVariant = $outputDirectory.DIRECTORY_SEPARATOR.$variant['file'];
                if (! is_file($localVariant)) {
                    continue;
                }

                $width = max(1, (int) ($variant['width'] ?? 0));
                $height = max(1, (int) ($variant['height'] ?? 0));
                $variantPath = $this->variantStoragePath($originalPath, $width);
                $stream = fopen($localVariant, 'r');

                if ($stream === false) {
                    continue;
                }

                $disk->put($variantPath, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }

                $storedVariants[] = [
                    'path' => $variantPath,
                    'width' => $width,
                    'height' => $height,
                ];
            }

            return $storedVariants === [] ? [] : ['webp' => $storedVariants];
        } catch (Throwable) {
            return [];
        } finally {
            $this->deleteDirectory($outputDirectory);
        }
    }

    private function variantStoragePath(string $originalPath, int $width): string
    {
        $directory = pathinfo($originalPath, PATHINFO_DIRNAME);
        $directory = $directory === '.' ? '' : $directory;
        $baseName = pathinfo($originalPath, PATHINFO_FILENAME);
        $prefix = $directory !== '' ? $directory.'/' : '';

        return $prefix.'variants/'.$baseName.'-'.$width.'.webp';
    }

    private function makeTempDirectory(): ?string
    {
        $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'photo-variants-'.bin2hex(random_bytes(5));

        return mkdir($base, 0777, true) ? $base : null;
    }

    private function deleteDirectory(string $path): void
    {
        if ($path === '' || ! is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if (! is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }

            $itemPath = $path.DIRECTORY_SEPARATOR.$item;

            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
