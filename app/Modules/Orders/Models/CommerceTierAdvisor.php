<?php

namespace App\Modules\Orders\Models;

use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Database\Eloquent\Model;

/**
 * Current commercial advisor assigned to a distributor tier.
 *
 * Order history never points back to this model; orders store nullable snapshots.
 */
class CommerceTierAdvisor extends Model
{
    protected $fillable = [
        'tier',
        'advisor_name',
        'advisor_email',
        'advisor_whatsapp',
    ];

    protected function casts(): array
    {
        return [
            'tier' => DistributorTier::class,
        ];
    }
}
