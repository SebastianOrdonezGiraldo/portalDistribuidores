<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'order_id',
        'user_id',
        'source',
        'previous_stock',
        'new_stock',
        'delta',
        'order_quantity',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        ?Product $product = null,
        ?ProductVariant $variant = null,
        ?float $previousStock = null,
        ?float $newStock = null,
        string $source = 'manual',
        ?Order $order = null,
        ?User $user = null,
        ?float $orderQty = null,
    ): self {
        $delta = null;

        if ($previousStock !== null && $newStock !== null) {
            $delta = round($newStock - $previousStock, 2);
        }

        return self::create([
            'product_id' => $product?->id,
            'product_variant_id' => $variant?->id,
            'order_id' => $order?->id,
            'user_id' => $user?->id ?? $order?->user_id,
            'source' => $source,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'delta' => $delta,
            'order_quantity' => $orderQty,
        ]);
    }
}
