<?php

namespace App\Modules\Orders\Models;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    public const OC_PREFIX  = 'CTC-';
    public const OC_PADDING = 6;

    protected $fillable = [
        'distributor_id',
        'user_id',
        'oc_number',
        'contact_name',
        'contact_email',
        'company_name',
        'company_nit',
        'company_address',
        'city',
        'phone',
        'notes',
        'approval_note',
        'status',
        'total_amount',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_amount' => 'decimal:2',
        ];
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('created_at');
    }
}
