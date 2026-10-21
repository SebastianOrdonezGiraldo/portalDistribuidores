<?php

namespace App\Modules\Shared\Enums;

/**
 * Who edits an existing order: decides the editable statuses, the history
 * note and whether a rejected quotation is resubmitted for approval.
 */
enum OrderEditor: string
{
    case Admin = 'admin';
    case Company = 'company';

    public function canEdit(OrderStatus $status): bool
    {
        return match ($this) {
            self::Admin => $status->canBeEditedByAdmin(),
            self::Company => $status->canBeEditedByCompany(),
        };
    }

    public function historyNote(): string
    {
        return match ($this) {
            self::Admin => 'Cotización actualizada por administrador.',
            self::Company => 'Cotización actualizada por el cliente.',
        };
    }

    /**
     * A company fixing a rejected quotation sends it back to internal approval.
     */
    public function resubmitsRejectedOrders(): bool
    {
        return $this === self::Company;
    }
}
