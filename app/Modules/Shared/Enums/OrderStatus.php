<?php

namespace App\Modules\Shared\Enums;

enum OrderStatus: string
{
    case Draft     = 'draft';
    case Submitted = 'submitted';
    case Sending   = 'sending';
    case Sent      = 'sent';
    case Failed    = 'failed';

    public function label(): string
    {
        return match ($this) {
            OrderStatus::Draft     => 'Borrador',
            OrderStatus::Submitted => 'Enviado',
            OrderStatus::Sending   => 'En proceso',
            OrderStatus::Sent      => 'Completado',
            OrderStatus::Failed    => 'Fallido',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            OrderStatus::Draft     => 'bg-amber-400',
            OrderStatus::Submitted => 'bg-emerald-400',
            OrderStatus::Sending   => 'bg-sky-400',
            OrderStatus::Sent      => 'bg-blue-400',
            OrderStatus::Failed    => 'bg-rose-400',
        };
    }
}

