<?php

namespace App\Modules\Company\Models;

use App\Modules\AuthAccess\Models\Distributor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBranch extends Model
{
    protected $table = 'company_branches';

    protected $fillable = [
        'distributor_id',
        'name',
        'address',
        'city',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /** @return BelongsTo<Distributor, $this> */
    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function fullAddress(): string
    {
        return implode(', ', array_filter([$this->address, $this->city]));
    }
}
