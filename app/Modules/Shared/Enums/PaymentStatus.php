<?php

namespace App\Modules\Shared\Enums;

enum PaymentStatus: string
{
    case NotApplicable = 'not_applicable';
    case PendingUpload = 'pending_upload';
    case Confirming = 'confirming';
    case Validated = 'validated';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::NotApplicable => 'Sin pago',
            self::PendingUpload => 'Pendiente de comprobante',
            self::Confirming => 'Confirmando pago',
            self::Validated => 'Pago validado',
            self::Rejected => 'Comprobante rechazado',
            self::Expired => 'Pago expirado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::NotApplicable => 'bg-slate-400',
            self::PendingUpload => 'bg-amber-500',
            self::Confirming => 'bg-sky-600',
            self::Validated => 'bg-emerald-600',
            self::Rejected, self::Expired => 'bg-red-600',
        };
    }

    public function allowsReceiptUpload(): bool
    {
        return in_array($this, [self::PendingUpload, self::Rejected], true);
    }

    public function blocksDispatchUnlessOk(): bool
    {
        return ! in_array($this, [self::NotApplicable, self::Validated], true);
    }

    /**
     * @return array<int, self>
     */
    public static function adminFilterable(): array
    {
        return [
            self::PendingUpload,
            self::Confirming,
            self::Validated,
            self::Rejected,
            self::Expired,
        ];
    }
}
