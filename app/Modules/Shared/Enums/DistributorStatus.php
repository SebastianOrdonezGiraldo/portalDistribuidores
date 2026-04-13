<?php

namespace App\Modules\Shared\Enums;

enum DistributorStatus: string
{
    case Active = 'active';
    case PendingReview = 'pending_review';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            DistributorStatus::Active => 'Activo',
            DistributorStatus::PendingReview => 'Pendiente de revisión',
            DistributorStatus::Rejected => 'Rechazado',
            DistributorStatus::Suspended => 'Suspendido',
        };
    }
}
