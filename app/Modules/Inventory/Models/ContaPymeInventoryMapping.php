<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaPymeInventoryMapping extends Model
{
    protected $table = 'contapyme_inventory_mappings';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'irecurso',
        'status',
        'last_validated_at',
        'last_seen_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'last_validated_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
