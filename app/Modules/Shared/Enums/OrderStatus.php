<?php

namespace App\Modules\Shared\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Submitted = 'submitted';
    case Sold = 'sold';
    case Dispatched = 'dispatched';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    // Legacy states kept for backward compatibility with historical data.
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'En revisión',
            self::PendingApproval => 'En revisión',
            self::Submitted => 'Registrado',
            self::Sold => 'Vendido',
            self::Dispatched => 'Despachado',
            self::Delivered => 'Entregado',
            self::Cancelled => 'Cancelado',
            self::Sending => 'Procesando',
            self::Sent => 'Enviado',
            self::Failed => 'Error',
            self::Rejected => 'Rechazado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft, self::PendingApproval => 'bg-amber-500',
            self::Submitted, self::Sold, self::Dispatched, self::Sending, self::Sent => 'bg-sky-600',
            self::Delivered => 'bg-emerald-600',
            self::Cancelled, self::Failed, self::Rejected => 'bg-red-600',
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

    public function canBeEditedByCompany(): bool
    {
        return in_array($this, [self::Draft, self::PendingApproval, self::Rejected], true);
    }

    public function canBeEditedByAdmin(): bool
    {
        return in_array($this, [self::Draft, self::PendingApproval, self::Rejected, self::Submitted], true);
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
            self::Submitted => [self::Sold, self::Cancelled],
            self::Sold => [self::Dispatched, self::Cancelled],
            self::Dispatched => [self::Delivered, self::Cancelled],
            self::Rejected => [self::PendingApproval, self::Cancelled],
            default => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->nextAllowedStatuses(), true);
    }

    /**
     * @return array<int, self>
     */
    public static function inventoryConsuming(): array
    {
        return [
            self::Submitted,
            self::Sold,
            self::Dispatched,
            self::Delivered,
        ];
    }
}
