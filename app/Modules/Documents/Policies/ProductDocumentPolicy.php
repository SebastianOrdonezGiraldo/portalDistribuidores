<?php

namespace App\Modules\Documents\Policies;

use App\Models\User;
use App\Modules\Catalog\Models\ProductDocument;

class ProductDocumentPolicy
{
    public function download(?User $user, ProductDocument $productDocument): bool
    {
        if ($productDocument->type !== 'tech_sheet' || ! $productDocument->product?->is_active) {
            return false;
        }

        if (! $user) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isDistributor() && $user->distributor_id !== null;
    }
}
