<?php

namespace App\Modules\Orders\Pricing;

use App\Models\User;
use App\Modules\Shared\Enums\DistributorTier;

/**
 * Resolves the commercial tier that applies to a given actor.
 *
 * When there is no associated distributor company (guests and admins without a
 * distributor), the Silver tier applies. Gold is never assumed as a fallback.
 */
final class DistributorTierResolver
{
    public function resolve(?User $user): DistributorTier
    {
        return $user?->distributor?->tier ?? DistributorTier::Silver;
    }
}
