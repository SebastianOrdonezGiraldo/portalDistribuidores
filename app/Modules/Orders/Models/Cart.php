<?php

namespace App\Modules\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Persistent shopping cart owned by one distributor account.
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon|null $reminder_sent_at
 * @property Carbon $updated_at
 */
class Cart extends Model
{
    public const EXPIRES_AFTER_HOURS = 48;

    public const REMINDER_AFTER_HOURS = 36;

    protected $fillable = [
        'user_id',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'reminder_sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<CartItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function hasExpired(): bool
    {
        return $this->updated_at->lte(now()->subHours(self::EXPIRES_AFTER_HOURS));
    }
}
