<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class)->orderBy('value');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'variant_attribute_id');
    }
}

