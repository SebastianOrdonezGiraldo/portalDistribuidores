<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\CatalogBannerPlacement;
use Illuminate\Database\Eloquent\Model;

class CatalogBanner extends Model
{
    protected $fillable = [
        'title',
        'placement',
        'path',
        'image_width',
        'image_height',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'placement' => CatalogBannerPlacement::class,
            'image_width' => 'integer',
            'image_height' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
