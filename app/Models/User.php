<?php

namespace App\Models;

use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\CompanyRole;
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
 * @property CompanyRole|null $company_role
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
        'company_role',
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
            'company_role' => CompanyRole::class,
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

    public function companyRole(): ?CompanyRole
    {
        return $this->company_role;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isCompanyAdmin(): bool
    {
        return $this->isDistributor() && $this->company_role === CompanyRole::AdminEmpresa;
    }

    public function canCreateOrders(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isDistributor()) {
            return false;
        }

        if ($this->company_role === null) {
            return true;
        }

        return $this->company_role->canCreateOrders();
    }

    public function canEditCompany(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return $this->isDistributor()
            && $this->company_role !== null
            && $this->company_role->canEditCompany();
    }

    public function canReorder(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isDistributor()) {
            return false;
        }

        // Legacy users without company_role can reorder
        if ($this->company_role === null) {
            return true;
        }

        return $this->company_role->canReorder();
    }

    public function canManageLists(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isDistributor()) {
            return false;
        }

        if ($this->company_role === null) {
            return true;
        }

        return $this->company_role->canManageLists();
    }

    public function canManageBranches(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return $this->isDistributor()
            && $this->company_role !== null
            && $this->company_role->canManageBranches();
    }

    public function canApproveOrders(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return $this->isDistributor()
            && $this->company_role !== null
            && $this->company_role->canApproveOrders();
    }

    public function orderRequiresApproval(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if (! $this->isDistributor()) {
            return false;
        }

        return $this->company_role?->requiresOrderApproval() ?? false;
    }
}
