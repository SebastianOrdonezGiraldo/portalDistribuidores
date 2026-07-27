<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Categories\Models\Category;
use App\Modules\Inventory\Models\ContaPymeInventoryMapping;
use App\Modules\Orders\Models\CartItem;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Support\TextNormalizer;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property string|null $brand
 * @property string $sku
 * @property bool $is_active
 * @property bool $is_new
 * @property bool $is_vat_excluded
 * @property CarbonInterface|null $new_until
 * @property int|null $category_id
 * @property int|null $variant_attribute_id
 * @property int $active_variants_count
 * @property CarbonInterface|null $stock_synced_at
 * @property string|null $stock_sync_status
 */
class Product extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return ProductFactory::new();
    }

    protected $fillable = [
        'name',
        'brand',
        'sku',
        'description',
        'category_id',
        'variant_attribute_id',
        'price',
        'stock',
        'stock_synced_at',
        'stock_sync_status',
        'is_active',
        'is_new',
        'new_until',
        'is_vat_excluded',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_new' => 'boolean',
            'new_until' => 'date',
            'is_vat_excluded' => 'boolean',
            'price' => 'decimal:2',
            'stock' => 'decimal:2',
            'stock_synced_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<ProductAttribute, $this> */
    public function variantAttribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'variant_attribute_id');
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<ProductPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(ProductPhoto::class)->orderBy('sort_order');
    }

    /** @return HasOne<ProductPhoto, $this> */
    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(ProductPhoto::class)->where('is_primary', true);
    }

    /** @return HasMany<ProductVideo, $this> */
    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class)->orderBy('sort_order');
    }

    /** @return HasMany<ProductDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<CartItem, $this> */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAdminSearch(Builder $query, ?string $term): Builder
    {
        $tokens = TextNormalizer::tokenize($term);

        if ($tokens === []) {
            return $query;
        }

        $searchableColumns = [
            $this->qualifyColumn('name'),
            $this->qualifyColumn('brand'),
            $this->qualifyColumn('sku'),
            $this->qualifyColumn('description'),
        ];

        foreach ($tokens as $token) {
            $query->where(function (Builder $subQuery) use ($searchableColumns, $token): void {
                $like = '%'.$token.'%';

                foreach ($searchableColumns as $column) {
                    $subQuery->orWhereRaw($this->accentInsensitiveExpr($column).' LIKE ?', [$like]);
                }
            });
        }

        return $query;
    }

    public function hasConfigurableVariants(): bool
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->where('is_active', true)->isNotEmpty();
        }

        return $this->variants()->where('is_active', true)->exists();
    }

    public function isStockManagedByContaPyme(): bool
    {
        return $this->stock_sync_status === 'synced' && $this->stock_synced_at !== null;
    }

    public function isCurrentlyNew(?CarbonInterface $at = null): bool
    {
        if (! $this->is_new) {
            return false;
        }

        if ($this->new_until === null) {
            return true;
        }

        return $this->new_until->format('Y-m-d') >= $this->localDate($at)->format('Y-m-d');
    }

    public function isNewExpired(?CarbonInterface $at = null): bool
    {
        return $this->is_new
            && $this->new_until !== null
            && ! $this->isCurrentlyNew($at);
    }

    public function newStatusText(?CarbonInterface $at = null): string
    {
        if (! $this->is_new) {
            return 'No marcado como nuevo.';
        }

        if ($this->new_until === null) {
            return 'Nuevo activo sin vencimiento.';
        }

        $formattedDate = $this->new_until->format('d/m/Y');

        if ($this->isNewExpired($at)) {
            return "Etiqueta vencida desde {$formattedDate}.";
        }

        return "Nuevo activo hasta {$formattedDate}.";
    }

    /** @return HasOne<ContaPymeInventoryMapping, $this> */
    public function contapymeMapping(): HasOne
    {
        return $this->hasOne(ContaPymeInventoryMapping::class);
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    public function activeVariantsCollection(): Collection
    {
        if ($this->relationLoaded('variants')) {
            /** @var Collection<int, ProductVariant> $variants */
            $variants = $this->variants->where('is_active', true)->values();

            return $variants;
        }

        /** @var Collection<int, ProductVariant> $variants */
        $variants = $this->variants()->where('is_active', true)->get();

        return $variants;
    }

    private function accentInsensitiveExpr(string $column): string
    {
        $expression = "coalesce({$column}, '')";

        if (DB::getDriverName() === 'pgsql') {
            return "unaccent(lower({$expression}))";
        }

        $replacements = [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ];

        foreach ($replacements as $search => $replace) {
            $expression = "replace({$expression}, '{$search}', '{$replace}')";
        }

        return "lower({$expression})";
    }

    private function localDate(?CarbonInterface $at = null): CarbonImmutable
    {
        $timezone = (string) config('app.timezone', 'America/Bogota');

        return $at === null
            ? CarbonImmutable::now($timezone)
            : CarbonImmutable::instance($at)->setTimezone($timezone);
    }
}
