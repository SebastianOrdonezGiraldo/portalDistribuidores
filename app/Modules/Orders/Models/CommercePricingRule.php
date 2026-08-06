<?php

namespace App\Modules\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Versioned commercial pricing rule used as the source of truth for Plata markup
 * and commercial order thresholds.
 *
 * @property int $id
 * @property int $silver_markup_basis_points
 * @property int $silver_rounding_multiple
 * @property bool $silver_min_order_enabled
 * @property int $silver_min_order_amount
 * @property bool $gold_min_order_enabled
 * @property int $gold_min_order_amount
 * @property bool $gold_pricing_threshold_enabled
 * @property int $gold_pricing_threshold_amount
 * @property string $gold_pricing_threshold_basis
 * @property int|null $created_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $createdBy
 */
class CommercePricingRule extends Model
{
    protected $fillable = [
        'silver_markup_basis_points',
        'silver_rounding_multiple',
        'silver_min_order_enabled',
        'silver_min_order_amount',
        'gold_min_order_enabled',
        'gold_min_order_amount',
        'gold_pricing_threshold_enabled',
        'gold_pricing_threshold_amount',
        'gold_pricing_threshold_basis',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'silver_markup_basis_points' => 'integer',
            'silver_rounding_multiple' => 'integer',
            'silver_min_order_enabled' => 'boolean',
            'silver_min_order_amount' => 'integer',
            'gold_min_order_enabled' => 'boolean',
            'gold_min_order_amount' => 'integer',
            'gold_pricing_threshold_enabled' => 'boolean',
            'gold_pricing_threshold_amount' => 'integer',
            'created_by_id' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
