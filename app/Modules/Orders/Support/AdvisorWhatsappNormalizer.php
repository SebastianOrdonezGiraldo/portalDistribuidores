<?php

namespace App\Modules\Orders\Support;

final class AdvisorWhatsappNormalizer
{
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || preg_match('/^\+?[\d\s().-]+$/u', $value) !== 1) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '3')) {
            $digits = '57'.$digits;
        }

        return preg_match('/^[1-9]\d{7,14}$/', $digits) === 1 ? $digits : null;
    }
}
