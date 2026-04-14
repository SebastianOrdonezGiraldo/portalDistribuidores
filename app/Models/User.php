<?php

namespace App\Models;

use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property int|null $distributor_id
 * @property bool $is_active
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'distributor_id',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /** @return BelongsTo<Distributor, $this> */
    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isDistributor(): bool
    {
        return $this->role === UserRole::Distributor;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function canCreateOrders(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return $this->isAdmin() || $this->isDistributor();
    }

    public function canEditCompany(): bool
    {
        return $this->isActive()
            && $this->isDistributor()
            && $this->distributor_id !== null;
    }

    public function canReorder(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return $this->isAdmin() || $this->isDistributor();
    }

    public function canManageLists(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return $this->isAdmin() || $this->isDistributor();
    }

    public function canManageBranches(): bool
    {
        return $this->isActive()
            && $this->isDistributor()
            && $this->distributor_id !== null;
    }

    public function canApproveOrders(): bool
    {
        return false;
    }

    public function orderRequiresApproval(): bool
    {
        return false;
    }
}
