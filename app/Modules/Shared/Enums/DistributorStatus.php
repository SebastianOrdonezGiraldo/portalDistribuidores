<?php

namespace App\Modules\Shared\Enums;

enum DistributorStatus: string
{
    case Active    = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            DistributorStatus::Active    => 'Activo',
            DistributorStatus::Suspended => 'Suspendido',
        };
    }
}
