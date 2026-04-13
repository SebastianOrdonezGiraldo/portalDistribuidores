<?php

namespace App\Modules\Catalog\Models;

use Database\Factories\ProductAttributeValueFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return ProductAttributeValueFactory::new();
    }

    protected $fillable = [
        'product_attribute_id',
        'value',
        'slug',
    ];

    /** @return BelongsTo<ProductAttribute, $this> */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
