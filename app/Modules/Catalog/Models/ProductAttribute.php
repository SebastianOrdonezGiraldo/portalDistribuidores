<?php

namespace App\Modules\Catalog\Models;

use Database\Factories\ProductAttributeFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttribute extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return ProductAttributeFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
    ];

    /** @return HasMany<ProductAttributeValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class)->orderBy('value');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'variant_attribute_id');
    }
}
