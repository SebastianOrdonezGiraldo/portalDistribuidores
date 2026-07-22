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

    public static function resolveValue(?string $shippingCarrier, ?string $trackingNumber): ?string
    {
        $detectedCarrier = self::detect($trackingNumber);
        $trimmed = trim((string) $shippingCarrier);

        if ($trimmed === '') {
            return $detectedCarrier?->value;
        }

        if ($detectedCarrier && mb_strtolower($trimmed) === mb_strtolower($detectedCarrier->label())) {
            return $detectedCarrier->value;
        }

        return $trimmed;
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
