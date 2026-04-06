<?php

namespace App\Modules\Shared\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PublicMediaUrl
{
    public static function fromPublicDisk(?string $path): string
    {
        $normalizedPath = self::normalize($path);

        $url = '';

        if ($normalizedPath === '') {
            return $url;
        }

        try {
            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk('public');

            $driver = (string) config('filesystems.disks.public.driver', '');
            $ttlMinutes = max(1, (int) config('filesystems.public_media_signed_url_ttl', 20));

            // R2/S3 can be private. Prefer signed URLs and fallback to public URL when unsupported.
            if ($driver === 's3') {
                try {
                    $url = $disk->temporaryUrl($normalizedPath, now()->addMinutes($ttlMinutes));
                } catch (Throwable) {
                    // Fallback handled below.
                }
            }

            if ($url === '') {
                $url = (string) $disk->url($normalizedPath);
            }
        } catch (Throwable) {
            $url = '';
        }

        return $url;
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
