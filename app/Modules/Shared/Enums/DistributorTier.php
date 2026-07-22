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

    public function description(): string
    {
        return match ($this) {
            DistributorTier::Silver => 'Precio comercial Plata con redondeo al alza. Los carritos abiertos y pedidos nuevos utilizan este nivel hasta un cambio manual.',
            DistributorTier::Gold => 'Precio base (Oro) con descuento respecto al precio Plata de lista. Los carritos abiertos y pedidos nuevos utilizan este nivel hasta un cambio manual.',
        };
    }

    public function shortDescription(): string
    {
        return match ($this) {
            DistributorTier::Silver => 'Precio comercial Plata.',
            DistributorTier::Gold => 'Precio base Oro con descuento respecto a Plata.',
        };
    }
}
