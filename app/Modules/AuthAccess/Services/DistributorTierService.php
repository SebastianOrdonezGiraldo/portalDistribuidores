<?php

namespace App\Modules\AuthAccess\Services;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Support\Facades\DB;

/**
 * Applies manual, admin-driven tier changes to a distributor company.
 *
 * The tier is never assigned automatically; it only changes here and stays in
 * effect until an administrator changes it again. Changing to the same tier is a
 * no-op so audit fields are not overwritten needlessly.
 */
class DistributorTierService
{
    public function changeTier(
        Distributor $distributor,
        DistributorTier $tier,
        User $changedBy,
    ): Distributor {
        if ($distributor->tier === $tier) {
            return $distributor;
        }

        return DB::transaction(function () use ($distributor, $tier, $changedBy): Distributor {
            $distributor->forceFill([
                'tier' => $tier,
                'tier_changed_at' => now(),
                'tier_changed_by_id' => $changedBy->id,
            ])->save();

            return $distributor->refresh();
        });
    }
}
