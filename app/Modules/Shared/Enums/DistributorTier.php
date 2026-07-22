<?php

namespace App\Modules\Shared\Enums;

enum DistributorTier: string
{
    case Silver = 'plata';
    case Gold = 'oro';

    public function label(): string
    {
        return match ($this) {
            DistributorTier::Silver => 'ICM Plata',
            DistributorTier::Gold => 'ICM Oro',
        };
    }
}
