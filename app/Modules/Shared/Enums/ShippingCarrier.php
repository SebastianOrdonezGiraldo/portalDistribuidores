<?php

namespace App\Modules\Shared\Enums;

enum ShippingCarrier: string
{
    case Servientrega = 'servientrega';
    case Interrapidisimo = 'interrapidisimo';
    case Deprisa = 'deprisa';
    case Envia = 'envia';

    public function label(): string
    {
        return match ($this) {
            self::Servientrega => 'Servientrega',
            self::Interrapidisimo => 'Interrapidísimo',
            self::Deprisa => 'Deprisa',
            self::Envia => 'Envía',
        };
    }

    public static function detect(?string $trackingNumber): ?self
    {
        return match (substr(trim((string) $trackingNumber), 0, 1)) {
            '2', '3' => self::Servientrega,
            '7' => self::Interrapidisimo,
            '8' => self::Deprisa,
            '9' => self::Envia,
            default => null,
        };
    }

    /** @return array<int, string> */
    public static function prefixLabels(): array
    {
        return [
            '2' => self::Servientrega->label(),
            '3' => self::Servientrega->label(),
            '7' => self::Interrapidisimo->label(),
            '8' => self::Deprisa->label(),
            '9' => self::Envia->label(),
        ];
    }
}
