<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogBanner extends Model
{
    protected $fillable = [
        'title',
        'path',
        'image_width',
        'image_height',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'image_width' => 'integer',
            'image_height' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
