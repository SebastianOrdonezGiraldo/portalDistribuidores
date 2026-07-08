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
        'sort_order',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasMany<DocumentDownload, $this> */
    public function downloads(): HasMany
    {
        return $this->hasMany(DocumentDownload::class);
    }

    public function isTechSheet(): bool
    {
        return $this->type === DocumentType::TechSheet->value;
    }

    public function isManual(): bool
    {
        return $this->type === DocumentType::Manual->value;
    }

    public function isInvima(): bool
    {
        return $this->type === DocumentType::Invima->value;
    }

    public function isQuickGuide(): bool
    {
        return $this->type === DocumentType::QuickGuide->value;
    }

    public function isCalibrationDocument(): bool
    {
        return $this->type === DocumentType::CalibrationDocument->value;
    }

    public function isProtected(): bool
    {
        return in_array($this->type, DocumentType::protectedValues(), true);
    }

    public function shouldTrackDownloads(): bool
    {
        return $this->isTechSheet();
    }

    public function typeLabel(): string
    {
        return DocumentType::tryFrom($this->type)?->label() ?? ucfirst((string) $this->type);
    }

    public function storageDisk(): string
    {
        if ($this->isProtected()) {
            return (string) config('filesystems.tech_sheets_disk', 'private');
        }

        return 'public';
    }
}
