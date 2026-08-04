<?php

namespace App\Modules\Orders\Models;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Enums\PaymentMethod;
use App\Modules\Shared\Enums\PaymentStatus;
use App\Modules\Shared\Enums\ShippingCarrier;
use Carbon\Carbon;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property OrderStatus $status
 * @property PaymentStatus $payment_status
 * @property PaymentMethod|null $payment_method
 * @property string|null $payment_receipt_path
 * @property string|null $payment_receipt_filename
 * @property Carbon|null $payment_receipt_uploaded_at
 * @property Carbon|null $payment_reservation_expires_at
 * @property DistributorTier|null $distributor_tier_snapshot
 * @property float $total_amount
 * @property string|null $pdf_path
 * @property string|null $oc_number
 * @property string|null $tracking_number
 * @property string|null $shipping_carrier
 * @property Carbon $created_at
 * @property float|null $revenue
 * @property int|null $orders
 * @property float|null $total
 */
class Order extends Model
{
    use HasFactory;

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    public const OC_PREFIX = 'CTC-';

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
        'department',
        'phone',
        'notes',
        'approval_note',
        'status',
        'payment_status',
        'payment_method',
        'payment_receipt_path',
        'payment_receipt_filename',
        'payment_receipt_uploaded_at',
        'payment_reservation_expires_at',
        'distributor_tier_snapshot',
        'commerce_pricing_rule_id',
        'minimum_order_tier_snapshot',
        'minimum_order_enabled_snapshot',
        'minimum_order_amount_snapshot',
        'minimum_order_evaluated_amount',
        'minimum_order_reached',
        'minimum_order_decision_reason_snapshot',
        'gold_pricing_threshold_enabled_snapshot',
        'gold_pricing_threshold_amount_snapshot',
        'gold_pricing_threshold_basis_snapshot',
        'gold_pricing_decision_reason_snapshot',
        'gold_pricing_applied',
        'silver_candidate_total',
        'gold_candidate_total',
        'gold_savings_total',
        'tracking_number',
        'shipping_carrier',
        'total_amount',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_receipt_uploaded_at' => 'datetime',
            'payment_reservation_expires_at' => 'datetime',
            'distributor_tier_snapshot' => DistributorTier::class,
            'minimum_order_enabled_snapshot' => 'boolean',
            'minimum_order_amount_snapshot' => 'integer',
            'minimum_order_evaluated_amount' => 'decimal:2',
            'minimum_order_reached' => 'boolean',
            'gold_pricing_threshold_enabled_snapshot' => 'boolean',
            'gold_pricing_threshold_amount_snapshot' => 'integer',
            'gold_pricing_applied' => 'boolean',
            'silver_candidate_total' => 'decimal:2',
            'gold_candidate_total' => 'decimal:2',
            'gold_savings_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function requiresManualPayment(): bool
    {
        return $this->payment_status !== PaymentStatus::NotApplicable
            && $this->payment_method !== null;
    }

    public function canDispatchRegardingPayment(): bool
    {
        return ! $this->payment_status->blocksDispatchUnlessOk();
    }

    public function shippingCarrierLabel(): ?string
    {
        if (! filled($this->shipping_carrier)) {
            return null;
        }

        return ShippingCarrier::tryFrom($this->shipping_carrier)?->label() ?? $this->shipping_carrier;
    }

    /** @return BelongsTo<CommercePricingRule, $this> */
    public function commercePricingRule(): BelongsTo
    {
        return $this->belongsTo(CommercePricingRule::class);
    }

    /** @return BelongsTo<Distributor, $this> */
    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<OrderStatusHistory, $this> */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('created_at');
    }

    /** @return HasMany<PaymentUploadToken, $this> */
    public function paymentUploadTokens(): HasMany
    {
        return $this->hasMany(PaymentUploadToken::class);
    }
}
