<?php

namespace App\Modules\Shared\Support;

use Illuminate\Support\Facades\Storage;

class PublicMediaUrl
{
    public static function fromPublicDisk(?string $path): string
    {
        $normalizedPath = self::normalize($path);

        if ($normalizedPath === '') {
            return '';
        }

        return Storage::disk('public')->url($normalizedPath);
    }

    private static function normalize(?string $path): string
    {
        $value = ltrim(trim((string) $path), '/');

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'storage/')) {
            $value = substr($value, strlen('storage/'));
        }

        return $value;
    }
}
