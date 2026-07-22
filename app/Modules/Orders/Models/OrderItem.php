<?php

namespace App\Modules\Orders\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $qty
 * @property float $price_each
 * @property float $subtotal
 * @property float|null $base_unit_price
 * @property float|null $silver_unit_price
 * @property float|null $unit_savings
 * @property float|null $line_savings
 */
class OrderItem extends Model
{
    use HasFactory;

    protected static function newFactory(): OrderItemFactory
    {
        return OrderItemFactory::new();
    }

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name_snapshot',
        'sku_snapshot',
        'variant_attribute_snapshot',
        'variant_value_snapshot',
        'qty',
        'unit_label',
        'price_each',
        'base_unit_price',
        'silver_unit_price',
        'unit_savings',
        'subtotal',
        'line_savings',
        'is_vat_excluded_snapshot',
        'vat_rate_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'price_each' => 'decimal:2',
            'base_unit_price' => 'decimal:2',
            'silver_unit_price' => 'decimal:2',
            'unit_savings' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'line_savings' => 'decimal:2',
            'is_vat_excluded_snapshot' => 'boolean',
            'vat_rate_snapshot' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
