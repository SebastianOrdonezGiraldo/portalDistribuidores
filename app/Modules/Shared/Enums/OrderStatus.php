<?php

namespace App\Modules\Shared\Enums;

enum OrderStatus: string
{
    case Draft            = 'draft';
    case PendingApproval  = 'pending_approval';
    case Submitted        = 'submitted';
    case Sending          = 'sending';
    case Sent             = 'sent';
    case Failed           = 'failed';
    case Rejected         = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft           => 'Borrador',
            self::PendingApproval => 'En revisión',
            self::Submitted       => 'Enviado',
            self::Sending         => 'En proceso',
            self::Sent            => 'Completado',
            self::Failed          => 'Fallido',
            self::Rejected        => 'Rechazado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft           => 'bg-amber-400',
            self::PendingApproval => 'bg-violet-400',
            self::Submitted       => 'bg-emerald-400',
            self::Sending         => 'bg-sky-400',
            self::Sent            => 'bg-blue-400',
            self::Failed          => 'bg-rose-400',
            self::Rejected        => 'bg-red-500',
        };
    }

    public function isPendingReview(): bool
    {
        return $this === self::PendingApproval;
    }

    public function isRejected(): bool
    {
        return $this === self::Rejected;
    }

    public function canBeApproved(): bool
    {
        return $this === self::PendingApproval;
    }
}
