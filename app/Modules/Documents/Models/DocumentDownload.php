<?php

namespace App\Modules\Documents\Models;

use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\ProductDocument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentDownload extends Model
{
    use HasFactory;

    protected $fillable = [
        'distributor_id',
        'product_document_id',
        'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
        ];
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function productDocument(): BelongsTo
    {
        return $this->belongsTo(ProductDocument::class);
    }
}
