<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use App\Modules\Shared\Support\PublicMediaUrl;

class ProductPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'path',
        'width',
        'height',
        'variants',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'width' => 'integer',
            'height' => 'integer',
            'variants' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function publicUrl(): string
    {
        return PublicMediaUrl::fromPublicDisk($this->path);
    }

    public function intrinsicWidth(int $fallback = 1080): int
    {
        return max(1, (int) ($this->width ?: $fallback));
    }

    public function intrinsicHeight(int $fallback = 1080): int
    {
        return max(1, (int) ($this->height ?: $fallback));
    }

    /**
     * @return Collection<int, array{path: string, width: int, height: int}>
     */
    public function webpVariants(): Collection
    {
        $variants = data_get($this->variants, 'webp', []);

        return collect(is_array($variants) ? $variants : [])
            ->filter(fn ($variant) => is_array($variant) && filled($variant['path'] ?? null))
            ->map(fn ($variant) => [
                'path' => (string) $variant['path'],
                'width' => max(1, (int) ($variant['width'] ?? $this->intrinsicWidth())),
                'height' => max(1, (int) ($variant['height'] ?? $this->intrinsicHeight())),
            ])
            ->sortBy('width')
            ->values();
    }

    public function webpSrcSet(): string
    {
        return $this->webpVariants()
            ->map(fn (array $variant) => PublicMediaUrl::fromPublicDisk($variant['path']).' '.$variant['width'].'w')
            ->implode(', ');
    }
}
