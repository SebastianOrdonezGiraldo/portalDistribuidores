<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Documents\Models\DocumentDownload;
use App\Modules\Shared\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'path',
        'filename',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(DocumentDownload::class);
    }

    public function isTechSheet(): bool
    {
        return $this->type === DocumentType::TechSheet->value;
    }

    public function storageDisk(): string
    {
        if ($this->isTechSheet()) {
            return (string) config('filesystems.tech_sheets_disk', 'private');
        }

        return 'public';
    }
}
