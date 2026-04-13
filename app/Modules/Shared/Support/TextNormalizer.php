<?php

namespace App\Modules\Shared\Support;

class TextNormalizer
{
    public static function normalize(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        $replacements = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ];

        $value = strtr($value, $replacements);

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    /**
     * @return list<string>
     */
    public static function tokenize(?string $value): array
    {
        $normalized = self::normalize($value);

        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(explode(' ', $normalized)));
    }
}
