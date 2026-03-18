<?php

namespace App\Modules\Company\Models;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyList extends Model
{
    protected $table = 'company_lists';

    protected $fillable = [
        'distributor_id',
        'created_by',
        'name',
    ];

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompanyListItem::class, 'list_id');
    }
}
