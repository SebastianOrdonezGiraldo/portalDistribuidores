<?php

namespace App\Modules\AuthAccess\Models;

use App\Models\User;
use App\Modules\Company\Models\CompanyBranch;
use App\Modules\Company\Models\CompanyList;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\DistributorStatus;
use App\Modules\Shared\Enums\DistributorTier;
use Database\Factories\DistributorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property DistributorStatus $status
 * @property DistributorTier $tier
 * @property Carbon|null $tier_changed_at
 * @property int|null $tier_changed_by_id
 * @property string|null $contact_email
 * @property string|null $contact_name
 * @property Carbon|null $portal_tour_completed_at
 */
class Distributor extends Model
{
    use HasFactory;

    protected static function newFactory(): DistributorFactory
    {
        return DistributorFactory::new();
    }

    protected $fillable = [
        'name',
        'status',
        'tier',
        'nit',
        'address',
        'city',
        'phone',
        'contact_email',
        'contact_name',
        'portal_tour_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DistributorStatus::class,
            'tier' => DistributorTier::class,
            'tier_changed_at' => 'datetime',
            'portal_tour_completed_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === DistributorStatus::Active;
    }

    public function isGold(): bool
    {
        return $this->tier === DistributorTier::Gold;
    }

    public function isSilver(): bool
    {
        return $this->tier === DistributorTier::Silver;
    }

    /** @return BelongsTo<User, $this> */
    public function tierChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tier_changed_by_id');
    }

    /** @return HasOne<User, $this> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasOne<Order, $this> */
    public function latestOrder(): HasOne
    {
        return $this->hasOne(Order::class)->latestOfMany();
    }

    /** @return HasMany<CompanyBranch, $this> */
    public function branches(): HasMany
    {
        return $this->hasMany(CompanyBranch::class);
    }

    /** @return HasMany<CompanyList, $this> */
    public function lists(): HasMany
    {
        return $this->hasMany(CompanyList::class);
    }

    public function defaultBranch(): ?CompanyBranch
    {
        return $this->branches()->where('is_default', true)->first();
    }
}
