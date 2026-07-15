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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Authenticated actor for admin and distributor workflows.
 *
 * Capability helpers below are intentionally domain-facing wrappers over role,
 * active state and distributor association. Controllers and policies should use
 * these helpers instead of duplicating role checks.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property int|null $distributor_id
 * @property bool $is_active
 * @property Carbon|null $email_verified_at
 * @property string|null $email_verification_code
 * @property Carbon|null $email_verification_code_expires_at
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
        'email_verification_code',
        'email_verification_code_expires_at',
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
            'email_verification_code_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Create and store a short-lived hashed email verification code.
     */
    public function generateEmailVerificationCode(): string
    {
        $code = (string) random_int(1000, 9999);

        $this->update([
            'email_verification_code' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(15),
        ]);

        return $code;
    }

    /**
     * Validate a plaintext verification code against the stored hash and expiry.
     */
    public function hasValidVerificationCode(string $code): bool
    {
        if ($this->email_verification_code_expires_at === null) {
            return false;
        }

        if (! $this->email_verification_code_expires_at->isFuture()) {
            return false;
        }

        if (! is_string($this->email_verification_code) || $this->email_verification_code === '') {
            return false;
        }

        return Hash::check($code, $this->email_verification_code);
    }

    /**
     * Remove any pending verification code after successful verification.
     */
    public function clearEmailVerificationCode(): void
    {
        $this->update([
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ]);
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

    /**
     * Whether this user can submit new orders from catalog/checkout.
     */
    public function canCreateOrders(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return $this->isAdmin() || $this->isDistributor();
    }

    /**
     * Whether this user can update company profile data.
     */
    public function canEditCompany(): bool
    {
        return $this->isActive()
            && $this->isDistributor()
            && $this->distributor_id !== null;
    }

    /**
     * Whether this user can create a new cart from a previous order.
     */
    public function canReorder(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return $this->isAdmin() || $this->isDistributor();
    }

    /**
     * Whether this user can manage saved company product lists.
     */
    public function canManageLists(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return $this->isAdmin() || $this->isDistributor();
    }

    /**
     * Whether this user can manage distributor delivery branches.
     */
    public function canManageBranches(): bool
    {
        return $this->isActive()
            && $this->isDistributor()
            && $this->distributor_id !== null;
    }

    /**
     * Company approval is currently disabled for every role.
     */
    public function canApproveOrders(): bool
    {
        return false;
    }

    /**
     * Orders currently go directly to submitted state without company approval.
     */
    public function orderRequiresApproval(): bool
    {
        return false;
    }
}
