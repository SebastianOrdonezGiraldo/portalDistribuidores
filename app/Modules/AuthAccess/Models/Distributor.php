<?php

namespace App\Modules\AuthAccess\Models;

use App\Models\User;
use App\Modules\Company\Models\CompanyBranch;
use App\Modules\Company\Models\CompanyList;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\DistributorStatus;
use Database\Factories\DistributorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'nit',
        'address',
        'city',
        'phone',
        'contact_email',
        'contact_name',
    ];

    protected function casts(): array
    {
        return [
            'status' => DistributorStatus::class,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === DistributorStatus::Active;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(CompanyBranch::class);
    }

    public function lists(): HasMany
    {
        return $this->hasMany(CompanyList::class);
    }

    public function defaultBranch(): ?CompanyBranch
    {
        return $this->branches()->where('is_default', true)->first();
    }
}
