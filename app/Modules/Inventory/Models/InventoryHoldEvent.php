<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryHoldEvent extends Model
{
    protected $fillable = [
        'inventory_hold_id',
        'order_id',
        'user_id',
        'action',
        'previous_quantity',
        'new_quantity',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'previous_quantity' => 'decimal:2',
            'new_quantity' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<InventoryHold, $this> */
    public function hold(): BelongsTo
    {
        return $this->belongsTo(InventoryHold::class, 'inventory_hold_id');
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
