<?php

namespace App\Modules\Shared\Enums;

enum PaymentMethod: string
{
    case Bancolombia = 'bancolombia';
    case Nequi = 'nequi';
    case Llave = 'llave';
    case Qr = 'qr';

    public function label(): string
    {
        $configured = config('commerce.payment.methods.'.$this->value.'.label');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return match ($this) {
            self::Bancolombia => 'Bancolombia',
            self::Nequi => 'Nequi',
            self::Llave => 'Llave',
            self::Qr => 'QR',
        };
    }

    /**
     * @return list<string>
     */
    public function instructionLines(): array
    {
        $lines = config('commerce.payment.methods.'.$this->value.'.lines', []);

        if (! is_array($lines)) {
            return ['Datos de pago pendientes de configurar.'];
        }

        return array_values(array_filter(array_map(
            static fn ($line) => is_string($line) ? trim($line) : '',
            $lines,
        )));
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $method) => $method->value, self::cases());
    }
}
