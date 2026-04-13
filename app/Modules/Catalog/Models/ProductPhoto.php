<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Shared\Support\PublicMediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProductPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'path',
        'photo_width',
        'photo_height',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'photo_width' => 'integer',
            'photo_height' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return array{width:int,height:int}
     */
    public function resolvedDimensions(): array
    {
        $width = (int) ($this->photo_width ?? 0);
        $height = (int) ($this->photo_height ?? 0);

        if ($width > 0 && $height > 0) {
            return ['width' => $width, 'height' => $height];
        }

        $detected = $this->detectDimensionsFromStorage();

        if ($detected !== null) {
            if ($this->exists) {
                $this->forceFill([
                    'photo_width' => $detected['width'],
                    'photo_height' => $detected['height'],
                ])->saveQuietly();
            }

            return $detected;
        }

        // Fallback safe ratio to keep reserved space if metadata cannot be resolved.
        return ['width' => 1200, 'height' => 1200];
    }

    public function responsiveSrcsetFromKnownVariants(): ?string
    {
        $path = ltrim(trim((string) $this->path), '/');

        if ($path === '') {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'svg') {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $directory = pathinfo($path, PATHINFO_DIRNAME);
        $directory = $directory === '.' ? '' : $directory;
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $basePrefix = $directory === '' ? '' : $directory.'/';

        $variants = [];

        foreach ([320, 480, 640, 768, 960, 1200, 1600] as $width) {
            $candidatePaths = [
                "{$basePrefix}{$filename}-{$width}w.{$extension}",
                "{$basePrefix}{$filename}_{$width}.{$extension}",
            ];

            foreach ($candidatePaths as $candidatePath) {
                if (! $disk->exists($candidatePath)) {
                    continue;
                }

                $variants[$width] = PublicMediaUrl::fromPublicDisk($candidatePath);
                break;
            }
        }

        $intrinsic = $this->resolvedDimensions();
        $originalWidth = (int) ($intrinsic['width'] ?? 0);

        if ($originalWidth > 0) {
            $variants[$originalWidth] = PublicMediaUrl::fromPublicDisk($path);
        }

        if (count($variants) < 2) {
            return null;
        }

        ksort($variants);

        $entries = [];

        foreach ($variants as $width => $url) {
            $entries[] = "{$url} {$width}w";
        }

        return implode(', ', $entries);
    }

    /**
     * @return array{width:int,height:int}|null
     */
    private function detectDimensionsFromStorage(): ?array
    {
        $path = ltrim(trim((string) $this->path), '/');

        if ($path === '') {
            return null;
        }

        $cacheKey = 'catalog.photo_dimensions.'.sha1($path);

        return Cache::remember($cacheKey, now()->addDays(7), static function () use ($path): ?array {
            $disk = Storage::disk('public');

            if (! $disk->exists($path)) {
                return null;
            }

            $bytes = $disk->get($path);

            if ($bytes === '') {
                return null;
            }

            $size = @getimagesizefromstring($bytes);

            if (is_array($size) && isset($size[0], $size[1]) && $size[0] > 0 && $size[1] > 0) {
                return [
                    'width' => (int) $size[0],
                    'height' => (int) $size[1],
                ];
            }

            return self::parseSvgDimensions($bytes);
        });
    }

    /**
     * @return array{width:int,height:int}|null
     */
    private static function parseSvgDimensions(string $svg): ?array
    {
        if (! str_contains(strtolower($svg), '<svg')) {
            return null;
        }

        if (preg_match('/viewBox\s*=\s*["\'][^"\']*\s([\d.]+)\s([\d.]+)["\']/i', $svg, $matches) === 1) {
            $width = (int) round((float) $matches[1]);
            $height = (int) round((float) $matches[2]);

            if ($width > 0 && $height > 0) {
                return ['width' => $width, 'height' => $height];
            }
        }

        if (
            preg_match('/\bwidth\s*=\s*["\']([\d.]+)(?:px)?["\']/i', $svg, $widthMatch) === 1
            && preg_match('/\bheight\s*=\s*["\']([\d.]+)(?:px)?["\']/i', $svg, $heightMatch) === 1
        ) {
            $width = (int) round((float) $widthMatch[1]);
            $height = (int) round((float) $heightMatch[1]);

            if ($width > 0 && $height > 0) {
                return ['width' => $width, 'height' => $height];
            }
        }

        return null;
    }
}
