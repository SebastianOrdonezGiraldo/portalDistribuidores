<?php

namespace App\Modules\Shared\Enums;

enum OrderStatus: string
{
    case Draft            = 'draft';
    case PendingApproval  = 'pending_approval';
    case Submitted        = 'submitted';
    case Sold             = 'sold';
    case Dispatched       = 'dispatched';
    case Delivered        = 'delivered';
    case Cancelled        = 'cancelled';
    // Legacy states kept for backward compatibility with historical data.
    case Sending          = 'sending';
    case Sent             = 'sent';
    case Failed           = 'failed';
    case Rejected         = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft           => 'Borrador',
            self::PendingApproval => 'En revisión',
            self::Submitted       => 'Registrado',
            self::Sold            => 'Vendido',
            self::Dispatched      => 'Despachado',
            self::Delivered       => 'Entregado',
            self::Cancelled       => 'Cancelado',
            self::Sending         => 'En proceso (legado)',
            self::Sent            => 'Completado (legado)',
            self::Failed          => 'Fallido (legado)',
            self::Rejected        => 'Rechazado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft           => 'bg-amber-400',
            self::PendingApproval => 'bg-violet-400',
            self::Submitted       => 'bg-emerald-400',
            self::Sold            => 'bg-cyan-500',
            self::Dispatched      => 'bg-sky-500',
            self::Delivered       => 'bg-blue-500',
            self::Cancelled       => 'bg-slate-500',
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

    public function requiresTransitionNote(): bool
    {
        return in_array($this, [self::Sold, self::Dispatched], true);
    }

    /**
     * @return array<int, self>
     */
    public function nextAllowedStatuses(): array
    {
        return match ($this) {
            self::PendingApproval => [self::Submitted, self::Rejected, self::Cancelled],
            self::Submitted       => [self::Sold, self::Cancelled],
            self::Sold            => [self::Dispatched, self::Cancelled],
            self::Dispatched      => [self::Delivered, self::Cancelled],
            default               => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->nextAllowedStatuses(), true);
    }
}
