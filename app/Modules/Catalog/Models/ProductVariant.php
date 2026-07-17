<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Inventory\Models\ContaPymeInventoryMapping;
use App\Modules\Orders\Models\CartItem;
use App\Modules\Orders\Models\OrderItem;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return ProductVariantFactory::new();
    }

    protected $fillable = [
        'product_id',
        'product_attribute_value_id',
        'price',
        'stock',
        'stock_synced_at',
        'stock_sync_status',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'decimal:2',
            'stock_synced_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductAttributeValue, $this> */
    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeValue::class, 'product_attribute_value_id');
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<CartItem, $this> */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'product_variant_id');
    }

    /** @return HasOne<ContaPymeInventoryMapping, $this> */
    public function contapymeMapping(): HasOne
    {
        return $this->hasOne(ContaPymeInventoryMapping::class, 'product_variant_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
