<?php

namespace App\Modules\Company\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyListItem extends Model
{
    protected $table = 'company_list_items';

    protected $fillable = [
        'list_id',
        'product_id',
        'product_variant_id',
        'product_name_snapshot',
        'sku_snapshot',
        'variant_value_snapshot',
        'qty',
        'unit_label',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
        ];
    }

    /** @return BelongsTo<CompanyList, $this> */
    public function list(): BelongsTo
    {
        return $this->belongsTo(CompanyList::class);
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
